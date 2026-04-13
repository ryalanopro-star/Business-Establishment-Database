<?php

// This file handles the database connection using PDO

// Database credentials
$host = "localhost";          // Database server
$dbname = "pc_peripherals_db"; // Database name
$username = "root";          
$password = "";               
try {
    // Create a new PDO connection to MySQL
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname",
        $username,
        $password
    );

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // This makes database results easier to work with (e.g. $row['name'])
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If connection fails, stop execution and show error message
    die("Database connection failed: " . $e->getMessage());
}

?>