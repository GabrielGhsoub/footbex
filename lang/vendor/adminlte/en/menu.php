<?php
// config/menu.php

return [
    'main_navigation' => 'MAIN NAVIGATION',
    'dashboard' => [
        'text' => 'Dashboard',
        'url'  => 'home',
        'icon' => 'fas fa-tachometer-alt',
    ],
    'matches' => [
        'text'    => 'Matches',
        'icon'    => 'fas fa-futbol',
        'submenu' => [
            [
                'text' => 'Upcoming Matches',
                'url'  => 'matches/upcoming',
            ],
            [
                'text' => 'Past Results',
                'url'  => 'matches/results',
            ],
        ],
    ],
    'scores' => [
        'text' => 'Scores',
        'url'  => 'scores',
        'icon' => 'fas fa-list-ol',
    ],
    'betting' => [
        'text'    => 'Betting',
        'icon'    => 'fas fa-money-bill-alt',
        'submenu' => [
            [
                'text' => 'Place Bet',
                'url'  => 'betting/place',
            ],
            [
                'text' => 'My Bets',
                'url'  => 'betting/my-bets',
            ],
            [
                'text' => 'Jackpot',
                'url'  => 'betting/jackpot',
            ],
        ],
    ],
    'account_settings' => [
        'text' => 'ACCOUNT SETTINGS',
        'url'  => 'settings',
        'icon' => 'fas fa-user-cog',
        'submenu' => [
            [
                'text' => 'Profile',
                'url'  => 'profile',
            ],
            [
                'text' => 'Change Password',
                'url'  => 'change-password',
            ],
        ],
    ],
    'labels' => [
        'text'       => 'LABELS',
        'icon_color' => 'red',
        'submenu' => [
            [
                'text'       => 'Important',
                'icon_color' => 'yellow',
            ],
            [
                'text'       => 'Warning',
                'icon_color' => 'cyan',
            ],
            [
                'text'       => 'Information',
                'icon_color' => 'blue',
            ],
        ],
    ],
];
