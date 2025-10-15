<?php
// charts.php
include_once 'config.php';

// Get date range from URL or default to last 7 days
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Fetch data for charts
function getChartData($start_date, $end_date) {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) return [];
    
    $query = "SELECT 
                DATE(timestamp) as date,
                HOUR(timestamp) as hour,
                timestamp,
                temperature,
                humidity,
                wind_speed,
                pressure,
                dew_point,
                visibility
              FROM weather_readings 
              WHERE DATE(timestamp) BETWEEN :start_date AND :end_date 
              ORDER BY timestamp ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$chartData = getChartData($start_date, $end_date);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weather Charts - ASAI Weather Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 20px;
        }
        
        .header h1 {
            font-size: 2.2em;
            color: #2c3e50;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .navigation {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .nav-link {
            display: inline-block;
            margin: 0 15px;
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
        
        .date-filter {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .date-filter form {
            display: inline-flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .date-filter label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .date-filter input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .date-filter button {
            padding: 8px 20px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .date-filter button:hover {
            background: #219a52;
        }
        
        .chart-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .chart-wrapper {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            text-align: center;
            font-size: 1.2em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 30px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #3498db;
        }
        
        .stat-value {
            font-size: 1.8em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9em;
            color: #7f8c8d;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .chart-container {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ASAI Weather Charts</h1>
            <p>Historical Weather Data Analysis</p>
        </div>
        
        <div class="navigation">
            <a href="index.php" class="nav-link">Current Weather</a>
            <a href="charts.php" class="nav-link">Weather Charts</a>
            <a href="archive.php" class="nav-link">Data Archive</a>
        </div>
        
        <div class="date-filter">
            <form method="GET">
                <label for="start_date">From:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                
                <label for="end_date">To:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                
                <button type="submit">Update Charts</button>
            </form>
        </div>
        
        <?php if (!empty($chartData)): ?>
            <div class="chart-container">
                <div class="chart-wrapper">
                    <div class="chart-title">Temperature Trends</div>
                    <canvas id="temperatureChart"></canvas>
                </div>
                
                <div class="chart-wrapper">
                    <div class="chart-title">Humidity Trends</div>
                    <canvas id="humidityChart"></canvas>
                </div>
                
                <div class="chart-wrapper">
                    <div class="chart-title">Wind Speed</div>
                    <canvas id="windChart"></canvas>
                </div>
                
                <div class="chart-wrapper">
                    <div class="chart-title">Barometric Pressure</div>
                    <canvas id="pressureChart"></canvas>
                </div>
            </div>
            
            <div class="stats-grid">
                <?php
                // Calculate statistics
                $temps = array_column($chartData, 'temperature');
                $humidities = array_column($chartData, 'humidity');
                $winds = array_column($chartData, 'wind_speed');
                $pressures = array_column($chartData, 'pressure');
                
                $stats = [
                    'Max Temp' => [max($temps) . '°F', '#e74c3c'],
                    'Min Temp' => [min($temps) . '°F', '#3498db'],
                    'Avg Humidity' => [round(array_sum($humidities) / count($humidities)) . '%', '#27ae60'],
                    'Max Wind' => [max($winds) . ' mph', '#f39c12']
                ];
                
                foreach ($stats as $label => [$value, $color]): ?>
                    <div class="stat-card" style="border-left-color: <?php echo $color; ?>">
                        <div class="stat-value"><?php echo $value; ?></div>
                        <div class="stat-label"><?php echo $label; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <script>
                // Prepare chart data
                const labels = <?php echo json_encode(array_map(function($row) {
                    return date('M j g:i A', strtotime($row['timestamp']));
                }, $chartData)); ?>;
                
                const temperatureData = <?php echo json_encode(array_column($chartData, 'temperature')); ?>;
                const humidityData = <?php echo json_encode(array_column($chartData, 'humidity')); ?>;
                const windData = <?php echo json_encode(array_column($chartData, 'wind_speed')); ?>;
                const pressureData = <?php echo json_encode(array_column($chartData, 'pressure')); ?>;
                
                // Chart configurations
                const chartConfig = {
                    type: 'line',
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            x: {
                                ticks: {
                                    maxTicksLimit: 8
                                }
                            }
                        },
                        elements: {
                            point: {
                                radius: 2,
                                hoverRadius: 5
                            }
                        }
                    }
                };
                
                // Temperature Chart
                new Chart(
                    document.getElementById('temperatureChart'),
                    {
                        ...chartConfig,
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Temperature (°F)',
                                data: temperatureData,
                                borderColor: '#e74c3c',
                                backgroundColor: 'rgba(231, 76, 60, 0.1)',
                                tension: 0.4
                            }]
                        }
                    }
                );
                
                // Humidity Chart
                new Chart(
                    document.getElementById('humidityChart'),
                    {
                        ...chartConfig,
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Humidity (%)',
                                data: humidityData,
                                borderColor: '#3498db',
                                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                                tension: 0.4
                            }]
                        }
                    }
                );
                
                // Wind Chart
                new Chart(
                    document.getElementById('windChart'),
                    {
                        ...chartConfig,
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Wind Speed (mph)',
                                data: windData,
                                borderColor: '#f39c12',
                                backgroundColor: 'rgba(243, 156, 18, 0.1)',
                                tension: 0.4
                            }]
                        }
                    }
                );
                
                // Pressure Chart
                new Chart(
                    document.getElementById('pressureChart'),
                    {
                        ...chartConfig,
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Pressure (inHg)',
                                data: pressureData,
                                borderColor: '#27ae60',
                                backgroundColor: 'rgba(39, 174, 96, 0.1)',
                                tension: 0.4
                            }]
                        }
                    }
                );
            </script>
            
        <?php else: ?>
            <div class="error">
                <h2>No data available for the selected date range</h2>
                <p>Please try selecting a different date range or check back later for more data.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>