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
$dbUser = getenv("DB_USERNAME");
$dbPass = getenv("DB_PASSWORD");

//en caso de activar odbc:Driver descomentar la línea siguiente y comentar la de sqlsrv:Server ir a .env y activar las variables que tienen valores y desactivar las que no y en caso de sqlsrv:Server al revés

//$dsn = "odbc:Driver={ODBC Driver 17 for SQL Server};Server=$dbHost,$dbPort;Database=$dbDatabase;Encrypt=Yes;TrustServerCertificate=Yes;LoginTimeout=15";


$dsn = "sqlsrv:Server=$dbHost,$dbPort;Database=$dbDatabase;Encrypt=Yes;TrustServerCertificate=Yes;LoginTimeout=15";

try {
  $pdo = new PDO($dsn, $dbUser, $dbPass, [
    PDO::SQLSRV_ATTR_DIRECT_QUERY => true,
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::SQLSRV_ATTR_ENCODING => PDO::SQLSRV_ENCODING_UTF8,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $e) {
  die("Error en la conexión PDO: " . $e->getMessage());
}
