CREATE TABLE users ( 
    id INT AUTO_INCREMENT PRIMARY KEY, 
    email VARCHAR(255) UNIQUE NOT NULL, 
    password VARCHAR(255) NOT NULL, 
    verification_token VARCHAR(255), 
    verified BOOLEAN DEFAULT FALSE, 
    totp_secret VARCHAR(32) NULL, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP 
);
