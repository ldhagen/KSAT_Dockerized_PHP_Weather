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

// Database configuration
class Database {
    private $host = 'db';
    private $db_name = 'weather_db';
    private $username = 'weather_user';
    private $password = 'weather_pass';
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name, 
                $this->username, 
                $this->password
            );
            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}

// Archive current reading to database
function archiveWeatherData($weatherData, $conditions) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db) {
            $query = "INSERT INTO weather_readings 
                     (temperature, humidity, wind_speed, wind_direction, pressure, dew_point, visibility, conditions) 
                     VALUES (:temp, :humidity, :wind_speed, :wind_dir, :pressure, :dew_point, :visibility, :conditions)";
            
            $stmt = $db->prepare($query);
            
            $stmt->bindParam(':temp', $weatherData['temperature']);
            $stmt->bindParam(':humidity', $weatherData['humidity']);
            $stmt->bindParam(':wind_speed', $weatherData['windSpeed']);
            $stmt->bindParam(':wind_dir', $weatherData['windDirection']);
            $stmt->bindParam(':pressure', $weatherData['pressure']);
            $stmt->bindParam(':dew_point', $weatherData['dewPoint']);
            $stmt->bindParam(':visibility', $weatherData['visibility']);
            $stmt->bindParam(':conditions', $conditions);
            
            $stmt->execute();
            return true;
        }
    } catch (Exception $e) {
        error_log("Archive error: " . $e->getMessage());
    }
    return false;
}

// Get the observation station URL
$errorMsg = '';
$debugInfo = [];
$debugInfo[] = "Script started at: " . date('Y-m-d H:i:s T');
$debugInfo[] = "Coordinates: {$latitude}, {$longitude}";

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
            $debugInfo[] = "Observation URL: " . $observationUrl;
        } else {
            $debugInfo[] = "No station features found in stations data";
            if ($stationsData) {
                $debugInfo[] = "Available stations: " . count($stationsData['features'] ?? []);
            }
        }
    }
} else {
    $debugInfo[] = "No points data properties found";
}

