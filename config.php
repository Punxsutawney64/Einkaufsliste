<?php
declare(strict_types=1);

return [
    'db' => [
        'host' => getenv('SHOPPINGLIST_DB_HOST') ?: 'andromeda.local',
        'port' => (int) (getenv('SHOPPINGLIST_DB_PORT') ?: 9474),
        'name' => getenv('SHOPPINGLIST_DB_NAME') ?: 'SHOPPINGLIST',
        'user' => getenv('SHOPPINGLIST_DB_USER') ?: 'DBUSER',
        'password' => getenv('SHOPPINGLIST_DB_PASSWORD') ?: 'BonnieTyler*1951',
    ],
];

