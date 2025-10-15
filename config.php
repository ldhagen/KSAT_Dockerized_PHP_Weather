<?php
// config.php
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

// Utility functions
function celsiusToFahrenheit($celsius) {
    return $celsius ? round(($celsius * 9/5) + 32, 1) : null;
}

function formatTimestamp($timestamp) {
    $dt = new DateTime($timestamp);
    $dt->setTimezone(new DateTimeZone('America/Chicago'));
    return $dt->format('g:i A T');
}

function getCurrentLocalTime() {
    $dt = new DateTime('now', new DateTimeZone('America/Chicago'));
    return $dt->format('g:i A T');
}

function getWindDirectionCardinal($degrees) {
    if ($degrees === null) return 'N/A';
    $directions = ['N', 'NNE', 'NE', 'ENE', 'E', 'ESE', 'SE', 'SSE', 'S', 'SSW', 'SW', 'WSW', 'W', 'WNW', 'NW', 'NNW'];
    $index = round($degrees / 22.5) % 16;
    return $directions[$index];
}
?>