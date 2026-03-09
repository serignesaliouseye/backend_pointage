<?php
return [
    'panels' => [
        'admin' => [
            'path' => 'admin',
            'domain' => env('FILAMENT_DOMAIN'),
            'auth' => [
                'guard' => 'web',
                'pages' => [
                    'login' => \Filament\Pages\Auth\Login::class,
                ],
            ],
            'resources' => [
                \App\Filament\Resources\UserResource::class,
                \App\Filament\Resources\PointageResource::class,
                \App\Filament\Resources\SanctionResource::class,
            ],
            'widgets' => [
                \App\Filament\Widgets\StatsOverview::class,
                \App\Filament\Widgets\PresencesChart::class,
                \App\Filament\Widgets\RetardsChart::class,
            ],
        ],
    ],
];