<?php
/**
 * Database Setup and Testing Script
 * Creates the ecommerce database and populates it with schema and sample data
 * This script is useful for initial project setup and development environment
 */

try {
    // Connect to MySQL server without selecting a specific database
    $pdo = new PDO(
        "mysql:host=localhost",
        "root",  // Replace with your MySQL username
        ""       // Replace with your MySQL password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    // This prevents errors if database already exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS ecommerce");
    echo "Database created successfully or already exists.<br>";
    
    // Select the newly created database for operations
    $pdo->exec("USE ecommerce");
    
    // Read SQL schema file and execute it
    // This creates all necessary tables with relationships
    $sql = file_get_contents(__DIR__ . '/database/ecommerce.sql');
    $pdo->exec($sql);
    
    echo "Database tables and sample data created successfully!<br>";
    
    // Test database setup by counting products
    // Verifies that the products table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $count = $stmt->fetchColumn();
    echo "Number of products in database: " . $count;
    
} catch(PDOException $e) {
    // Display error message if database operations fail
    die("ERROR: " . $e->getMessage());
}
?>
