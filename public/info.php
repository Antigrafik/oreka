<?php
// Modo: script de debug que IMPRIME (text/plain) y termina

// 1) Cargar .env (líneas KEY=VAL, ignora comentarios y vacías)
$envPath = __DIR__ . '/../.env';
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES) as $line) {
        $t = trim($line);
        if ($t === '' || str_starts_with($t, '#')) continue;
        putenv($t);
        // opcional: también en $_ENV
        if (strpos($t, '=') !== false) {
            [$k,$v] = explode('=', $t, 2);
            $_ENV[$k] = $v;
        }
    }
}

// 2) Cabecera de texto plano (no rompas tu HTML principal)
if (!headers_sent()) {
    header('Content-Type: text/plain; charset=utf-8');
}

// 3) Conexión PDO SQL Server (Windows Auth si UID/PWD nulos)
$dbHost     = getenv('DB_HOST') ?: 'localhost';
$dbPort     = getenv('DB_PORT') ?: '1433';
$dbDatabase = getenv('DB_DATABASE') ?: 'oreka';
$dsn = "sqlsrv:Server=$dbHost,$dbPort;Database=$dbDatabase;TrustServerCertificate=Yes;LoginTimeout=15";

try {
    $pdo = new PDO($dsn, null, null, [
        PDO::SQLSRV_ATTR_DIRECT_QUERY => true,
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::SQLSRV_ATTR_ENCODING    => PDO::SQLSRV_ENCODING_UTF8,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    echo "Error en la conexión PDO: " . $e->getMessage() . PHP_EOL;
    exit;
}

// 4) Consultas y salida
$sql = "
    SELECT
      SUSER_SNAME()    AS login_name,
      SYSTEM_USER      AS [system_user],
      CURRENT_USER     AS [db_user],
      ORIGINAL_LOGIN() AS original_login;
";
$row = $pdo->query($sql)->fetch();

echo "login_name : {$row['login_name']}\n";
echo "system_user: {$row['system_user']}\n";
echo "db_user    : {$row['db_user']}\n";
echo "original_login : {$row['original_login']}\n";

echo "Proceso Windows: " . (getenv('USERNAME') ?: getenv('USER')) . PHP_EOL;
echo "App Pool user  : " . (php_uname('n')) . "\\" . (getenv('USERNAME') ?: '') . PHP_EOL;
echo "SSO navegador  : " . ($_SERVER['REMOTE_USER'] ?? $_SERVER['AUTH_USER'] ?? '(no SSO)') . PHP_EOL;

echo PHP_EOL . "---- Lista de [usuario] en dbo.[user] ----" . PHP_EOL;

try {
    $stmt  = $pdo->query("SELECT [usuario] FROM dbo.[user] ORDER BY [usuario];");
    $names = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    if (empty($names)) {
        echo "(sin filas)\n";
    } else {
        foreach ($names as $n) echo $n . PHP_EOL;
    }
} catch (PDOException $e) {
    echo "Error listando dbo.[user].[usuario]: " . $e->getMessage() . PHP_EOL;
}

exit; // asegura que no se mezcle con más HTML
