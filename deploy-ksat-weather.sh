#!/bin/bash
set -euo pipefail

echo "🚀 Deploying KSAT Weather Dashboard from Docker Hub..."

# Create deployment directory
mkdir -p ksat-weather
cd ksat-weather

# Download docker-compose.yml
curl -fsSL -o docker-compose.yml \
  https://raw.githubusercontent.com/ldhagen/KSAT_Dockerized_PHP_Weather/main/docker-compose.yml

# Download init.sql
curl -fsSL -o init.sql \
  https://raw.githubusercontent.com/ldhagen/KSAT_Dockerized_PHP_Weather/main/init.sql

echo "Pulling latest images from Docker Hub..."
docker compose pull

echo "Starting services..."
docker compose up -d

echo "✅ Deployment complete!"
echo ""
echo "🌐 Access your weather dashboard at: http://localhost:8085"
echo "📊 Charts: http://localhost:8085/charts.php"
echo "📁 Archive: http://localhost:8085/archive.php"
echo ""
echo "📋 Useful commands:"
echo "   View logs: docker compose logs -f"
echo "   Check status: docker compose ps"
echo "   Stop: docker compose down"
echo ""
echo "⏰ Weather data will be automatically collected every 5 minutes"

