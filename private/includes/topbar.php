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
        $checkUser = $pdo->prepare("SELECT id FROM [user] WHERE name = :name");
        $checkUser->execute([':name' => $user]);
        $userData = $checkUser->fetch(PDO::FETCH_ASSOC);

       /*if (!$userData) {
            // Usuario no permitido → mostrar mensaje y salir
            echo "<h1 style='color:red; text-align:center;'>" . $language['topbar']['without_permission'] . "</h1>";
            exit;
        }*/

        // Total de puntos del usuario (todas sus actividades)
        $sql = "
            SELECT COALESCE(SUM(p.points), 0) AS total_puntos
            FROM [user] u
            JOIN user_activity ua ON ua.id_user = u.id
            JOIN point p        ON p.id = ua.id_point
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
    echo "<h1 style='color:red; text-align:center;'>" . $language['topbar']['without_permission'] . "</h1>";
    exit;
}
?>

<!-- Encabezado -->
<header class="main-header">
    <div class="logo-left">
        <img src="/assets/images/logo_oreka.png" alt="Oreka Logo">
    </div>

    <div class="user-center">
        <span><?= htmlspecialchars($user) ?> | <?= htmlspecialchars($totalPuntos) ?> <?php echo $language['topbar']['points']; ?></span>
    </div>

    <div class="logo-right-container">
        <img src="/assets/images/logo_kutxa.jpg" alt="Kutxa Logo" class="logo-right">
    </div>
</header>
