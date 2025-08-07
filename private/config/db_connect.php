<?php
$envPath = dirname(__DIR__, 2) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath);
    foreach ($lines as $line) {
        if (trim($line) !== '' && strpos(trim($line), '#') !== 0) {
            putenv(trim($line));
        }
    }
}

$dbHost = getenv("DB_HOST");
$dbPort = getenv("DB_PORT") ?: "1433";
$dbDatabase = getenv("DB_DATABASE");

$dsn = "sqlsrv:Server=$dbHost,$dbPort;Database=$dbDatabase";

try {
    $pdo = new PDO($dsn, null, null, [
        PDO::SQLSRV_ATTR_DIRECT_QUERY => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,
    ]);
} catch (PDOException $e) {
    die("Error en la conexión PDO: " . $e->getMessage());
}

