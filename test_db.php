<?php
try {
    $pdo = new PDO(
        "mysql:host=localhost",
        "root",  // Replace with your MySQL username
        ""       // Replace with your MySQL password
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS ecommerce");
    echo "Database created successfully or already exists.<br>";
    
    // Select the database
    $pdo->exec("USE ecommerce");
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/database/ecommerce.sql');
    $pdo->exec($sql);
    
    echo "Database tables and sample data created successfully!<br>";
    
    // Test by counting products
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $count = $stmt->fetchColumn();
    echo "Number of products in database: " . $count;
    
} catch(PDOException $e) {
    die("ERROR: " . $e->getMessage());
}
?>
