<?php
// cron_fetch_weather.php
require_once 'config.php';

// Your existing weather fetching logic from index.php
$latitude = 29.4241;
$longitude = -98.4936;
$pointsUrl = "https://api.weather.gov/points/{$latitude},{$longitude}";

function fetchWeatherData($url, &$error = null) {
    // Your existing fetchWeatherData function
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

function archiveWeatherData($weatherData, $conditions) {
    // Your existing archiveWeatherData function
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

// Main execution
$pointsData = fetchWeatherData($pointsUrl, $errorMsg);

if ($pointsData && isset($pointsData['properties'])) {
    $observationStations = $pointsData['properties']['observationStations'] ?? null;
    
    if ($observationStations) {
        $stationsData = fetchWeatherData($observationStations, $errorMsg);
        
        if ($stationsData && isset($stationsData['features'][0])) {
            $stationId = $stationsData['features'][0]['properties']['stationIdentifier'];
            $observationUrl = "https://api.weather.gov/stations/{$stationId}/observations/latest";
            
            $currentWeather = fetchWeatherData($observationUrl, $errorMsg);
            
            if ($currentWeather && isset($currentWeather['properties'])) {
                $props = $currentWeather['properties'];
                
                $weatherData = [
                    'temperature' => celsiusToFahrenheit($props['temperature']['value']),
                    'humidity' => $props['relativeHumidity']['value'] ? round($props['relativeHumidity']['value']) : null,
                    'windSpeed' => 0.0,
                    'windDirection' => $props['windDirection']['value'] ?? null,
                    'windDirectionCardinal' => 'N/A',
                    'pressure' => $props['barometricPressure']['value'] ? round($props['barometricPressure']['value'] * 0.0002953, 2) : null,
                    'dewPoint' => celsiusToFahrenheit($props['dewpoint']['value']),
                    'visibility' => $props['visibility']['value'] ? round($props['visibility']['value'] * 0.000621371, 1) : null,
                    'conditions' => $props['textDescription'] ?? null,
                    'timestamp' => $props['timestamp'] ?? null
                ];

                // Wind speed calculation
                if (isset($props['windSpeed']['value']) && $props['windSpeed']['value'] !== null) {
                    $windSpeedKmh = $props['windSpeed']['value'];
                    $weatherData['windSpeed'] = round($windSpeedKmh * 0.621371, 1);
                }

                // Wind direction
                if ($weatherData['windDirection'] !== null) {
                    $directions = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
                    $index = round($weatherData['windDirection'] / 22.5) % 16;
                    $weatherData['windDirectionCardinal'] = $directions[$index];
                }

                // Archive data
                if ($weatherData['temperature'] !== null) {
                    $conditions = $weatherData['conditions'] ?? 'Unknown';
                    $archiveResult = archiveWeatherData($weatherData, $conditions);
                    error_log("Cron weather fetch: " . ($archiveResult ? "Success" : "Failed"));
                }
            }
        }
    }
}
?>