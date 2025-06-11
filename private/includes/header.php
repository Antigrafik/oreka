<?php include_once PRIVATE_PATH . '/config/config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $globalConfig['site_title'] ?? 'Mi Sitio' ?></title>
   <!--  <link rel="icon" href="<?= $globalConfig['favicon'] ?>" type="image/png"> -->

    <!-- Estilos -->
    <link rel="stylesheet" href="<?=$baseUrl . $globalConfig['stylesheet'] ?>">

</head>
<body>
