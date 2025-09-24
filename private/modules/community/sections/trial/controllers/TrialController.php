<?php
require_once __DIR__ . '/../models/Trial.php';

class TrialController
{
  private function baseUrl(): string {
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $pathOnly = strtok($_SERVER['REQUEST_URI'] ?? '/', '?'); // sin query
    return $scheme . '://' . $host . $pathOnly;
  }

  private function sectionUrl(): string {
    return $this->baseUrl() . '#trial';
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
    $m = new Trial();

    // Procesar SOLO si es el formulario de Trial + PRG
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
        && (($_POST['form'] ?? '') === 'trial_submit')) {

      $idUser   = $m->currentUserId() ?? 0;
      $content  = trim($_POST['content'] ?? '');
      $fileInfo = $_FILES['file'] ?? null;

      $res = $m->createTrialWithUpload([
        'id_user' => $idUser,
        'content' => $content,
        'file'    => $fileInfo,
      ]);

      // Limpia posibles claves antiguas
      unset($_SESSION['flash_msg'], $_SESSION['flash_msg_trial']);

      if (!empty($res['ok'])) {
        $_SESSION['flash_success_trial'] = 'Enhorabuena, acabas de sumar 10 puntos por tu esfuerzo.';
      } else {
        $_SESSION['flash_error_trial'] = '✖ ' . ($res['msg'] ?? 'Error al enviar tu prueba.');
      }

      $this->safeRedirect($this->sectionUrl(), 303);
    }


    ob_start();
    include __DIR__ . '/../views/trial.php';
    return ob_get_clean();
  }
}
