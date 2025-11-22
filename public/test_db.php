<?php
// Load .env variables manually since we are outside Laravel
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && substr($line, 0, 1) !== '#') {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '5432';
$db   = $_ENV['DB_DATABASE'] ?? 'laravel';
$user = $_ENV['DB_USERNAME'] ?? 'root';
$pass = $_ENV['DB_PASSWORD'] ?? '';

echo "<h3>Database Connection Test</h3>";
echo "Attempting to connect to: <strong>$host</strong> on port <strong>$port</strong>...<br><br>";

try {
    $dsn = "pgsql:host=$host;port=$port;dbname=$db";
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<span style='color: green; font-weight: bold;'>✅ Connection Successful!</span>";
} catch (PDOException $e) {
    echo "<span style='color: red; font-weight: bold;'>❌ Connection Failed:</span><br>";
    echo "Error: " . $e->getMessage();
}
