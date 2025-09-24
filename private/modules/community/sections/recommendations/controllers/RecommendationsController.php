<?php
require_once PRIVATE_PATH . '/modules/community/sections/recommendations/models/Recommendations.php';
require_once PRIVATE_PATH . '/modules/community/sections/recommendations/models/RecommendationInsert.php';
require_once PRIVATE_PATH . '/modules/community/shared/models/CommunityCategories.php';

class RecommendationsController
{
  private function baseUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    // ruta sin query (PRG limpio)
    $pathOnly = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    return $scheme . '://' . $host . $pathOnly;
  }

  private function sectionUrl(): string {
    return $this->baseUrl() . '#community-recommendations';
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
    // ========== 1) POST SOLO DE ESTA SECCIÓN ==========
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
        && (($_POST['form'] ?? '') === 'new_recommendation')) {

      // --- Captura ---
      $author    = trim($_POST['author']  ?? '');
      $title     = trim($_POST['title']   ?? '');
      $comment   = trim($_POST['comment'] ?? '');
      $ilang     = trim($_POST['lang']    ?? $lang);
      $temaId    = isset($_POST['tema_id'])    && $_POST['tema_id']    !== '' ? (int)$_POST['tema_id']    : null;
      $soporteId = isset($_POST['soporte_id']) && $_POST['soporte_id'] !== '' ? (int)$_POST['soporte_id'] : null;

      $errors = [];
      if ($author === '')  $errors[] = 'Falta el autor.';
      if ($title === '')   $errors[] = 'Falta el título.';
      if ($comment === '') $errors[] = 'Falta el comentario.';
      if (!in_array($ilang, ['es','eu'], true)) $errors[] = 'Idioma no válido.';

      // Usuario actual (igual que antes)
      $currentUserId = $_SESSION['user_id'] ?? null;
      if (!$currentUserId) {
        global $pdo;
        $remote = $_SERVER['REMOTE_USER'] ?? null;
        if ($remote) {
          if (strpos($remote, '\\') !== false) {
            $remote = substr($remote, strrpos($remote, '\\') + 1);
          }
          $st = $pdo->prepare("SELECT id FROM dbo.[user] WHERE [name] = :name");
          $st->execute([':name' => $remote]);
          if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $currentUserId = (int)$row['id'];
          }
        }
      }
      if (!$currentUserId) $errors[] = 'No se pudo identificar el usuario actual.';

      // PRG en error: guardamos errores + old y redirigimos a la sección
      if ($errors) {
        $_SESSION['errors_reco'] = $errors;
        $_SESSION['old_reco'] = [
          'lang'       => $ilang,
          'tema_id'    => $temaId,
          'soporte_id' => $soporteId,
          'title'      => $title,
          'author'     => $author,
          'comment'    => $comment,
        ];
        $this->safeRedirect($this->sectionUrl(), 303);
      }

      // Inserción
      try {
        $svc = new RecommendationInsert();
        $svc->addRecommendation([
          'author'     => $author,
          'title'      => $title,
          'comment'    => $comment,
          'lang'       => $ilang,
          'user_id'    => $currentUserId,
          'points'     => 10,
          'tema_id'    => $temaId,
          'soporte_id' => $soporteId
        ]);

        // PRG en éxito: flash y redirección a la sección
        $_SESSION['flash_msg_reco'] = '✔ Recomendación enviada';
        $this->safeRedirect($this->sectionUrl(), 303);

      } catch (Throwable $e) {
        $_SESSION['errors_reco'] = ['Error al crear la recomendación: ' . $e->getMessage()];
        $_SESSION['old_reco'] = [
          'lang'       => $ilang,
          'tema_id'    => $temaId,
          'soporte_id' => $soporteId,
          'title'      => $title,
          'author'     => $author,
          'comment'    => $comment,
        ];
        $this->safeRedirect($this->sectionUrl(), 303);
      }
    }

    // ========== 2) Datos para render ==========
    $catRepo  = new CommunityCategories();
    $themes   = $catRepo->getChildren(10, $lang, $fallback);
    $supports = $catRepo->getChildren(11, $lang, $fallback);

    $model = new Recommendations();
    $recommendations = $model->getRecommendations($lang, $fallback);

    // ========== 3) Render ==========
    ob_start();
    include PRIVATE_PATH . '/modules/community/sections/recommendations/views/recommendations.php';
    return ob_get_clean();
  }
}
