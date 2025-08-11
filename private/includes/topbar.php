<?php
// Asegura excepciones en PDO
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$user = $_SERVER['REMOTE_USER'] ?? null;
$totalPuntos = null;

// Si REMOTE_USER viene como DOMINIO\usuario, quédate con la parte del usuario
if ($user && strpos($user, '\\') !== false) {
    $user = explode('\\', $user, 2)[1];
}

if ($user) {
    try {
        // Comprobar si el usuario existe
        $checkUser = $pdo->prepare("SELECT id FROM dbo.users WHERE name = :name");
        $checkUser->execute([':name' => $user]);
        $userData = $checkUser->fetch(PDO::FETCH_ASSOC);

        if (!$userData) {
            // Usuario no permitido → mostrar mensaje y salir
            echo "<h1 style='color:red; text-align:center;'>No tienes permitido entrar</h1>";
            exit;
        }

        // Total de puntos del usuario (todas sus actividades)
        $sql = "
            SELECT COALESCE(SUM(p.puntos), 0) AS total_puntos
            FROM dbo.users u
            JOIN dbo.user_activity ua ON ua.id_user = u.id
            JOIN dbo.points p        ON p.id = ua.id_points
            WHERE u.name = :name
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':name' => $user]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalPuntos = (string)$row['total_puntos'];

    } catch (PDOException $e) {
        echo "<h1>Error en la base de datos: " . htmlspecialchars($e->getMessage()) . "</h1>";
        exit;
    }
} else {
    // No hay usuario autenticado
    echo "<h1 style='color:red; text-align:center;'>No tienes permitido entrar</h1>";
    exit;
}
?>

<p>Hola <?= htmlspecialchars($user) ?> | <?= htmlspecialchars($totalPuntos) ?> puntos</p>
