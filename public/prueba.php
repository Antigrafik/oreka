<?php
require_once '../private/config/db_connect.php'; // ajusta la ruta real

try {
    $stmt = $pdo->query("SELECT name FROM sys.tables");
    echo "<h3>Tablas disponibles en la base de datos:</h3><ul>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li>" . htmlspecialchars($row['name']) . "</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
