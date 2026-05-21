<?php
/**
 * Database Connection Wrapper
 * Uses PDO for full SQL Injection mitigation
 */

require_once __DIR__ . '/config.php';

try {
    // Attempt to connect to the database
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // If the database does not exist, let's try connecting to host and auto-creating it
    try {
        $dsn_no_db = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
        $pdo_temp = new PDO($dsn_no_db, DB_USER, DB_PASS);
        $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
        
        // Retry connection to the newly created database
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        
        // Automatically import schema if empty
        $query = $pdo->query("SHOW TABLES");
        if ($query->rowCount() === 0) {
            $sql = file_get_contents(__DIR__ . '/schema.sql');
            $pdo->exec($sql);
        }
    } catch (PDOException $ex) {
        die("Connection failed: " . $ex->getMessage());
    }
}
