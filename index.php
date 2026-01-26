<?php
// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Include utility functions and Database class (which are now in config.php)
// The original file had these redefined, now we rely on config.php
require_once 'config.php';

// San Antonio coordinates (KSAT area)
$latitude = 29.4241;
$longitude = -98.4936;

// NWS API endpoints - **These are no longer used for fetching current data, but kept for context/future expansion**
$pointsUrl = "https://api.weather.gov/points/{$latitude},{$longitude}";


// ======================================================================
// NEW FUNCTIONS FOR READING FROM LOCAL DATABASE
// ======================================================================

/**
 * Fetches the single latest weather reading from the local database.
 * This replaces the direct NWS API call on page load.
 */
function getLatestReading() {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        error_log("Database connection failed when reading latest record.");
        return null;
    }
    
    try {
        // Select only the single most recent record
        $query = "SELECT * FROM weather_readings ORDER BY timestamp DESC LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching latest record: " . $e->getMessage());
        return null;
    }
}

/**
 * ONLY USED FOR FORECAST. This remains to fetch the 7-day forecast, which 
 * is less volatile than observations and does not archive. You may still 
 * want to move this to the cron job to be a pure local-only dashboard.
 */
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
// ======================================================================
// END OF NEW/MODIFIED FUNCTIONS
// ======================================================================


// Archive function REMOVED - ONLY the cron script should archive

// Get the observation station URL - **REMOVED OBSERVATION FETCH LOGIC**
$errorMsg = '';
$debugInfo = [];
$debugInfo[] = "Script started at: " . date('Y-m-d H:i:s T');
$debugInfo[] = "Coordinates: {$latitude}, {$longitude}";
$debugInfo[] = "Current Data Source: Local Database (Updated by cron job every 15 minutes)";

// Step 1: Get latest reading from local DB
$latestReading = getLatestReading();

if ($latestReading) {
    $debugInfo[] = "Local DB Reading: Success. Timestamp: " . $latestReading['timestamp'];
} else {
    $debugInfo[] = "Local DB Reading: Failed or no records found.";
    $errorMsg = "No recent weather data in local database. Check cron job status.";
}


// Step 2: Fetch the forecast (still making an external call, but less frequent)
$forecastUrl = null;
$pointsData = fetchWeatherData($pointsUrl, $errorMsg);

if ($pointsData && isset($pointsData['properties'])) {
    $forecastUrl = $pointsData['properties']['forecast'] ?? null;
    $debugInfo[] = "Forecast URL: " . ($forecastUrl ? "Found" : "Not found");
}

$forecast = null;
if ($forecastUrl) {
    // Note: The NWS Forecast is usually updated hourly or less, so this is an acceptable 
    // fetch on page load, but it is separate from the observation data frequency issue.
    $forecast = fetchWeatherData($forecastUrl, $errorMsg);
    $debugInfo[] = "Forecast API: " . ($forecast ? "Success" : "Failed - " . $errorMsg);
}


// Calculate next refresh time for display
// We are trusting the cron job to update every 15 minutes (900 seconds)
$nextRefreshTime = date('g:i A T', time() + 900);

// Get version information
$VERSION = @file_get_contents('version.txt') ?: '2.1.0';
$VERSION = trim($VERSION);

// Process current weather data from local database
$weatherData = [];
if ($latestReading) {
    
    $weatherData = [
        'temperature' => $latestReading['temperature'],
        'humidity' => $latestReading['humidity'],
        'windSpeed' => $latestReading['wind_speed'],
        'windDirection' => $latestReading['wind_direction'],
        // Use helper function from config.php
        'windDirectionCardinal' => getWindDirectionCardinal($latestReading['wind_direction']),
        'pressure' => $latestReading['pressure'],
        'dewPoint' => $latestReading['dew_point'],
        'visibility' => $latestReading['visibility'],
        'conditions' => $latestReading['conditions'],
        'timestamp' => $latestReading['timestamp'] // This is the local DB record timestamp
    ];

    $debugInfo[] = "=== FINAL WEATHER DATA (FROM LOCAL DB) ===";
    $debugInfo[] = "Final Wind Speed: " . ($weatherData['windSpeed'] ?? 'N/A') . " mph";
    $debugInfo[] = "Final Wind Direction: " . ($weatherData['windDirectionCardinal'] ?? 'N/A');
    $debugInfo[] = "Temperature: " . ($weatherData['temperature'] ?? 'N/A') . " °F";
    $debugInfo[] = "Humidity: " . ($weatherData['humidity'] ?? 'N/A') . " %";
    $debugInfo[] = "Data Timestamp: " . formatTimestamp($latestReading['timestamp']);

} else {
    $debugInfo[] = "No local weather data available for processing";
}

