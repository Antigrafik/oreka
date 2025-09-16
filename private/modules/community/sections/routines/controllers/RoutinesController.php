<?php
require_once __DIR__ . '/../models/Routines.php';

class RoutinesController
{
  public function render(string $lang = 'es', string $fallback = 'eu'): string {
    $m = new Routines();

    // 1) Guardar (POST) + PRG
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $idUser     = $m->currentUserId() ?? 0;
      $idCategory = (int)($_POST['category_id'] ?? 0);
      $frequency  = (int)($_POST['frequency'] ?? 0);
      $duration   = (int)($_POST['duration'] ?? 0);
      $typePosted = (int)($_POST['type_id'] ?? 0);

      $res = $m->createRoutineWithActivity([
        'id_user'     => $idUser,
        'id_category' => $idCategory,
        'frequency'   => $frequency,
        'duration'    => $duration,
      ]);

      // Flash en sesión (mensaje + recordatorio del tipo)
      $_SESSION['flash_msg']          = $res['ok'] ? '✔ Rutina guardada' : ('✖ ' . ($res['msg'] ?? 'Error'));
      $_SESSION['flash_selected_type'] = $typePosted;

      // Redirigir a la misma ruta (GET), sin query
      $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
      $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
      $pathOnly = strtok($_SERVER['REQUEST_URI'] ?? '/', '?'); // sin query

      $location = $scheme . '://' . $host . $pathOnly . '#routines';

      header('Location: ' . $location, true, 303);
      exit;
    }

    // 2) Cargar Tipos (hijos de 38: Indoor/Outdoor, etc.)
    $types = $m->getChildrenCategories(38, $lang, $fallback);

    // Mapa: idTipo => [subcategorías...]
    $catsByType = [];
    foreach ($types as $t) {
      $tid = (int)$t['id'];
      $catsByType[$tid] = $m->getChildrenCategories($tid, $lang, $fallback);
    }

    // 3) Tipo seleccionado (solo desde flash de sesión, sin GET)
    $selectedType = (int)($_SESSION['flash_selected_type'] ?? 0);
    if (isset($_SESSION['flash_selected_type'])) unset($_SESSION['flash_selected_type']);

    // 4) Subcategorías del tipo seleccionado (si lo hay)
    $cats = ($selectedType && isset($catsByType[$selectedType])) ? $catsByType[$selectedType] : [];

    ob_start();
    include __DIR__ . '/../views/routines.php';
    return ob_get_clean();
  }
}
