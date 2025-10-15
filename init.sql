-- init.sql
CREATE DATABASE IF NOT EXISTS weather_db;
USE weather_db;

CREATE TABLE IF NOT EXISTS weather_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    temperature DECIMAL(5,2),
    humidity DECIMAL(5,2),
    wind_speed DECIMAL(5,2),
    wind_direction INT,
    pressure DECIMAL(6,2),
    dew_point DECIMAL(5,2),
    visibility DECIMAL(5,2),
    conditions VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_timestamp ON weather_readings(timestamp);