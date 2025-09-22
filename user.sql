CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY, 
    username VARCHAR(100) NOT NULL,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(255) UNIQUE NOT NULL, 
    password VARCHAR(255) NOT NULL, 
    verification_token VARCHAR(255), 
    email_verified BOOLEAN DEFAULT FALSE, 
    verified BOOLEAN DEFAULT FALSE, 
    totp_secret VARCHAR(32) NULL, 
    token_expiry TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
--#Sets the storage engine to InnoDB Character Encoding: Uses UTF-8 MB4 encoding helps with emojis and languages to i think

