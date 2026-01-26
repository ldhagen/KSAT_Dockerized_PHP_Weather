# KSAT Weather App Docker Image

Docker image for the KSAT Weather Dashboard - a comprehensive weather monitoring system for San Antonio, Texas.

## Quick Start

```bash
# Create project directory
mkdir ksat-weather && cd ksat-weather

# Download production compose file
curl -O https://raw.githubusercontent.com/ldhagen/KSAT_Dockerized_PHP_Weather/main/docker-compose.prod.yml

# Rename to docker-compose.yml
mv docker-compose.prod.yml docker-compose.yml

# Download database init script
curl -O https://raw.githubusercontent.com/ldhagen/KSAT_Dockerized_PHP_Weather/main/init.sql

# Start the application
docker-compose up -d

Access the dashboard at: http://localhost:8085

Features
🌤️ Real-time weather data from National Weather Service

📊 Interactive charts and historical data

🔄 Automated data collection every 15 minutes

🗄️ MySQL database with persistent storage

🐳 Full Docker containerization

❤️ Health checks and monitoring

Image Contents
PHP 8.2 with Apache

MySQL client extensions

Cron for scheduled tasks

All application code pre-loaded

Environment Variables
All environment variables are set in the docker-compose file. No additional configuration required.

Health Check
The image includes health checks that verify:

Web server responsiveness

Database connectivity

Data freshness

Source Code
For full source code and development setup, visit:
https://github.com/ldhagen/KSAT_Dockerized_PHP_Weather

Tags
latest - Most recent stable version

2.0.0 - Version 2.0.0 release
