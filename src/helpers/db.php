<?php
// src/helpers/db.php
// Establishes and returns a connection to the MySQL database for all database operations
$config = require __DIR__ . '/../../config.php';
$db = $config->db;

$dsn = "mysql:host={$db->host};port={$db->port};dbname={$db->name};charset={$db->charset}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $db->user, $db->pass, $options);
} catch (PDOException $e) {
    die("Database Connection failed: " . $e->getMessage());
}


return $pdo;  
