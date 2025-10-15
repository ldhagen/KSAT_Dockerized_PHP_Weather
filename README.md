# ASAI Weather Dashboard

A comprehensive weather monitoring and visualization system built with PHP, MySQL, and Docker. This application provides real-time weather data, historical analysis, and interactive charts for San Antonio, Texas using the National Weather Service API.

![Weather Dashboard](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.2+-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-8.0-blue.svg)
![Docker](https://img.shields.io/badge/Docker-Ready-green.svg)

## 🌟 Features

### Current Weather
- Real-time weather conditions from NWS API
- Temperature, humidity, wind speed/direction, pressure, dew point, and visibility
- 7-day weather forecast
- Auto-refresh every 5 minutes
- Responsive design for all devices

### Data Archiving
- Automatic storage of all weather readings
- MySQL database with proper indexing
- Paginated archive view with date filtering
- Export-ready data structure

### Interactive Charts
- Multiple chart types (line, combo, time series)
- Temperature, humidity, wind speed, pressure trends
- Date range filtering
- Statistical summaries
- Mobile-responsive chart layouts

### Technical Features
- Docker containerization
- MySQL database persistence
- Error handling and logging
- RESTful API integration
- Cache control and performance optimization

## 🚀 Quick Start

### Prerequisites
- Docker and Docker Compose
- Git

### Installation

**Clone the repository**
   ```bash
   git clone https://github.com/yourusername/weather-dashboard.git
   cd weather-dashboard

Start the application

bash
docker-compose up -d
Access the dashboard

Main Dashboard: http://localhost:8085

Charts: http://localhost:8085/charts.php

Archive: http://localhost:8085/archive.php

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
└── README.md                   # This file



