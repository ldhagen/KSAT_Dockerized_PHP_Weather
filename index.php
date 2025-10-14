<?php
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// San Antonio coordinates (KSAT area)
$latitude = 29.4241;
$longitude = -98.4936;

// NWS API endpoints
$pointsUrl = "https://api.weather.gov/points/{$latitude},{$longitude}";

// Function to fetch data from NWS API
function fetchWeatherData($url, &$error = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'KSAT Weather Dashboard (contact@example.com)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Accept: application/geo+json',
        'Cache-Control: no-cache',
        'Pragma: no-cache'
    ));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        $error = "cURL Error: " . $curlError;
        return null;
    }
    
    if ($httpCode !== 200) {
        $error = "HTTP Error: " . $httpCode;
        return null;
    }
    
    if ($response) {
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        } else {
            $error = "JSON Error: " . json_last_error_msg();
        }
    }
    
    return null;
}

// Get the observation station URL
$errorMsg = '';
$debugInfo = [];
$pointsData = fetchWeatherData($pointsUrl, $errorMsg);
$debugInfo[] = "Points API: " . ($pointsData ? "Success" : "Failed - " . $errorMsg);

$observationUrl = null;
$forecastUrl = null;

if ($pointsData && isset($pointsData['properties'])) {
    $observationStations = $pointsData['properties']['observationStations'] ?? null;
    $forecastUrl = $pointsData['properties']['forecast'] ?? null;
    
    $debugInfo[] = "Observation Stations URL: " . ($observationStations ? "Found" : "Not found");
    $debugInfo[] = "Forecast URL: " . ($forecastUrl ? "Found" : "Not found");
    
    if ($observationStations) {
        $stationsData = fetchWeatherData($observationStations, $errorMsg);
        $debugInfo[] = "Stations Data: " . ($stationsData ? "Success" : "Failed - " . $errorMsg);
        
        if ($stationsData && isset($stationsData['features'][0])) {
            $stationId = $stationsData['features'][0]['properties']['stationIdentifier'];
            $observationUrl = "https://api.weather.gov/stations/{$stationId}/observations/latest";
            $debugInfo[] = "Station ID: " . $stationId;
        }
    }
}

// Fetch current observations
$currentWeather = null;
if ($observationUrl) {
    $currentWeather = fetchWeatherData($observationUrl, $errorMsg);
    $debugInfo[] = "Current Weather: " . ($currentWeather ? "Success" : "Failed - " . $errorMsg);
}

// Fetch forecast
$forecast = null;
if ($forecastUrl) {
    $forecast = fetchWeatherData($forecastUrl, $errorMsg);
    $debugInfo[] = "Forecast: " . ($forecast ? "Success" : "Failed - " . $errorMsg);
}

// Helper function to convert Celsius to Fahrenheit
function celsiusToFahrenheit($celsius) {
    return $celsius ? round(($celsius * 9/5) + 32, 1) : null;
}

// Helper function to format timestamp
function formatTimestamp($timestamp) {
    $dt = new DateTime($timestamp);
    $dt->setTimezone(new DateTimeZone('America/Chicago'));
    return $dt->format('g:i A T');
}

// Helper function to get current local time
function getCurrentLocalTime() {
    $dt = new DateTime('now', new DateTimeZone('America/Chicago'));
    return $dt->format('g:i A T');
}

