<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php-error.log');
error_reporting(E_ALL);

header('Content-Type: application/json');

// 1. Get environment variable
$database_url = getenv("DATABASE_URL");

if (!$database_url) {
    http_response_code(500);
    echo json_encode(["error" => "DATABASE_URL not set"]);
    exit;
}

// 2. Convert postgresql:// → postgres://
$database_url = str_replace("postgresql://", "postgres://", $database_url);

// 3. Parse URL
$parts = parse_url($database_url);
if (!$parts) {
    http_response_code(500);
    echo json_encode(["error" => "Invalid DATABASE_URL format"]);
    exit;
}

// 4. Extract components
$host = $parts['host'] ?? null;
$port = $parts['port'] ?? 5432;
$user = $parts['user'] ?? null;
$pass = $parts['pass'] ?? null;
$db   = ltrim($parts['path'] ?? '', '/');

if (!$host || !$user || !$db) {
    http_response_code(500);
    echo json_encode(["error" => "DATABASE_URL missing required components"]);
    exit;
}

// 5. Build connection string
$conn_string = sprintf(
    "host=%s port=%s dbname=%s user=%s password=%s sslmode=require",
    $host, $port, $db, $user, $pass
);

// 6. Connect
$conn = pg_connect($conn_string);
if (!$conn) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed"]);
    exit;
}

// 7. Fetch analytics
$analytics_result = pg_query($conn, "SELECT * FROM iss_analytics ORDER BY id DESC LIMIT 1");
$analytics = pg_fetch_assoc($analytics_result);

// 8. Fetch data
$data_result = pg_query($conn, 'SELECT * FROM iss_data ORDER BY "timestamp" DESC LIMIT 1000');
$data = [];

while ($row = pg_fetch_assoc($data_result)) {
    $data[] = $row;
}

// 9. Return JSON
echo json_encode([
    "analytics" => $analytics,
    "data" => $data
]);

pg_close($conn);
?>
