<?php
// Enable error display temporarily for debugging; remove in production
ini_set('display_errors', 0);
error_reporting(0);

// Set JSON content-type header
header('Content-Type: application/json');

// Connect to PostgreSQL database using environment variables
$conn_string = sprintf(
    "host=%s port=%s dbname=%s user=%s password=%s",
    getenv('DB_HOST'),
    getenv('DB_PORT'),
    getenv('DB_NAME'),
    getenv('DB_USER'),
    getenv('DB_PASSWORD')
);

$conn = pg_connect($conn_string);

if (!$conn) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

fetch('get_data.php')
    .then(response => response.text())  // Get raw text first
    .then(text => {
        console.log('Raw response text:', text);  // Log raw response for inspection
        return JSON.parse(text);  // Parse JSON after inspection
    })
    .then(data => {
        // your existing code to handle data
    })
    .catch(error => {
        console.error('Error parsing JSON:', error);
    });

// Query: latest analytics record
$analytics_result = pg_query($conn, "SELECT * FROM iss_analytics ORDER BY id DESC LIMIT 1");
if (!$analytics_result) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to query analytics']);
    pg_close($conn);
    exit();
}
$analytics = pg_fetch_assoc($analytics_result);
if ($analytics) {
    $analytics['altitude_changes'] = (int)$analytics['altitude_changes'];
    $analytics['altitude_change_magnitude'] = (float)$analytics['altitude_change_magnitude'];
    $analytics['max_longitude'] = (float)$analytics['max_longitude'];
    $analytics['min_longitude'] = (float)$analytics['min_longitude'];
} else {
    $analytics = null;
}

// Query: last 1000 entries from iss_data
$data_result = pg_query($conn, "SELECT * FROM iss_data ORDER BY timestamp DESC LIMIT 1000");
if (!$data_result) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to query iss_data']);
    pg_close($conn);
    exit();
}
$data = [];
while ($row = pg_fetch_assoc($data_result)) {
    $data[] = $row;
}

// Prepare response
$response = [
    'analytics' => $analytics,
    'data' => $data
];

// Output response as JSON
echo json_encode($response);

pg_close($conn);
?>



