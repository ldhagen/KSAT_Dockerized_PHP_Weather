#!/bin/bash
set -e

# Start the cron service in the background
service cron start

# Run the weather script once immediately so the dashboard isn't empty on first load
echo "Running initial weather fetch..."
php /var/www/html/cron_fetch_weather.php

# Start Apache in the foreground (Standard Docker behavior)
exec apache2-foreground
