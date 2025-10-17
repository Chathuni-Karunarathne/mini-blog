<?php
// config.php - database credentials for XAMPP
return (object)[
    'db' => (object)[
        'host' => '127.0.0.1',
        'port' => '3306',
        'name' => 'mini-blog',
        'user' => 'root',
        'pass' => '', // XAMPP default is empty password
        'charset' => 'utf8mb4',
    ],
];