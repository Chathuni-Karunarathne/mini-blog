<?php
// src/helpers/db.php
// Establishes and returns a connection to the MySQL database for all database operations

// Load database credentials from the config file
$config = require __DIR__ . '/../../config.php';
$db = $config->db;


$dsn = "mysql:host={$db->host};port={$db->port};dbname={$db->name};charset={$db->charset}";


// Configure PDO options for error handling and result formatting$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Attempt to create a new PDO database connection with the credentials and options
try {
    $pdo = new PDO($dsn, $db->user, $db->pass, $options);
} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}


return $pdo;  //  THIS LINE IS IMPORTANT
