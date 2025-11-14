<?php
// Disable PHP error display to prevent corrupting JSON output
ini_set('display_errors', 0);
error_reporting(0);

// Connect to MySQL database
$conn = new mysqli("localhost", "root", "", "iss_telemetry");
$conn = pg_connect("host=" . getenv('DB_HOST') .
                   " port=" . getenv('DB_PORT') .
                   " dbname=" . getenv('DB_NAME') .
                   " user=" . getenv('DB_USER') .
                   " password=" . getenv('DB_PASSWORD'));
if (!$conn) {
    http_response_code(500);
    exit;
}

// Set JSON content type header
header('Content-Type: application/json');

// Execute query to get latest analytics record
$analytics_result = $conn->query("SELECT * FROM iss_analytics ORDER BY id DESC LIMIT 1");
if (!$analytics_result) {
    $analytics = null;
} else {
    $analytics = $analytics_result->fetch_assoc();
    
    // Cast fields to proper numeric types
    if ($analytics) {
        $analytics['altitude_changes'] = (int)$analytics['altitude_changes'];
        $analytics['altitude_change_magnitude'] = (float)$analytics['altitude_change_magnitude'];
        $analytics['max_longitude'] = (float)$analytics['max_longitude'];
        $analytics['min_longitude'] = (float)$analytics['min_longitude'];
    }
}

// Execute query to get last 1000 entries from iss_data
$data_result = $conn->query("SELECT * FROM iss_data ORDER BY timestamp DESC LIMIT 1000");
$data = [];
if ($data_result) {
    while ($row = $data_result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Create combined response array
$response = [
    'analytics' => $analytics,
    'data' => $data
];

// Output JSON encoded response
echo json_encode($response);

// Close database connection
$conn->close();
?>


