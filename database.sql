CREATE DATABASE IF NOT EXISTS kilometer_db;
USE kilometer_db;


CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bmi_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    weight DECIMAL(5,2) NOT NULL,
    height DECIMAL(5,2) NOT NULL,
    age INT NOT NULL,
    gender ENUM('male', 'female') NOT NULL,
    activity_level VARCHAR(50),
    bmi DECIMAL(4,1) NOT NULL,
    bmi_status VARCHAR(20) NOT NULL,
    daily_calories INT NOT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS running_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    distance_km DECIMAL(5,2) NOT NULL,
    duration_minutes INT NOT NULL,
    calories_burned INT NOT NULL,
    burn_rate DECIMAL(5,2),
    notes TEXT,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Goals table
CREATE TABLE IF NOT EXISTS goals (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    title         VARCHAR(120) NOT NULL,
    category      ENUM('distance','calories','sessions','weight','bmi','custom') NOT NULL DEFAULT 'distance',
    target_value  DECIMAL(10,2) NOT NULL,
    current_value DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit          VARCHAR(20) DEFAULT '',
    deadline      DATE NULL,
    status        ENUM('active','completed','paused') NOT NULL DEFAULT 'active',
    notes         TEXT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