// Fetch current observations
$currentWeather = null;
if ($observationUrl) {
    $currentWeather = fetchWeatherData($observationUrl, $errorMsg);
    $debugInfo[] = "Current Weather: " . ($currentWeather ? "Success" : "Failed - " . $errorMsg);
    
    // Debug: Show raw wind data structure
    if ($currentWeather && isset($currentWeather['properties'])) {
        $props = $currentWeather['properties'];
        $debugInfo[] = "Raw Wind Speed Structure: " . json_encode($props['windSpeed'] ?? 'No wind speed data');
        $debugInfo[] = "Raw Wind Direction Structure: " . json_encode($props['windDirection'] ?? 'No wind direction data');
        $debugInfo[] = "Raw Wind Gust Structure: " . json_encode($props['windGust'] ?? 'No wind gust data');
        
        // Check wind speed units in API response
        if (isset($props['windSpeed'])) {
            $debugInfo[] = "Wind Speed Unit Code: " . ($props['windSpeed']['unitCode'] ?? 'Not specified');
        }
        
        // Show sample of available properties for debugging
        $availableProps = array_keys($props);
        $debugInfo[] = "Available Properties: " . implode(', ', array_slice($availableProps, 0, 10)) . (count($availableProps) > 10 ? '...' : '');
    }
} else {
    $debugInfo[] = "No observation URL available";
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

// Process current weather data
$weatherData = [];
if ($currentWeather && isset($currentWeather['properties'])) {
    $props = $currentWeather['properties'];
    
    // Enhanced debug wind data
    $debugInfo[] = "=== WIND DATA DEBUG ===";
    $debugInfo[] = "Raw Wind Speed Value: " . (isset($props['windSpeed']['value']) ? $props['windSpeed']['value'] : 'NULL');
    $debugInfo[] = "Raw Wind Direction Value: " . (isset($props['windDirection']['value']) ? $props['windDirection']['value'] : 'NULL');
    $debugInfo[] = "Raw Wind Gust Value: " . (isset($props['windGust']['value']) ? $props['windGust']['value'] : 'NULL');
    $debugInfo[] = "Wind Speed Type: " . (isset($props['windSpeed']['value']) ? gettype($props['windSpeed']['value']) : 'NULL');

    $weatherData = [
        'temperature' => celsiusToFahrenheit($props['temperature']['value']),
        'humidity' => $props['relativeHumidity']['value'] ? round($props['relativeHumidity']['value']) : null,
        'windSpeed' => 0.0, // Default value
        'windDirection' => $props['windDirection']['value'] ?? null,
        'windDirectionCardinal' => 'N/A',
        'pressure' => $props['barometricPressure']['value'] ? round($props['barometricPressure']['value'] * 0.0002953, 2) : null,
        'dewPoint' => celsiusToFahrenheit($props['dewpoint']['value']),
        'visibility' => $props['visibility']['value'] ? round($props['visibility']['value'] * 0.000621371, 1) : null,
        'conditions' => $props['textDescription'] ?? null,
        'timestamp' => $props['timestamp'] ?? null
    ];

    // CORRECTED WIND SPEED CALCULATION - Assume km/h for NWS API
    if (isset($props['windSpeed']['value']) && $props['windSpeed']['value'] !== null) {
        $windSpeedKmh = $props['windSpeed']['value'];
        // Convert km/h to mph: 1 km/h = 0.621371 mph
        $weatherData['windSpeed'] = round($windSpeedKmh * 0.621371, 1);
        $debugInfo[] = "Wind Speed Conversion: {$windSpeedKmh} km/h × 0.621371 = {$weatherData['windSpeed']} mph";
    } elseif (isset($props['windGust']['value']) && $props['windGust']['value'] !== null) {
        // Fallback to wind gust if available
        $windGustKmh = $props['windGust']['value'];
        $weatherData['windSpeed'] = round($windGustKmh * 0.621371, 1);
        $debugInfo[] = "Using wind gust as fallback: {$windGustKmh} km/h = {$weatherData['windSpeed']} mph";
    } else {
        $weatherData['windSpeed'] = 0.0;
        $debugInfo[] = "No wind data available, defaulting to 0.0 mph";
    }

    // Calculate wind direction cardinal
    if ($weatherData['windDirection'] !== null) {
        $directions = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
        $index = round($weatherData['windDirection'] / 22.5) % 16;
        $weatherData['windDirectionCardinal'] = $directions[$index];
        $debugInfo[] = "Wind direction: {$weatherData['windDirection']}° = {$weatherData['windDirectionCardinal']}";
    } else {
        $weatherData['windDirectionCardinal'] = 'N/A';
        $weatherData['windDirection'] = null;
        $debugInfo[] = "No wind direction data available";
    }
    
    $debugInfo[] = "=== FINAL WEATHER DATA ===";
    $debugInfo[] = "Final Wind Speed: " . $weatherData['windSpeed'] . " mph";
    $debugInfo[] = "Final Wind Direction: " . ($weatherData['windDirectionCardinal'] ?? 'N/A');
    $debugInfo[] = "Temperature: " . ($weatherData['temperature'] ?? 'N/A') . " °F";
    $debugInfo[] = "Humidity: " . ($weatherData['humidity'] ?? 'N/A') . " %";
    
    // Archive the current reading if we have valid data
    if ($weatherData['temperature'] !== null) {
        $conditions = $weatherData['conditions'] ?? 'Unknown';
        $archiveResult = archiveWeatherData($weatherData, $conditions);
        $debugInfo[] = "Archive: " . ($archiveResult ? "Success" : "Failed");
    }
} else {
    $debugInfo[] = "No current weather data available for processing";
}

$debugInfo[] = "Script completed at: " . date('Y-m-d H:i:s T');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ASAI Weather Dashboard</title>
    <script>
        // Auto-refresh every 5 minutes (300000 milliseconds)
        let secondsLeft = 300;
        
        // Function to reload the page
        function refreshPage() {
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
            background: white;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 20px;
        }
        
        .header h1 {
            font-size: 2.2em;
            color: #2c3e50;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .header p {
            font-size: 1.1em;
            color: #7f8c8d;
        }
        
        .navigation {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .nav-link {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .nav-link:hover {
            background: #2980b9;
        }
        
        .nav-link.current {
            background: #2c3e50;
        }
        
        .current-conditions {
            margin-bottom: 30px;
        }
        
        .conditions-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1px;
            background: #e0e0e0;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .condition-item {
            background: white;
            padding: 20px;
            text-align: center;
        }
        
        .condition-label {
            font-size: 0.85em;
            font-weight: 600;
            color: #7f8c8d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        
        .condition-value {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .condition-unit {
            font-size: 0.9em;
            color: #7f8c8d;
        }
        
        .wind-direction {
            font-size: 1.4em;
            margin-bottom: 5px;
        }
        
        .secondary-conditions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        
        .secondary-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .secondary-label {
            font-size: 0.9em;
            font-weight: 600;
            color: #7f8c8d;
            margin-bottom: 8px;
        }
        
        .secondary-value {
            font-size: 1.6em;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .secondary-unit {
            font-size: 0.8em;
            color: #7f8c8d;
            margin-left: 2px;
        }
        
        .divider {
            height: 1px;
            background: #e0e0e0;
            margin: 30px 0;
        }
        
        .forecast-section {
            margin-bottom: 30px;
        }
        
        .forecast-section h2 {
            font-size: 1.4em;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .forecast-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 15px;
        }
        
        .forecast-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        
        .forecast-day {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 0.9em;
        }
        
        .forecast-temp {
            font-size: 1.3em;
            font-weight: bold;
            color: #e74c3c;
            margin-bottom: 8px;
        }
        
        .forecast-condition {
            font-size: 0.85em;
            color: #7f8c8d;
            margin-bottom: 8px;
            min-height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .forecast-wind {
            font-size: 0.8em;
            color: #7f8c8d;
        }
        
        .timestamp {
            text-align: center;
            color: #7f8c8d;
            font-size: 0.85em;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .refresh-link {
            color: #3498db;
            text-decoration: underline;
            cursor: pointer;
        }
        
        .refresh-link:hover {
            color: #2980b9;
        }
        
        .error {
            background: #ffeaa7;
            border: 1px solid #fdcb6e;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            color: #e17055;
        }
        
        .debug-panel {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .debug-toggle {
            background: #34495e;
            color: #ecf0f1;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            font-size: 0.9em;
        }
        
        .debug-toggle:hover {
            background: #3d566e;
        }
        
        @media (max-width: 768px) {
            .conditions-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .secondary-conditions {
                grid-template-columns: 1fr;
            }
            
            .forecast-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .nav-link {
                display: block;
                margin: 5px 0;
            }
        }
        
        @media (max-width: 480px) {
            .conditions-grid {
                grid-template-columns: 1fr;
            }
            
            .forecast-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ASAI Weather Dashboard</h1>
            <p>San Antonio, Texas - National Weather Service Data</p>
        </div>
        
        <div class="navigation">
            <a href="index.php" class="nav-link current">Current Weather</a>
            <a href="charts.php" class="nav-link">Weather Charts</a>
            <a href="archive.php" class="nav-link">Data Archive</a>
        </div>
        
        <?php if (!empty($weatherData)): ?>
            <div class="current-conditions">
                <div class="conditions-grid">
                    <div class="condition-item">
                        <div class="condition-label">Temperature</div>
                        <div class="condition-value">
                            <?php echo $weatherData['temperature'] ?? 'N/A'; ?>
                        </div>
                        <div class="condition-unit">°F</div>
                    </div>
                    
                    <div class="condition-item">
                        <div class="condition-label">Humidity</div>
                        <div class="condition-value">
                            <?php echo $weatherData['humidity'] ?? 'N/A'; ?>
                        </div>
                        <div class="condition-unit">%</div>
                    </div>
                    
                    <div class="condition-item">
                        <div class="condition-label">Wind Speed</div>
                        <div class="condition-value">
                            <?php echo $weatherData['windSpeed'] ?? '0.0'; ?>
                        </div>
                        <div class="condition-unit">mph</div>
                    </div>
                    
                    <div class="condition-item">
                        <div class="condition-label">Wind Direction</div>
                        <div class="wind-direction">
                            <?php echo $weatherData['windDirectionCardinal'] ?? 'N/A'; ?>
                        </div>
                        <div class="condition-unit">
                            <?php echo $weatherData['windDirection'] ? round($weatherData['windDirection']) . '°' : ''; ?>
                        </div>
                    </div>
                </div>
                
                <div class="secondary-conditions">
                    <div class="secondary-item">
                        <div class="secondary-label">Barometric Pressure</div>
                        <div class="secondary-value">
                            <?php echo $weatherData['pressure'] ?? 'N/A'; ?>
                            <span class="secondary-unit">inHg</span>
                        </div>
                    </div>
                    
                    <div class="secondary-item">
                        <div class="secondary-label">Dew Point</div>
                        <div class="secondary-value">
                            <?php echo $weatherData['dewPoint'] ?? 'N/A'; ?>
                            <span class="secondary-unit">°F</span>
                        </div>
                    </div>
                    
                    <div class="secondary-item">
                        <div class="secondary-label">Visibility</div>
                        <div class="secondary-value">
                            <?php echo $weatherData['visibility'] ?? 'N/A'; ?>
                            <span class="secondary-unit">miles</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="divider"></div>
            
            <?php if ($forecast && isset($forecast['properties']['periods'])): ?>
                <div class="forecast-section">
                    <h2>7-Day Forecast</h2>
                    <div class="forecast-grid">
                        <?php foreach (array_slice($forecast['properties']['periods'], 0, 7) as $period): ?>
                            <div class="forecast-item">
                                <div class="forecast-day"><?php echo htmlspecialchars($period['name']); ?></div>
                                <div class="forecast-temp"><?php echo $period['temperature']; ?>°<?php echo $period['temperatureUnit']; ?></div>
                                <div class="forecast-condition"><?php echo htmlspecialchars($period['shortForecast']); ?></div>
                                <div class="forecast-wind">💨 <?php echo htmlspecialchars($period['windSpeed'] . ' ' . $period['windDirection']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="timestamp">
                Station Updated: <?php echo formatTimestamp($weatherData['timestamp']); ?> | 
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
                <p style="margin-top: 10px;">
                    <span class="refresh-link" onclick="location.reload()">Refresh Page</span>
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Debug Panel -->
        <div class="debug-panel">
            <h3 style="margin-bottom: 15px; color: #ecf0f1;">Debug Information</h3>
            <?php foreach ($debugInfo as $info): ?>
                <div style="margin-bottom: 5px; padding: 3px 0; border-bottom: 1px solid #34495e;">
                    <?php echo htmlspecialchars($info); ?>
                </div>
            <?php endforeach; ?>
            
            <?php if ($currentWeather && isset($currentWeather['properties'])): ?>
                <div style="margin-top: 15px; padding: 10px; background: #34495e; border-radius: 5px;">
                    <strong>Raw Wind Data Structure:</strong><br>
                    <pre style="font-size: 0.8em; overflow-x: auto;"><?php echo htmlspecialchars(json_encode($currentWeather['properties']['windSpeed'] ?? 'No wind speed data', JSON_PRETTY_PRINT)); ?></pre>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
    
    <script>
        // Toggle debug panel visibility
        function toggleDebug() {
            const debugPanel = document.querySelector('.debug-panel');
            if (debugPanel) {
                debugPanel.style.display = debugPanel.style.display === 'none' ? 'block' : 'none';
            }
        }
        
        // Add toggle button
        document.addEventListener('DOMContentLoaded', function() {
            const debugPanel = document.querySelector('.debug-panel');
            if (debugPanel) {
                const toggleBtn = document.createElement('button');
                toggleBtn.textContent = 'Toggle Debug Info';
                toggleBtn.className = 'debug-toggle';
                toggleBtn.onclick = toggleDebug;
                debugPanel.parentNode.insertBefore(toggleBtn, debugPanel);
            }
        });
    </script>
</body>
</html>
<div class="version-info" style="text-align: center; margin-top: 20px; color: #7f8c8d; font-size: 0.8em;">
    v2.0.0 | 
    <a href="https://github.com/ldhagen/KSAT_Dockerized_PHP_Weather/commit/<?php echo $GIT_COMMIT; ?>" 
       target="_blank" style="color: #7f8c8d;">
       Build: <?php echo substr($GIT_COMMIT, 0, 7); ?>
    </a>
</div>
