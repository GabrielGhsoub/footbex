<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FootballDataService
{
    protected Client $client;
    protected ?string $apiToken;

    public function __construct()
    {
        $this->apiToken = config('services.football-data.token');

        if (empty($this->apiToken)) {
            Log::critical('FootballDataService: X-Auth-Token is NOT configured. Please check your .env and config/services.php file.');
        } else {
            Log::debug('FootballDataService: Service initialized with an API token.');
        }

        $this->client = new Client([
            'base_uri'        => 'https://api.football-data.org/v4/',
            'headers'         => [
                'X-Auth-Token' => $this->apiToken,
                'Accept'       => 'application/json',
            ],
            'timeout'         => 10,
            'connect_timeout' => 5,
        ]);
    }

    /**
     * Fetch and cache Premier League matches for a given week.
     *
     * @param Carbon|null $referenceDate A date within the week to fetch. Defaults to 1 week ago.
     * @return array ['success' => bool, 'data' => array, 'message' => string|null]
     */
    public function getWeeklyMatches(Carbon $referenceDate = null): array
    {
        if ($referenceDate === null) {
            Log::debug('FootballDataService: getWeeklyMatches called with no referenceDate. Defaulting to 1 week ago.');
            $referenceDate = Carbon::now()->subWeeks(1);
        } else {
            Log::debug('FootballDataService: getWeeklyMatches called with referenceDate: ' . $referenceDate->toDateTimeString());
        }

        $dateFrom = $referenceDate->copy()->startOfWeek(Carbon::MONDAY)->toDateString();
        $dateTo  = $referenceDate->copy()->startOfWeek(Carbon::MONDAY)->addDays(7)->toDateString();
        
        $cacheKey = "pl-matches-weekly-{$dateFrom}-to-{$dateTo}";
        $cacheDuration = now()->addHour();

        Log::info("FootballDataService: Preparing to fetch matches.", [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'cacheKey' => $cacheKey
        ]);

        if (Cache::has($cacheKey)) {
            Log::info("FootballDataService: Cache HIT for key '{$cacheKey}'. Returning cached data.");
        } else {
            Log::info("FootballDataService: Cache MISS for key '{$cacheKey}'. Will query API.");
        }

        return Cache::remember(
            $cacheKey,
            $cacheDuration,
            function () use ($dateFrom, $dateTo) {
                if (empty($this->apiToken)) {
                    return ['success' => false, 'data' => [], 'message' => 'API token is not configured.'];
                }
                
                try {
                    $response = $this->client->get('competitions/PL/matches', [
                        'query' => ['dateFrom' => $dateFrom, 'dateTo' => $dateTo],
                    ]);

                    $rawBody = $response->getBody()->getContents();
                    $body = json_decode($rawBody, true);

                    Log::debug("FootballDataService: Received API response.", [
                        'status_code' => $response->getStatusCode(),
                        'response_body_preview' => substr($rawBody, 0, 1000)
                    ]);

                    if ($response->getStatusCode() !== 200 || !isset($body['matches']) || !is_array($body['matches'])) {
                        Log::error('Invalid response structure from football-data API', ['status' => $response->getStatusCode(), 'body_preview' => substr($rawBody, 0, 500)]);
                        return ['success' => false, 'data' => [], 'message' => 'Invalid API response.'];
                    }

                    $matchCount = $body['resultSet']['count'] ?? count($body['matches']);
                    Log::info("FootballDataService: API returned {$matchCount} matches.");

                    $out = [];
                    foreach ($body['matches'] as $m) {
                        $requiredKeys = ['id', 'utcDate', 'status', 'homeTeam', 'awayTeam', 'score'];
                        if (count(array_intersect_key(array_flip($requiredKeys), $m)) !== count($requiredKeys)) {
                            Log::warning('Skipping match due to missing essential data keys.', ['match_id' => $m['id'] ?? 'N/A', 'raw_match_data' => $m]);
                            continue;
                        }

                        $utc = Carbon::parse($m['utcDate']);
                        $scoreStr = null;
                        if ($m['status'] === 'FINISHED') {
                            $scoreStr = ($m['score']['fullTime']['home'] ?? '?') . ' – ' . ($m['score']['fullTime']['away'] ?? '?');
                        }

                        $out[] = [
                            'id' => $m['id'],
                            'utcDate' => $m['utcDate'],
                            'date' => $utc->toDateString(),
                            'time' => $utc->format('H:i'),
                            'home' => ['id' => $m['homeTeam']['id'] ?? null, 'name' => $m['homeTeam']['name'] ?? 'N/A', 'crest' => $m['homeTeam']['crest'] ?? null],
                            'away' => ['id' => $m['awayTeam']['id'] ?? null, 'name' => $m['awayTeam']['name'] ?? 'N/A', 'crest' => $m['awayTeam']['crest'] ?? null],
                            'status' => ['value' => $m['status'], 'finished' => $m['status'] === 'FINISHED', 'scoreStr' => $scoreStr],
                        ];
                    }

                    Log::info("FootballDataService: Processed " . count($out) . " matches successfully.");
                    return ['success' => true, 'data' => $out, 'message' => null];
                } catch (RequestException $e) {
                    Log::error('Failed to fetch data from football-data API', ['message' => $e->getMessage(), 'response' => $e->hasResponse() ? (string) $e->getResponse()->getBody() : 'N/A']);
                    return ['success' => false, 'data' => [], 'message' => 'Could not load match data.'];
                } catch (\Exception $e) {
                    Log::critical('An unexpected error occurred in FootballDataService', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                    return ['success' => false, 'data' => [], 'message' => 'An unexpected server error occurred.'];
                }
            }
        );
    }

    /**
     * Fetch and cache details for a given match ID.
     */
    public function getMatchDetails(int $matchId): ?array
    {
        return Cache::remember("pl-match-details-{$matchId}", now()->addHours(1), function () use ($matchId) {
            try {
                $response = $this->client->get("matches/{$matchId}");
                return json_decode($response->getBody()->getContents(), true);
            } catch (\Exception $e) {
                Log::error("Failed to fetch details for match ID {$matchId}", ['error' => $e->getMessage()]);
                return null;
            }
        });
    }
}
