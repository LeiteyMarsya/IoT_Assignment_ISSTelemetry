<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php-error.log');
error_reporting(E_ALL);

header('Content-Type: application/json');

// 1. Get database URL
$database_url = getenv("DATABASE_URL");
if (!$database_url) {
    http_response_code(500);
    echo json_encode(["error" => "DATABASE_URL not set"]);
    exit;
}

// 2. Parse database URL
$parts = parse_url($database_url);
if (!$parts) {
    http_response_code(500);
    echo json_encode(["error" => "Invalid DATABASE_URL"]);
    exit;
}

$host = $parts['host'] ?? null;
$port = $parts['port'] ?? 5432;
$user = $parts['user'] ?? null;
$pass = urldecode($parts['pass'] ?? '');
$db   = ltrim($parts['path'] ?? '', '/');

if (!$host || !$user || !$db) {
    http_response_code(500);
    echo json_encode(["error" => "Incomplete DATABASE_URL"]);
    exit;
}

// 3. Build connection string
$conn_string = sprintf(
    "host=%s port=%s dbname=%s user=%s password=%s sslmode=require",
    $host, $port, $db, $user, $pass
);

// 4. Connect to DB
$conn = pg_connect($conn_string);
if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . pg_last_error()]);
    exit;
}

// 5. Query analytics
$analytics_result = pg_query($conn, "SELECT * FROM iss_analytics ORDER BY id DESC LIMIT 1");
if (!$analytics_result) {
    http_response_code(500);
    echo json_encode(["error" => pg_last_error($conn)]);
    exit;
}
$analytics = pg_fetch_assoc($analytics_result);

// 6. Query recent data
$data_result = pg_query($conn, 'SELECT * FROM iss_data ORDER BY "timestamp" DESC LIMIT 1000');
if (!$data_result) {
    http_response_code(500);
    echo json_encode(["error" => pg_last_error($conn)]);
    exit;
}

$data = [];
while ($row = pg_fetch_assoc($data_result)) {
    $data[] = $row;
}

// 7. Send JSON
echo json_encode([
    "analytics" => $analytics,
    "data" => $data
]);

pg_close($conn);
?>
