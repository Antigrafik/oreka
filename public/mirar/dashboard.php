<?php
include('../config/config.php');
include('../config/session_start.php');

if (!isset($_SESSION['user']) || $_SESSION['user'] !== "ok") {
    header('Location: ' . $globalConfig['base_url'] . '/login');
    exit();
}

$page = $_GET['page'] ?? 'welcome';
$currentConfig = $pageConfig[$page] ?? $pageConfig['welcome'];

global $globalConfig, $currentConfig;

include("includes/admin_header.php");
?>

<div class="admin"> <!-- Estructura principal del dashboard -->

	<?php include('includes/admin_nav.php'); ?> <!-- Menú lateral -->

	<div class="admin__main"> <!-- Contenido principal -->
		<main class="admin__content">
		<?php
			$pageFile = "{$currentConfig['template_type']}.php";
			if (file_exists($pageFile)) {
			include($pageFile);
			} else {
			include "../404.php";
			}
		?>
		</main>
		<?php include("includes/admin_footer.php"); ?>
	</div>

</div>

