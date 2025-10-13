<?php
// sync_moodle.php

// --- Config ---
$token = getenv('MOODLE_TOKEN') ?: '9d076fd25cc057e331cc3bdd41a80015'; // Mejor usa variable de entorno
$base  = 'https://www.aulavirtual.kutxabank.com/webservice/rest/server.php';
$params = http_build_query([
    'wstoken'               => $token,
    'wsfunction'            => 'local_wscontent_get_content_data',
    'moodlewsrestformat'    => 'json',
]);

$uri = $base . '?' . $params;

// Carpeta del propio script
$outDir = __DIR__;

// Marca de tiempo (igual que tu ps1)
$dt   = new DateTime('now', new DateTimeZone('Europe/Madrid'));
$name = sprintf('moodle_sync_%s.json', $dt->format('Y-m-d_H-i-s'));
$outFile = $outDir . DIRECTORY_SEPARATOR . $name;

// --- Descarga directa a archivo ---
$fp = @fopen($outFile, 'wb');
if ($fp === false) {
    fwrite(STDERR, "No puedo crear el archivo: $outFile\n");
    exit(1);
}

$ch = curl_init($uri);
curl_setopt_array($ch, [
    CURLOPT_FILE            => $fp,           // vuelca directo a disco (sin truncado en memoria)
    CURLOPT_FOLLOWLOCATION  => true,
    CURLOPT_FAILONERROR     => false,         // gestionaremos manualmente
    CURLOPT_TIMEOUT         => 300,
    CURLOPT_CONNECTTIMEOUT  => 30,
    CURLOPT_SSL_VERIFYPEER  => true,
    CURLOPT_SSL_VERIFYHOST  => 2,
    CURLOPT_USERAGENT       => 'sync_moodle.php/1.0',
    CURLOPT_HTTPHEADER      => ['Accept: application/json'],
    CURLOPT_ENCODING        => '',            // acepta gzip/deflate si el servidor lo ofrece
]);

$ok = curl_exec($ch);
$errno = curl_errno($ch);
$err   = curl_error($ch);
$code  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
fclose($fp);

// Validaciones y limpieza si falló
if (!$ok || $errno !== 0 || $code !== 200) {
    @unlink($outFile);
    $msg = "Error en la descarga. HTTP=$code; cURL_errno=$errno; cURL_error=$err";
    fwrite(STDERR, $msg . "\n");
    exit(1);
}

// (Opcional) Verificar tamaño > 0
if (filesize($outFile) === 0) {
    @unlink($outFile);
    fwrite(STDERR, "Descarga vacía.\n");
    exit(1);
}

echo "Guardado en: $outFile\n";
exit(0);
