<?php
require_once __DIR__ . '/../models/Routines.php';

class RoutinesController
{
  private function baseUrl(): string {
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $pathOnly = strtok($_SERVER['REQUEST_URI'] ?? '/', '?'); // sin query
    return $scheme . '://' . $host . $pathOnly;
  }

  private function sectionUrl(): string {
    return $this->baseUrl() . '#routines';
  }

  private function safeRedirect(string $url, int $code = 303): void {
    if (!headers_sent()) {
      header('Location: ' . $url, true, $code);
      exit;
    }
    echo '<script>location.replace(' . json_encode($url) . ');</script>';
    exit;
  }

  public function render(string $lang = 'es', string $fallback = 'eu'): string {
    $m = new Routines();

    // 1) Guardar (POST) SOLO si viene de este formulario + PRG
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
        && (($_POST['form'] ?? '') === 'routines_submit')) {

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

      // Limpia posibles claves antiguas
      unset($_SESSION['flash_msg'], $_SESSION['flash_msg_routines']);

      if (!empty($res['ok'])) {
        $_SESSION['flash_success_routines'] = '✔ Rutina guardada';
      } else {
        $_SESSION['flash_error_routines'] = '✖ ' . ($res['msg'] ?? 'Error');
      }
      $_SESSION['flash_selected_type'] = $typePosted;

      $this->safeRedirect($this->sectionUrl(), 303);
    }

    // 2) Cargar Tipos (hijos de 38: Indoor/Outdoor, etc.)
    $types = $m->getChildrenCategories(38, $lang, $fallback);

    // 3) Mapa: idTipo => [subcategorías...]
    $catsByType = [];
    foreach ($types as $t) {
      $tid = (int)$t['id'];
      $catsByType[$tid] = $m->getChildrenCategories($tid, $lang, $fallback);
    }

    // 4) Tipo seleccionado (solo desde flash de sesión, sin GET)
    $selectedType = (int)($_SESSION['flash_selected_type'] ?? 0);
    if (isset($_SESSION['flash_selected_type'])) unset($_SESSION['flash_selected_type']);

    // 5) Subcategorías del tipo seleccionado (si lo hay)
    $cats = ($selectedType && isset($catsByType[$selectedType])) ? $catsByType[$selectedType] : [];

    ob_start();
    include __DIR__ . '/../views/routines.php';
    return ob_get_clean();
  }
}
