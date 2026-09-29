<?php

$db = __DIR__ . '/database.sql';
try {
    $conn = new PDO("sqlite:$db");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        title TEXT NOT NULL,
        photo TEXT NOT NULL,
        bio TEXT NOT NULL,
        price REAL NOT NULL,
        user_id INTEGER NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES login(id)
    )");
    echo "<script>console.log('Connected to database!');</script>";
} catch (PDOException $e) {
    echo $e->getMessage();
}

ini_set('display_errors', 1);
error_reporting(E_ALL);