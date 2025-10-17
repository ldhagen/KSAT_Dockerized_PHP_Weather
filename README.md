# KSAT Dockerized PHP Weather Dashboard

![Docker Build Status](https://github.com/ldhagen/KSAT_Dockerized_PHP_Weather/actions/workflows/docker.yml/badge.svg)
![Docker Pulls](https://img.shields.io/docker/pulls/ldhagen/ksat-weather-app)
![Docker Image Version](https://img.shields.io/docker/v/ldhagen/ksat-weather-app)
![GitHub](https://img.shields.io/github/license/ldhagen/KSAT_Dockerized_PHP_Weather)

A comprehensive weather monitoring and visualization system built with PHP, MySQL, and Docker. This application provides real-time weather data, historical analysis, and interactive charts for San Antonio, Texas using the National Weather Service API.

## 🚀 Quick Deployment

### Deploy with Docker (Recommended)

```bash
# One-command deployment
curl -fsSL https://raw.githubusercontent.com/ldhagen/KSAT_Dockerized_PHP_Weather/main/deploy.sh | bash
Or manually:

bash
# Create deployment directory
mkdir ksat-weather && cd ksat-weather

# Download deployment files
curl -O https://raw.githubusercontent.com/ldhagen/KSAT_Dockerized_PHP_Weather/main/docker-compose.yml
curl -O https://raw.githubusercontent.com/ldhagen/KSAT_Dockerized_PHP_Weather/main/init.sql

# Start the application
docker-compose up -d
Access the dashboard: http://localhost:8085

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

CI/CD with GitHub Actions

Error handling and logging

RESTful API integration

Cache control and performance optimization

📦 Docker Images
Service	Image	Description
Web App	ldhagen/ksat-weather-app:latest	PHP/Apache web application
Database	mysql:8.0	MySQL database with persistent storage
Cron	ldhagen/ksat-weather-app:latest	Automated data collection
Available Tags
latest - Most recent stable version

2.0.0 - Version 2.0.0 release

git-<commit> - Specific Git commit builds

🔧 Development
Prerequisites
Docker and Docker Compose

Git

Local Development
bash
# Clone the repository
git clone https://github.com/ldhagen/KSAT_Dockerized_PHP_Weather.git
cd KSAT_Dockerized_PHP_Weather

# Start development environment
docker-compose up -d --build

# Access the application
open http://localhost:8085
Project Structure
text
KSAT_Dockerized_PHP_Weather/
├── .github/workflows/          # CI/CD pipelines
├── docker-compose.yml          # Multi-service configuration
├── Dockerfile                  # PHP/Apache container setup
├── init.sql                    # Database schema
├── config.php                  # Database configuration
├── index.php                   # Main dashboard
├── charts.php                  # Interactive charts
├── archive.php                 # Historical data
├── cron_fetch_weather.php      # Automated data collection
└── README.md                   # This file
🔄 Automated Data Collection
The system features continuous data collection that runs independently of user visits:

Frequency: Every 5 minutes

Method: Dockerized cron service

Reliability: Runs even when no browsers are open

Data Integrity: No gaps in historical records

Monitoring
bash
# Check cron service logs
docker-compose logs cron

# Verify data collection
docker-compose exec db mysql -u weather_user -pweather_pass weather_db -e "SELECT COUNT(*) as readings, MAX(timestamp) as latest FROM weather_readings;"
🛠️ Management Commands
bash
# View service status
docker-compose ps

# View logs
docker-compose logs -f

# Stop services
docker-compose down

# Restart services
docker-compose restart

# Update to latest version
docker-compose pull
docker-compose up -d
📊 Access Points
Main Dashboard: http://localhost:8085

Charts: http://localhost:8085/charts.php

Archive: http://localhost:8085/archive.php

Health Check: http://localhost:8085/health.php

🔍 Troubleshooting
Common Issues
No data in archive:

bash
# Check if cron service is running
docker-compose ps | grep cron

# Check cron logs
docker-compose logs cron
Database connection issues:

bash
# Check database health
docker-compose exec db mysqladmin ping -h localhost -uweather_user -pweather_pass
Application not accessible:

bash
# Check web service logs
docker-compose logs web

# Verify port mapping
docker-compose ps
🤝 Contributing
Fork the repository

Create a feature branch (git checkout -b feature/amazing-feature)

Commit your changes (git commit -m 'Add amazing feature')

Push to the branch (git push origin feature/amazing-feature)

Open a Pull Request

📄 License
This project is licensed under the MIT License - see the LICENSE file for details.

🙏 Acknowledgments
National Weather Service for providing free API access

Docker community for containerization tools

PHP and MySQL communities

Version: 2.0.0
Last Updated: 17 Oct 2025
Maintainer: Lance Hagen

For support or questions, please check the troubleshooting section or open an issue in the repository.


