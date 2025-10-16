ASAI Weather Dashboard
A comprehensive weather monitoring and visualization system built with PHP, MySQL, and Docker. This application provides real-time weather data, historical analysis, and interactive charts for San Antonio, Texas using the National Weather Service API.

https://img.shields.io/badge/version-2.0.0-blue.svg
https://img.shields.io/badge/PHP-8.2+-purple.svg
https://img.shields.io/badge/MySQL-8.0-blue.svg
https://img.shields.io/badge/Docker-Ready-green.svg
https://img.shields.io/badge/Data%2520Collection-Automated-success.svg

🌟 Features
Current Weather
Real-time weather conditions from NWS API

Temperature, humidity, wind speed/direction, pressure, dew point, and visibility

7-day weather forecast

Auto-refresh every 5 minutes in browser

Automated data collection every 5 minutes (even when browser is closed)

Responsive design for all devices

Data Archiving
Continuous automated storage of all weather readings

MySQL database with proper indexing

Paginated archive view with date filtering

Export-ready data structure

Interactive Charts
Multiple chart types (line, combo, time series)

Temperature, humidity, wind speed, pressure trends

Date range filtering

Statistical summaries

Mobile-responsive chart layouts

Technical Features
Docker containerization with multi-service architecture

MySQL database persistence

Automated cron-based data collection

Error handling and logging

RESTful API integration

Cache control and performance optimization

🚀 Quick Start
Prerequisites
Docker and Docker Compose

Git

Installation
Clone the repository

bash
git clone https://github.com/ldhagen/KSAT_Dockerized_PHP_Weather.git
cd KSAT_Dockerized_PHP_Weather
Start the application

bash
docker-compose up -d
Access the dashboard

Main Dashboard: http://localhost:8085

Charts: http://localhost:8085/charts.php

Archive: http://localhost:8085/archive.php

🔄 Automated Data Collection
The system now features continuous data collection that runs independently of user visits:

Frequency: Every 5 minutes

Method: Dockerized cron service

Reliability: Runs even when no browsers are open

Data Integrity: No gaps in historical records

How it Works
Cron Service: A dedicated Docker container runs scheduled tasks

Weather Fetching: cron_fetch_weather.php executes every 5 minutes

Data Storage: All readings are automatically archived to MySQL

Error Handling: Failed attempts are logged for monitoring

Monitoring Data Collection
Check the health of automated data collection:

bash
# View cron service logs
docker-compose logs cron

# Check recent data entries
docker-compose exec db mysql -u weather_user -p weather_db -e "SELECT COUNT(*) as total_readings, MAX(timestamp) as latest FROM weather_readings;"
📁 Project Structure
text
weather-dashboard/
├── docker-compose.yml          # Docker services configuration
├── Dockerfile                  # PHP/Apache container setup
├── init.sql                    # Database schema and initial data
├── config.php                  # Database configuration and utilities
├── index.php                   # Main dashboard with current weather
├── charts.php                  # Interactive weather charts
├── archive.php                 # Historical data archive
├── cron_fetch_weather.php      # Automated weather data fetcher
└── README.md                   # This file
🐳 Docker Services
The application runs in a multi-container Docker environment:

web: PHP/Apache web server (port 8085)

db: MySQL 8.0 database with persistent storage

cron: Automated data collection service (runs every 5 minutes)

Service Details
yaml
services:
  web:      # Main application interface
  db:       # Data persistence  
  cron:     # Automated data collection
📊 Data Management
Database Schema
sql
weather_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    temperature DECIMAL(5,2),    # °F
    humidity DECIMAL(5,2),       # %
    wind_speed DECIMAL(5,2),     # mph
    wind_direction INT,          # degrees
    pressure DECIMAL(6,2),       # inHg
    dew_point DECIMAL(5,2),      # °F
    visibility DECIMAL(5,2),     # miles
    conditions VARCHAR(255),     # text description
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)
Data Retention
Data is stored indefinitely by default

Optional: Add automatic cleanup of old records (see comments in code)

All data is exportable via the archive interface

🛠️ Development
Running in Development Mode
bash
# Start with log viewing
docker-compose up

# Run specific service
docker-compose up web db

# View logs
docker-compose logs -f web
docker-compose logs -f cron
Database Access
bash
# Connect to MySQL
docker-compose exec db mysql -u weather_user -p weather_db

# Export data
docker-compose exec db mysqldump -u weather_user -p weather_db > backup.sql
Troubleshooting
Check if automated data collection is working:

bash
# Check cron service status
docker-compose ps cron

# View cron logs
docker-compose logs cron

# Verify data is being collected
docker-compose exec db mysql -u weather_user -p weather_db -e "SELECT COUNT(*) as readings, MIN(timestamp) as first, MAX(timestamp) as latest FROM weather_readings;"
Common Issues:

No data in archive: Check if cron service is running

API errors: Verify internet connectivity and NWS API status

Database connection: Ensure MySQL container is healthy

🔧 Configuration
Environment Variables
TZ=America/Chicago - Timezone for data display

DB_HOST=db - Database host

DB_NAME=weather_db - Database name

DB_USER=weather_user - Database user

DB_PASS=weather_pass - Database password

Customization Options
Change data collection interval:
Modify the cron expression in docker-compose.yml:

yaml
# For every 10 minutes: '*/10 * * * *'
# For every hour: '0 * * * *'
command: >
  sh -c "
  echo '*/5 * * * * /usr/local/bin/php /var/www/html/cron_fetch_weather.php > /dev/null 2>&1' > /etc/cron.d/weather-cron
Modify location coordinates:
Update in index.php and cron_fetch_weather.php:

php
$latitude = 29.4241;   // San Antonio, TX
$longitude = -98.4936;
📈 Monitoring & Maintenance
Health Checks
Visit the archive page to verify data collection

Check Docker container status: docker-compose ps

Monitor logs: docker-compose logs -f

Backup Strategy
bash
# Regular database backups
docker-compose exec db mysqldump -u weather_user -p weather_db > backup_$(date +%Y%m%d).sql

# Backup Docker volumes
docker-compose down
tar -czf weather_data_backup.tar.gz db_data/
🤝 Contributing
Fork the repository

Create a feature branch

Make your changes

Test with docker-compose up

Submit a pull request

📄 License
This project is licensed under the MIT License - see the LICENSE file for details.

🙏 Acknowledgments
National Weather Service for providing free API access

Docker community for containerization tools

PHP and MySQL communities

Version: 2.0.0
Last Updated: 2024
Maintainer: Lance Hagen

For support or questions, please check the troubleshooting section or open an issue in the repository.


