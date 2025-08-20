<?php
require_once __DIR__ . '/../includes/config.php';

// Quick DB connection test using the constants from config.php
$host = defined('DB_HOST') ? DB_HOST : 'undefined';
$user = defined('DB_USER') ? DB_USER : 'undefined';
$pass = defined('DB_PASS') ? DB_PASS : 'undefined';
$name = defined('DB_NAME') ? DB_NAME : 'undefined';

header('Content-Type: text/plain');

echo "Testing DB connection with:\n";
echo "DB_HOST=$host\nDB_USER=$user\nDB_NAME=$name\n";

$mysqli = @new mysqli($host, $user, $pass, $name);
if ($mysqli->connect_error) {
    echo "ERROR: " . $mysqli->connect_error . "\n";
    exit(1);
}

echo "OK - connected. Server info: " . $mysqli->server_info . "\n";
$mysqli->close();
