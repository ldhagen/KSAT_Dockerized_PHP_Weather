```

### Updated `README.md`

Below is the revised README content. The primary changes include updating the manual deployment instructions, troubleshooting commands, and monitoring examples to use the `docker compose` syntax.

```markdown
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
curl -fsSL [https://raw.githubusercontent.com/ldhagen/KSAT_Dockerized_PHP_Weather/main/deploy-ksat-weather.sh](https://raw.githubusercontent.com/ldhagen/KSAT_Dockerized_PHP_Weather/main/deploy-ksat-weather.sh) | bash

```

**Or manually (Ensure file separation for security and stability):**

```bash
# Create deployment directory
mkdir ksat-weather && cd ksat-weather

# Download deployment files (docker-compose.yml, init.sql, etc.)
# ... (download all project files here) ...

# ⚠️ New Security Step: Create cron directory and move the fetch script
mkdir cron-scripts
# Ensure cron_fetch_weather.php is moved into the cron-scripts folder
mv cron_fetch_weather.php cron-scripts/

# Start the application
docker compose up -d
Access the dashboard: http://localhost:8085

```

---

## 🌟 Features

### Current Weather

* Real-time weather conditions **read from local MySQL database**.
* Automated data collection exactly **every 5 minutes**.
* Interactive charts and paginated historical archive.
* 
**Secure Architecture:** Background tasks run from non-web-accessible directories.



---

## 🔧 Development

### Local Development

```bash
# Clone the repository
git clone [https://github.com/ldhagen/KSAT_Dockerized_PHP_Weather.git](https://github.com/ldhagen/KSAT_Dockerized_PHP_Weather.git)
cd KSAT_Dockerized_PHP_Weather

# ⚠️ Essential Step for Separation/Security ⚠️
mkdir cron-scripts
mv cron_fetch_weather.php cron-scripts/

# Start development environment
docker compose up -d --build

# Access the application
open http://localhost:8085

```

---

## 🔄 Automated Data Collection

### Monitoring

```bash
# Check cron service logs
docker compose logs -f cron

# Verify data collection
docker compose exec db mysql -u weather_user -pweather_pass weather_db -e "SELECT COUNT(*) as readings, MAX(timestamp) as latest FROM weather_readings;"

```

---

## 🔍 Troubleshooting

| Issue | Potential Solution |
| --- | --- |
| **No data in archive** | `docker compose ps |
| **Database connection issues** | `docker compose exec db mysqladmin ping -h localhost -uweather_user -pweather_pass` |
| **Application not accessible** | `docker compose logs web` <br>

<br> `docker compose ps` |

---

## 📄 License

This project is licensed under the MIT License.

**Version: 2.1.1**
**Last Updated: 2024**
Maintainer: Lance Hagen

```

### Why this change is necessary:
* **Compatibility:** Modern Linux distributions (like Ubuntu 24.04) have deprecated the Python-based `docker-compose` in favor of the Go-based Compose V2 plugin (`docker compose`).
* **Consistency:** Your `deploy-ksat-weather.sh` script has already been updated to use `docker compose`, so the documentation now matches the actual execution commands.