// **ARCHIVE STEP REMOVED - THIS IS THE KEY FIX**

// Get Git commit information for version display
$GIT_COMMIT = $_ENV['GIT_COMMIT'] ?? 
              (function() {
                  $commit = @shell_exec('git rev-parse --short HEAD');
                  return $commit ? trim($commit) : null;
              })();

// Determine GitHub URL and display text
if ($GIT_COMMIT && preg_match('/^[a-f0-9]{7,40}$/i', $GIT_COMMIT)) {
    // Valid commit hash - link to specific commit
    $githubUrl = "https://github.com/ldhagen/KSAT_Dockerized_PHP_Weather/commit/{$GIT_COMMIT}";
    $buildText = substr($GIT_COMMIT, 0, 7);
} else {
    // No valid commit - link to main branch
    $githubUrl = "https://github.com/ldhagen/KSAT_Dockerized_PHP_Weather";
    $buildText = 'main';
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
        // Auto-refresh every 15 minutes (900000 milliseconds)
        let secondsLeft = 900;
        
        // Function to reload the page
        function refreshPage() {
            const timestamp = new Date().getTime();
            // Appending a timestamp query parameter to defeat browser caching
            const url = window.location.href.split('?')[0] + '?t=' + timestamp;
            window.location.href = url;
        }
        
        // Set the main refresh timeout
        // This causes the browser to refresh, which is still desirable for a dashboard.
        setTimeout(refreshPage, 900000);
        
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
        /* ... CSS STYLES REMAINS UNCHANGED ... */
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
                Data Updated: <?php echo formatTimestamp($weatherData['timestamp']); ?> | 
                Page Loaded: <?php echo date('g:i A T'); ?> | 
                Next Page Refresh: <span id="countdown">15:00</span> (<?php echo $nextRefreshTime; ?>) | 
                <span class="refresh-link" onclick="refreshPage()">Refresh Now</span>
            </div>
            
        <?php else: ?>
            <div class="error">
                <h2>⚠️ Unable to display weather data</h2>
                <p>No recent weather data available in the local database.</p>
                <p>Please ensure your background cron job is running correctly every 15 minutes and archiving data.</p>
                <?php if ($errorMsg): ?>
                    <p style="margin-top: 10px; font-size: 0.9em; color: #666;">
                        Forecast Error details: <?php echo htmlspecialchars($errorMsg); ?>
                    </p>
                <?php endif; ?>
                <p style="margin-top: 10px;">
                    <span class="refresh-link" onclick="location.reload()">Refresh Page</span>
                </p>
            </div>
        <?php endif; ?>
        
        <div class="debug-panel">
            <h3 style="margin-bottom: 15px; color: #ecf0f1;">Debug Information</h3>
            <?php foreach ($debugInfo as $info): ?>
                <div style="margin-bottom: 5px; padding: 3px 0; border-bottom: 1px solid #34495e;">
                    <?php echo htmlspecialchars($info); ?>
                </div>
            <?php endforeach; ?>
            
            <?php 
            // Removed raw wind data structure debug since we are no longer fetching raw NWS data here.
            // Keeping the toggle script below.
            ?>
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
                // Insert the toggle button before the debug panel
                debugPanel.parentNode.insertBefore(toggleBtn, debugPanel);
                // Hide by default to keep the page clean
                debugPanel.style.display = 'none'; 
            }
        });
    </script>
</body>
</html>
<div class="version-info" style="text-align: center; margin-top: 20px; color: #7f8c8d; font-size: 0.8em;">
    v<?php echo $VERSION; ?> | 
    <a href="<?php echo $githubUrl; ?>" 
       target="_blank" style="color: #7f8c8d;">
       Build: <?php echo $buildText; ?>
    </a>
</div>
