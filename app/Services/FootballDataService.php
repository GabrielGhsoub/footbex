<?php
namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache; // Import Log facade
use Illuminate\Support\Facades\Log;
// Import Guzzle exception

class FootballDataService
{
    protected Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri'        => 'https://api.football-data.org/v4/',
            'headers'         => [
                // Ensure the token is correctly loaded from your config/services.php or .env
                'X-Auth-Token' => config('services.football-data.token'),
                'Accept'       => 'application/json',
            ],
            'timeout'         => 10, // Add a reasonable timeout
            'connect_timeout' => 5,  // Add a connect timeout
        ]);
    }

    /**
     * Fetch and cache Premier League matches for the current week (Mon–Sun),
     * including crest URLs and structured data for the frontend.
     *
     * @return array ['success' => bool, 'data' => array, 'message' => string|null]
     */
    public function getWeeklyMatches(): array
    {
        // Use current date based on server time (or adjust timezone if needed)
        // Note: The sample API call uses May 2025, using Carbon::now() for dynamic behaviour
        $dateFrom = Carbon::now()->subWeeks(15)->startOfWeek(Carbon::MONDAY)->toDateString();
        $dateTo   = Carbon::now()->subWeeks(15)->endOfWeek(Carbon::SUNDAY)->toDateString();
        $cacheKey = "pl-matches-weekly-{$dateFrom}-to-{$dateTo}";
        // Cache for 1 hour, adjust as needed based on update frequency
        $cacheDuration = now()->addHour();

        // *** Logging added around Cache::remember ***
        Log::debug("Attempting to retrieve weekly matches from cache or API.", ['cacheKey' => $cacheKey]);

        return Cache::remember(
            $cacheKey,
            $cacheDuration,
            function () use ($dateFrom, $dateTo) {
                try {
                    Log::info("FootballDataService: Fetching matches from API for {$dateFrom} to {$dateTo}");
                    $response = $this->client->get('competitions/PL/matches', [
                        'query' => [
                            'dateFrom' => $dateFrom,
                            'dateTo'   => $dateTo,
                        ],
                    ]);

                    $body = json_decode($response->getBody()->getContents(), true);

                    if ($response->getStatusCode() !== 200 || ! isset($body['matches']) || ! is_array($body['matches'])) {
                        Log::error('Invalid response structure or status code from football-data API', [
                            'status'       => $response->getStatusCode(),
                            'body_preview' => substr(json_encode($body), 0, 500), // Log preview of body
                        ]);
                        return ['success' => false, 'data' => [], 'message' => 'Invalid API response.'];
                    }

                    Log::info("FootballDataService: Received {$body['resultSet']['count']} matches from API.");

                    $out = [];
                    foreach ($body['matches'] as $m) {
                        // *** ADDED LOGGING HERE: Raw match data ***
                        Log::debug('Raw match data from API:', ['match_id' => $m['id'] ?? 'N/A', 'raw_match' => $m]);

                        // Basic validation for essential keys within a match object
                        if (! isset($m['id'], $m['utcDate'], $m['status'], $m['homeTeam']['id'], $m['awayTeam']['id'], $m['score'])) {
                            Log::warning('Skipping match due to missing essential data', ['match_data' => $m]);
                            continue;
                        }

                        $utc = Carbon::parse($m['utcDate']);

                        // Determine score string based on status
                        $scoreStr = null;
                        if ($m['status'] === 'FINISHED') {
                            $scoreStr = ($m['score']['fullTime']['home'] ?? '?') . ' – ' . ($m['score']['fullTime']['away'] ?? '?');
                        } elseif ($m['status'] !== 'SCHEDULED' && $m['status'] !== 'CANCELLED' && $m['status'] !== 'POSTPONED') {
                            // For live, paused, etc., still show current score if available
                            $scoreStr = ($m['score']['fullTime']['home'] ?? '?') . ' – ' . ($m['score']['fullTime']['away'] ?? '?');
                        }

                        // Create the formatted array
                        $formattedMatch = [
                            'id'      => $m['id'],
                            'utcDate' => $m['utcDate'],
                            'date'    => $utc->toDateString(),
                            'time'    => $utc->format('H:i'),
                            'home'    => [
                                'id'    => $m['homeTeam']['id'], // Include ID if needed later
                                'name'  => $m['homeTeam']['name'] ?? 'N/A',
                                'score' => $m['score']['fullTime']['home'] ?? null, // Keep individual score if needed elsewhere
                                'crest' => $m['homeTeam']['crest'] ?? null,         // Include crest URL
                            ],
                            'away'    => [
                                'id'    => $m['awayTeam']['id'],
                                'name'  => $m['awayTeam']['name'] ?? 'N/A',
                                'score' => $m['score']['fullTime']['away'] ?? null,
                                'crest' => $m['awayTeam']['crest'] ?? null, // Include crest URL
                            ],
                            'status'  => [
                                'value'    => $m['status'],                                                              // e.g., SCHEDULED, FINISHED, IN_PLAY
                                'started'  => ! in_array($m['status'], ['SCHEDULED', 'TIMED', 'CANCELLED', 'POSTPONED']), // More robust check if match has started or is finished
                                'finished' => $m['status'] === 'FINISHED',
                                'scoreStr' => $scoreStr, // Central score string (e.g., "2 – 1" or null)
                            ],
                        ];

                        // *** ADDED LOGGING HERE: Formatted match data ***
                        Log::debug('Formatted match for frontend:', ['match_id' => $formattedMatch['id'], 'formatted_match' => $formattedMatch]);

                        $out[] = $formattedMatch; // Add the formatted match to the output array
                    }

                    Log::info("FootballDataService: Processed " . count($out) . " matches successfully for cache.");
                    return [
                        'success' => true,
                        'data'    => $out,
                        'message' => null,
                    ];

                } catch (RequestException $e) {
                    $context = [
                        'message'     => $e->getMessage(),
                        'code'        => $e->getCode(),
                        'request_uri' => $e->getRequest() ? (string) $e->getRequest()->getUri() : 'N/A',
                        'response'    => $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'N/A',
                    ];
                    Log::error('Failed to fetch data from football-data API', $context);
                    // Provide a user-friendly error message
                    return ['success' => false, 'data' => [], 'message' => 'Could not load match data. The service might be temporarily unavailable.'];
                } catch (\Exception $e) {
                    // Catch other potential errors (e.g., Carbon parsing, unexpected structure)
                    Log::error('An unexpected error occurred in FootballDataService getWeeklyMatches', [
                        'message' => $e->getMessage(),
                        'file'    => $e->getFile(),
                        'line'    => $e->getLine(),
                        // 'trace' => $e->getTraceAsString(), // Avoid logging full trace in production unless necessary
                    ]);
                    return ['success' => false, 'data' => [], 'message' => 'An unexpected error occurred while processing match data.'];
                }
            }
        );
    }

    /**
     * Fetch and cache details for a given match ID.
     * Consider adding more details here if needed for the details page.
     *
     * @param  int  $matchId
     * @return array|null Returns match details array or null on failure/not found.
     */
    public function getMatchDetails(int $matchId): ?array
    {
        $cacheKey = "pl-match-details-{$matchId}";
        // Cache for longer if details don't change often post-match, shorter if live details needed
        $cacheDuration = now()->addHours(1);

        return Cache::remember(
            $cacheKey,
            $cacheDuration,
            function () use ($matchId) {
                try {
                    Log::info("FootballDataService: Fetching details for match ID {$matchId}");
                    $response = $this->client->get("matches/{$matchId}");
                    $body     = json_decode($response->getBody()->getContents(), true);

                    if ($response->getStatusCode() === 200 && isset($body['id']) && $body['id'] == $matchId) {
                        Log::info("FootballDataService: Successfully fetched details for match ID {$matchId}");
                        return $body; // Return the full match details
                    } elseif ($response->getStatusCode() === 404) {
                        Log::warning("FootballDataService: Match ID {$matchId} not found (404).");
                        return null;
                    } else {
                        Log::error('Invalid response or status code fetching match details', [
                            'match_id'     => $matchId,
                            'status'       => $response->getStatusCode(),
                            'body_preview' => substr(json_encode($body), 0, 500), // Log preview
                        ]);
                        return null;
                    }

                } catch (RequestException $e) {
                    $context = [
                        'match_id' => $matchId,
                        'message'  => $e->getMessage(),
                        'code'     => $e->getCode(),
                        'response' => $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'N/A',
                    ];
                    Log::error('Failed to fetch match details from football-data API', $context);
                    return null; // Return null on failure
                } catch (\Exception $e) {
                    Log::error('An unexpected error occurred fetching match details', [
                        'match_id' => $matchId,
                        'message'  => $e->getMessage(),
                        'file'     => $e->getFile(),
                        'line'     => $e->getLine(),
                    ]);
                    return null;
                }
            }
        );
    }
}