// Calculate next refresh time for display
$nextRefreshTime = date('g:i A T', time() + 300);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KSAT Weather Dashboard</title>
    <script>
        // Auto-refresh every 5 minutes (300000 milliseconds)
        let secondsLeft = 300;
        
        // Function to reload the page
        function refreshPage() {
            // Add cache-busting parameter to ensure fresh data
            const timestamp = new Date().getTime();
            const url = window.location.href.split('?')[0] + '?t=' + timestamp;
            window.location.href = url;
        }
        
        // Set the main refresh timeout
        setTimeout(refreshPage, 300000);
        
        // Update countdown timer every second
        setInterval(function() {
            secondsLeft--;
            
            const minutes = Math.floor(secondsLeft / 60);
            const seconds = secondsLeft % 60;
            const countdownEl = document.getElementById('countdown');
            if (countdownEl) {
                countdownEl.textContent = minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
            }
            
            // When countdown reaches 0, refresh immediately (backup in case setTimeout fails)
            if (secondsLeft <= 0) {
                refreshPage();
            }
        }, 1000);
    </script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card h3 {
            color: #667eea;
            font-size: 0.9em;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }
        
        .card .value {
            font-size: 2.5em;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }
        
        .card .unit {
            font-size: 0.9em;
            color: #666;
        }
        
        .forecast-section {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .forecast-section h2 {
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .forecast-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .forecast-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }
        
        .forecast-item h4 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .forecast-item p {
            color: #666;
            font-size: 0.9em;
            line-height: 1.5;
        }
        
        .error {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            color: #d32f2f;
        }
        
        .timestamp {
            text-align: center;
            color: white;
            margin-top: 20px;
            font-size: 0.9em;
            opacity: 0.8;
        }
        
        .temp-high { color: #d32f2f; }
        .temp-low { color: #1976d2; }
        
        .refresh-link {
            color: white;
            text-decoration: underline;
            cursor: pointer;
        }
        
        .refresh-link:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🌤️ KSAT Weather Dashboard</h1>
            <p>San Antonio, Texas - National Weather Service Data</p>
        </div>
        
        <?php if ($currentWeather && isset($currentWeather['properties'])): ?>
            <?php $props = $currentWeather['properties']; ?>
            
            <div class="dashboard">
                <div class="card">
                    <h3>Temperature</h3>
                    <div class="value">
                        <?php 
                        $tempF = celsiusToFahrenheit($props['temperature']['value']);
                        echo $tempF ? $tempF : 'N/A';
                        ?>
                    </div>
                    <div class="unit">°F</div>
                </div>
                
                <div class="card">
                    <h3>Humidity</h3>
                    <div class="value">
                        <?php echo $props['relativeHumidity']['value'] ? round($props['relativeHumidity']['value']) : 'N/A'; ?>
                    </div>
                    <div class="unit">%</div>
                </div>
                
                <div class="card">
                    <h3>Wind Speed</h3>
                    <div class="value">
                        <?php 
                        $windSpeed = $props['windSpeed']['value'];
                        // Convert m/s to mph
                        echo $windSpeed ? round($windSpeed * 2.237, 1) : 'N/A';
                        ?>
                    </div>
                    <div class="unit">mph</div>
                </div>
                
                <div class="card">
                    <h3>Wind Direction</h3>
                    <div class="value" style="font-size: 1.8em;">
                        <?php 
                        $windDir = $props['windDirection']['value'];
                        if ($windDir !== null) {
                            $directions = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
                            $index = round($windDir / 22.5) % 16;
                            echo $directions[$index];
                        } else {
                            echo 'N/A';
                        }
                        ?>
                    </div>
                    <div class="unit"><?php echo $windDir ? round($windDir) . '°' : ''; ?></div>
                </div>
                
                <div class="card">
                    <h3>Barometric Pressure</h3>
                    <div class="value">
                        <?php 
                        $pressure = $props['barometricPressure']['value'];
                        // Convert Pa to inHg
                        echo $pressure ? round($pressure * 0.0002953, 2) : 'N/A';
                        ?>
                    </div>
                    <div class="unit">inHg</div>
                </div>
                
                <div class="card">
                    <h3>Dew Point</h3>
                    <div class="value">
                        <?php 
                        $dewF = celsiusToFahrenheit($props['dewpoint']['value']);
                        echo $dewF ? $dewF : 'N/A';
                        ?>
                    </div>
                    <div class="unit">°F</div>
                </div>
                
                <div class="card">
                    <h3>Visibility</h3>
                    <div class="value">
                        <?php 
                        $visibility = $props['visibility']['value'];
                        // Convert meters to miles
                        echo $visibility ? round($visibility * 0.000621371, 1) : 'N/A';
                        ?>
                    </div>
                    <div class="unit">miles</div>
                </div>
                
                <div class="card">
                    <h3>Conditions</h3>
                    <div class="value" style="font-size: 1.5em;">
                        <?php echo $props['textDescription'] ?? 'N/A'; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($forecast && isset($forecast['properties']['periods'])): ?>
                <div class="forecast-section">
                    <h2>7-Day Forecast</h2>
                    <div class="forecast-grid">
                        <?php foreach (array_slice($forecast['properties']['periods'], 0, 7) as $period): ?>
                            <div class="forecast-item">
                                <h4><?php echo htmlspecialchars($period['name']); ?></h4>
                                <p>
                                    <strong class="temp-high"><?php echo $period['temperature']; ?>°<?php echo $period['temperatureUnit']; ?></strong>
                                </p>
                                <p><?php echo htmlspecialchars($period['shortForecast']); ?></p>
                                <p style="margin-top: 10px; font-size: 0.85em;">
                                    💨 <?php echo htmlspecialchars($period['windSpeed'] . ' ' . $period['windDirection']); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="timestamp">
                Station Updated: <?php echo formatTimestamp($props['timestamp']); ?> | 
                Page Loaded: <?php echo date('g:i A T'); ?> | 
                Next Refresh: <span id="countdown">5:00</span> (<?php echo $nextRefreshTime; ?>) | 
                <span class="refresh-link" onclick="refreshPage()">Refresh Now</span>
            </div>
            
        <?php else: ?>
            <div class="error">
                <h2>⚠️ Unable to fetch weather data</h2>
                <p>Please check your internet connection and try again.</p>
                <?php if ($errorMsg): ?>
                    <p style="margin-top: 10px; font-size: 0.9em; color: #666;">
                        Error details: <?php echo htmlspecialchars($errorMsg); ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($debugInfo)): ?>
                    <div style="margin-top: 15px; text-align: left; background: #f5f5f5; padding: 15px; border-radius: 5px; font-size: 0.85em; color: #333;">
                        <strong>Debug Info:</strong><br>
                        <?php foreach ($debugInfo as $info): ?>
                            <?php echo htmlspecialchars($info); ?><br>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <p style="margin-top: 10px;">
                    <span class="refresh-link" onclick="location.reload()">Refresh Page</span>
                </p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>