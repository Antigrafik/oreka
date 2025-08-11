<?php
session_start();

// Suponiendo que ya tienes el nombre del usuario logueado en $user
// y que $pdo es tu conexión PDO

$isAdmin = false;

if ($user) {
    $stmt = $pdo->prepare("SELECT roles FROM users WHERE name = :name");
    $stmt->execute([':name' => $user]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && strtolower($row['roles']) === 'admin') {
        $isAdmin = true;
    }
}
?>
<div class="menu">
    <a class="menu-item" href="/">HOME</a>
    <a class="menu-item" href="/#learn">LEARN</a>
    <a class="menu-item" href="/#forum">FORUM</a>
    <a class="menu-item" href="/#community">COMMUNITY</a>
    <a class="menu-item" href="/store">STORE</a>
    <a class="menu-item" href="/mi-espacio">MI ESPACIO</a>
    <?php if ($isAdmin): ?>
        <a class="menu-item" href="/admin">ADMIN</a>
    <?php endif; ?>
</div>
