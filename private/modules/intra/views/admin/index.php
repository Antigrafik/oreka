<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">
            <li class="nav-item active">
                <a class="nav-link" href="/admin">Inicio</a>
            </li>
            <li class="nav-item active">
                <a class="nav-link" href="?section=users">Usuarios</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="?section=store">Tienda</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="?section=learn">Aula</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="?section=forum">Foro</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="?section=community">Comunidad</a>
            </li>
        </ul>
    </div>
</nav>
<?php
$request = $_SERVER['REQUEST_URI'];
$parts = explode('/', trim($request, '/')); // quita slashes y separa
$section = $parts[1] ?? 'admin'; // [0] sería "admin", [1] sería la sección

switch ($section) {
    case 'admin':
        include 'admin.php';
        break;
    case 'users':
        include 'users.php';
        break;
    case 'store':
        include 'store.php';
        break;
    case 'learn':
        include 'learn.php';
        break;
    case 'forum':
        include 'forum.php';
        break;
    case 'community':
        include 'community.php';
        break;
    default:
        echo "<p>Sección no encontrada.</p>";
        break;
}
?>
