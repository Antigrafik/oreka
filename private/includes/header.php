<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $globalConfig['site_title'] ?? 'Mi Sitio' ?></title>
    <link rel="icon" href="<?= $globalConfig['favicon'] ?>" type="image/png">

    <?php foreach ($globalConfig['stylesheet'] as $style): ?>
        <link rel="stylesheet" href="<?php echo $style; ?>">
    <?php endforeach; ?>
    <link rel="stylesheet" href="/assets/css/banner.css">
</head>
<body>
