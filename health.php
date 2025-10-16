<?php
// health.php - Simple health check endpoint
header('Content-Type: application/json');

try {
    require_once 'config.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Check if we have recent data
    $stmt = $db->query("SELECT MAX(timestamp) as last_update FROM weather_readings");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $lastUpdate = $result['last_update'] ? new DateTime($result['last_update']) : null;
    $now = new DateTime();
    
    $status = [
        'status' => 'healthy',
        'timestamp' => $now->format('Y-m-d H:i:s T'),
        'database' => 'connected',
        'last_data_update' => $lastUpdate ? $lastUpdate->format('Y-m-d H:i:s T') : 'never',
        'data_freshness' => $lastUpdate ? $now->getTimestamp() - $lastUpdate->getTimestamp() : null
    ];
    
    // Consider unhealthy if no data in last 15 minutes
    if (!$lastUpdate || ($now->getTimestamp() - $lastUpdate->getTimestamp()) > 900) {
        $status['status'] = 'degraded';
        $status['warning'] = 'No recent weather data';
    }
    
    echo json_encode($status, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode([
        'status' => 'unhealthy',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s T')
    ], JSON_PRETTY_PRINT);
}
?>