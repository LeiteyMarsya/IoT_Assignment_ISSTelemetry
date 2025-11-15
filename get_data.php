<?php
// Proper error logging (safe for production)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php-error.log');
error_reporting(E_ALL);

header('Content-Type: application/json');

// Render uses DATABASE_URL instead of separate DB_* variables
$database_url = getenv("EXTERNAL_DATABASE_URL");

if (!$database_url) {
    http_response_code(500);
    echo json_encode(["error" => "DATABASE_URL not set on server"]);
    exit();
}

$parts = parse_url($database_url);

$conn_string = sprintf(
    "host=%s port=%s dbname=%s user=%s password=%s sslmode=require",
    $parts["host"],
    $parts["port"],
    ltrim($parts["path"], "/"),
    $parts["user"],
    $parts["pass"]
);

$conn = pg_connect($conn_string);

if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit();
}

// --- Fetch latest analytics record ---
$analytics_query = "SELECT * FROM iss_analytics ORDER BY id DESC LIMIT 1";
$analytics_result = pg_query($conn, $analytics_query);

if (!$analytics_result) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to query iss_analytics"]);
    exit();
}

$analytics = pg_fetch_assoc($analytics_result);

// Convert numeric fields
if ($analytics) {
    $analytics['altitude_changes'] = (int)$analytics['altitude_changes'];
    $analytics['altitude_change_magnitude'] = (float)$analytics['altitude_change_magnitude'];
    $analytics['max_longitude'] = (float)$analytics['max_longitude'];
    $analytics['min_longitude'] = (float)$analytics['min_longitude'];
}

// --- Fetch last 1000 raw ISS data records ---
$data_query = 'SELECT * FROM iss_data ORDER BY "timestamp" DESC LIMIT 1000';
$data_result = pg_query($conn, $data_query);

if (!$data_result) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to query iss_data"]);
    exit();
}

$data = [];

while ($row = pg_fetch_assoc($data_result)) {
    $data[] = $row;
}

// Return JSON
echo json_encode([
    "analytics" => $analytics,
    "data" => $data
]);

pg_close($conn);
?>

