<?php
// Disable error display to protect JSON output
ini_set('display_errors', 0);
error_reporting(0);

// Connect to PostgreSQL
$conn = pg_connect("host=" . getenv('DB_HOST') .
                   " port=" . getenv('DB_PORT') .
                   " dbname=" . getenv('DB_NAME') .
                   " user=" . getenv('DB_USER') .
                   " password=" . getenv('DB_PASSWORD'));

if (!$conn) {
    http_response_code(500);
    exit(json_encode(['error' => 'DB connection failed']));
}

// Set JSON content type
header('Content-Type: application/json');

// Fetch analytics
$analytics_result = pg_query($conn, "SELECT * FROM iss_analytics ORDER BY id DESC LIMIT 1");
$analytics = $analytics_result ? pg_fetch_assoc($analytics_result) : null;

if ($analytics) {
    $analytics['altitude_changes'] = (int)$analytics['altitude_changes'];
    $analytics['altitude_change_magnitude'] = (float)$analytics['altitude_change_magnitude'];
    $analytics['max_longitude'] = (float)$analytics['max_longitude'];
    $analytics['min_longitude'] = (float)$analytics['min_longitude'];
}

// Fetch data entries
$data_result = pg_query($conn, "SELECT * FROM iss_data ORDER BY timestamp DESC LIMIT 1000");
$data = [];
if ($data_result) {
    while ($row = pg_fetch_assoc($data_result)) {
        $data[] = $row;
    }
}

// Output JSON response
echo json_encode([
    'analytics' => $analytics,
    'data' => $data
]);

pg_close($conn);
?>
