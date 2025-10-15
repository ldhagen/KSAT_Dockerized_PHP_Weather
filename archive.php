<?php
// archive.php
include_once 'config.php';

// Get page and date filter parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$records_per_page = 50;
$offset = ($page - 1) * $records_per_page;

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Build query with filters
function getArchiveData($offset, $records_per_page, $start_date, $end_date) {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) return ['data' => [], 'total' => 0];
    
    // Build where clause
    $where_clause = '';
    $params = [];
    
    if ($start_date && $end_date) {
        $where_clause = 'WHERE DATE(timestamp) BETWEEN :start_date AND :end_date';
        $params[':start_date'] = $start_date;
        $params[':end_date'] = $end_date;
    } elseif ($start_date) {
        $where_clause = 'WHERE DATE(timestamp) >= :start_date';
        $params[':start_date'] = $start_date;
    } elseif ($end_date) {
        $where_clause = 'WHERE DATE(timestamp) <= :end_date';
        $params[':end_date'] = $end_date;
    }
    
    // Get data
    $query = "SELECT * FROM weather_readings 
              $where_clause 
              ORDER BY timestamp DESC 
              LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM weather_readings $where_clause";
    $count_stmt = $db->prepare($count_query);
    foreach ($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    $count_stmt->execute();
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    return ['data' => $data, 'total' => $total];
}

$archiveData = getArchiveData($offset, $records_per_page, $start_date, $end_date);
$total_pages = ceil($archiveData['total'] / $records_per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Archive - ASAI Weather Dashboard</title>
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
            max-width: 1400px;
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
        
        .filters {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .filters form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .filters label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .filters input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .filters button {
            padding: 8px 20px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .filters button:hover {
            background: #219a52;
        }
        
        .archive-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .archive-table th {
            background: #34495e;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85em;
            letter-spacing: 0.5px;
        }
        
        .archive-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .archive-table tr:hover {
            background: #f8f9fa;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 30px;
            gap: 10px;
        }
        
        .pagination a, .pagination span {
            padding: 8px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #3498db;
        }
        
        .pagination a:hover {
            background: #3498db;
            color: white;
        }
        
        .pagination .current {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .stats {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 20px;
            font-size: 0.9em;
        }
        
        @media (max-width: 768px) {
            .archive-table {
                display: block;
                overflow-x: auto;
            }
            
            .filters form {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ASAI Weather Data Archive</h1>
            <p>Historical Weather Readings</p>
        </div>
        
        <div class="navigation">
            <a href="index.php" class="nav-link">Current Weather</a>
            <a href="charts.php" class="nav-link">Weather Charts</a>
            <a href="archive.php" class="nav-link">Data Archive</a>
        </div>
        
        <div class="filters">
            <form method="GET">
                <label for="start_date">From Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>">
                
                <label for="end_date">To Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>">
                
                <button type="submit">Filter</button>
                <a href="archive.php" style="padding: 8px 15px; background: #95a5a6; color: white; text-decoration: none; border-radius: 4px;">Clear</a>
            </form>
        </div>
        
        <div class="stats">
            Total Records: <?php echo number_format($archiveData['total']); ?> | 
            Showing page <?php echo $page; ?> of <?php echo $total_pages; ?>
        </div>
        
        <?php if (!empty($archiveData['data'])): ?>
            <table class="archive-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Temp (°F)</th>
                        <th>Humidity (%)</th>
                        <th>Wind Speed (mph)</th>
                        <th>Wind Dir</th>
                        <th>Pressure (inHg)</th>
                        <th>Dew Point (°F)</th>
                        <th>Visibility (mi)</th>
                        <th>Conditions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($archiveData['data'] as $row): 
                        // Convert wind direction to cardinal
                        $wind_dir = $row['wind_direction'];
                        $directions = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
                        $wind_cardinal = $wind_dir !== null ? $directions[round($wind_dir / 22.5) % 16] : 'N/A';
                    ?>
                        <tr>
                            <td><?php echo date('M j, Y g:i A', strtotime($row['timestamp'])); ?></td>
                            <td><?php echo $row['temperature'] ?? 'N/A'; ?></td>
                            <td><?php echo $row['humidity'] ?? 'N/A'; ?></td>
                            <td><?php echo $row['wind_speed'] ?? '0.0'; ?></td>
                            <td><?php echo $wind_cardinal . ($wind_dir ? ' (' . round($wind_dir) . '°)' : ''); ?></td>
                            <td><?php echo $row['pressure'] ?? 'N/A'; ?></td>
                            <td><?php echo $row['dew_point'] ?? 'N/A'; ?></td>
                            <td><?php echo $row['visibility'] ?? 'N/A'; ?></td>
                            <td><?php echo htmlspecialchars($row['conditions'] ?? 'Unknown'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?php echo $start_date ? '&start_date=' . urlencode($start_date) : ''; ?><?php echo $end_date ? '&end_date=' . urlencode($end_date) : ''; ?>">First</a>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $start_date ? '&start_date=' . urlencode($start_date) : ''; ?><?php echo $end_date ? '&end_date=' . urlencode($end_date) : ''; ?>">Previous</a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $start_date ? '&start_date=' . urlencode($start_date) : ''; ?><?php echo $end_date ? '&end_date=' . urlencode($end_date) : ''; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $start_date ? '&start_date=' . urlencode($start_date) : ''; ?><?php echo $end_date ? '&end_date=' . urlencode($end_date) : ''; ?>">Next</a>
                        <a href="?page=<?php echo $total_pages; ?><?php echo $start_date ? '&start_date=' . urlencode($start_date) : ''; ?><?php echo $end_date ? '&end_date=' . urlencode($end_date) : ''; ?>">Last</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="error">
                <h2>No archive data available</h2>
                <p>Weather data will be archived automatically when the current weather page is loaded.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>