````markdown
# KSAT Dockerized PHP Weather Dashboard

![Docker Build Status](https://github.com/ldhagen/KSAT_Dockerized_PHP_Weather/actions/workflows/docker.yml/badge.svg)
![Docker Pulls](https://img.shields.io/docker/pulls/ldhagen/ksat-weather-app)
![Docker Image Version](https://img.shields.io/docker/v/ldhagen/ksat-weather-app)
![GitHub](https://img.shields.io/github/license/ldhagen/KSAT_Dockerized_PHP_Weather)

A comprehensive weather monitoring and visualization system built with PHP, MySQL, and Docker. This application provides near real-time weather data, historical analysis, and interactive charts for San Antonio, Texas using the National Weather Service API.

---

## 🚀 Quick Deployment

### Deploy with Docker (Recommended)

```bash
# One-command deployment
curl -fsSL [https://raw.githubusercontent.com/ldhagen/KSAT_Dockerized_PHP_Weather/main/deploy.sh](https://raw.githubusercontent.com/ldhagen/KSAT_Dockerized_PHP_Weather/main/deploy.sh) | bash
````

**Or manually (Ensure file separation for security and stability):**

```bash
# Create deployment directory
mkdir ksat-weather && cd ksat-weather

# Download deployment files (docker-compose.yml, init.sql, etc.)
# ... (download all project files here) ...

# ⚠️ New Security Step: Create cron directory and move the fetch script
mkdir cron-scripts
mv cron_fetch_weather.php cron-scripts/

# Start the application
docker-compose up -d
Access the dashboard: http://localhost:8085
```

-----

## 🌟 Features

### Current Weather

  * Real-time weather conditions **read from local MySQL database** (updated by the cron job).
  * Temperature, humidity, wind speed/direction, pressure, dew point, and visibility.
  * 7-day weather forecast.
  * Auto-refresh every 5 minutes in browser.

### Automated Data Collection

  * Continuous automated storage of all weather readings.
  * **Frequency:** Exactly **Every 5 minutes**, regardless of web traffic, ensuring API rate limit compliance.
  * **Data Integrity:** No gaps in historical records.

### Data Archiving

  * Paginated archive view with date filtering.
  * Export-ready data structure.

### Interactive Charts

  * Multiple chart types (line, combo, time series).
  * Temperature, humidity, wind speed, pressure trends.

### Technical Features

  * Docker containerization with multi-service architecture.
  * **Secure Cron Job:** Data collection script (`cron_fetch_weather.php`) runs from a **non-web-accessible directory** (`/app`) within the container.
  * MySQL database persistence.
  * Automated cron-based data collection.
  * Error handling and logging.

-----

## 📦 Docker Images

| Service | Image | Description |
| :--- | :--- | :--- |
| Web App | `ldhagen/ksat-weather-app:latest` | PHP/Apache web application serving `index.php` and `archive.php`. |
| Database | `mysql:8.0` | MySQL database with persistent storage. |
| Cron | `ldhagen/ksat-weather-app:latest` | Automated data collection **isolated from web traffic**. |

-----

## 🔧 Development

### Prerequisites

  * Docker and Docker Compose
  * Git

### Local Development

```bash
# Clone the repository
git clone [https://github.com/ldhagen/KSAT_Dockerized_PHP_Weather.git](https://github.com/ldhagen/KSAT_Dockerized_PHP_Weather.git)
cd KSAT_Dockerized_PHP_Weather

# ⚠️ Essential Step for Separation/Security ⚠️
# The cron script MUST be moved out of the web root
mkdir cron-scripts
mv cron_fetch_weather.php cron-scripts/

# Start development environment
docker-compose up -d --build

# Access the application
open http://localhost:8085
```

### Project Structure

```text
KSAT_Dockerized_PHP_Weather/
├── .github/workflows/          # CI/CD pipelines
├── docker-compose.yml          # Multi-service configuration (includes secure cron mount)
├── Dockerfile                  # PHP/Apache container setup
├── init.sql                    # Database schema
├── config.php                  # Database configuration (kept in web root for web scripts)
├── index.php                   # Main dashboard (only reads from local DB)
├── charts.php                  # Interactive charts
├── archive.php                 # Historical data
├── cron-scripts/               # 🆕 NEW: Secure directory for background tasks
│   └── cron_fetch_weather.php  # Automated data collection (NO LONGER web-accessible)
└── README.md                   # This file
```

-----

## 🔄 Automated Data Collection

The system features continuous data collection that runs independently of user visits:

  * **Frequency:** Every 5 minutes.
  * **Method:** Dockerized cron service using a secure, non-web path.

### Monitoring

```bash
# Check cron service logs (shows execution output due to improved logging)
docker-compose logs -f cron

# Verify data collection
docker-compose exec db mysql -u weather_user -pweather_pass weather_db -e "SELECT COUNT(*) as readings, MAX(timestamp) as latest FROM weather_readings;"
```

-----

## 🔍 Troubleshooting

### Common Issues

| Issue | Potential Solution |
| :--- | :--- |
| **Duplicate Archive Entries** (Multiple records per minute) | Check that `cron_fetch_weather.php` has been **moved** to the secure `cron-scripts/` directory and is not in the web root. |
| **`cron` container immediately exits** | Ensure the `docker-compose.yml` uses `exec cron -f` in the `cron` service's command block to keep the process running. |
| **No data in archive** | `# Check if cron service is running`<br>`docker-compose ps | grep cron`<br>`# Check cron logs`<br>`docker-compose logs cron` |
| **PHP function redeclaration errors** | Ensure all utility functions (e.g., `formatTimestamp()`) are only defined once in `config.php` and removed from `index.php` and `archive.php`. |
| **Database connection issues** | `# Check database health`<br>`docker-compose exec db mysqladmin ping -h localhost -uweather_user -pweather_pass` |
| **Application not accessible** | `# Check web service logs`<br>`docker-compose logs web`<br>`# Verify port mapping`<br>`docker-compose ps` |

-----

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

**Version: 2.1.0** (New stable version reflecting architecture fixes)
**Last Updated: 18 Oct 2025**
Maintainer: Lance Hagen

For support or questions, please check the troubleshooting section or open an issue in the repository.

```
