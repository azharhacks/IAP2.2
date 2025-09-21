<?php 
require_once 'config.php'; 

try { 
    $pdo = new PDO("mysql:host=" . DB_HOST . "auth_db" . DB_NAME, DB_USER, DB_PASS); 
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) { 
    die("Connection failed: " . $e->getMessage()); 
} 
?>
