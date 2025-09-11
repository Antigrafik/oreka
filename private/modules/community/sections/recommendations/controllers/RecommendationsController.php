<?php
require_once PRIVATE_PATH . '/modules/community/sections/recommendations/models/Recommendations.php';
require_once PRIVATE_PATH . '/modules/community/sections/recommendations/models/RecommendationInsert.php';
require_once PRIVATE_PATH . '/modules/community/shared/models/CommunityCategories.php';

class RecommendationsController
{
  // URL actual con fallbacks compatibles con IIS
  private function currentUrl(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $uri =
      ($_SERVER['HTTP_X_ORIGINAL_URL'] ?? null) // IIS URL Rewrite
      ?? ($_SERVER['UNENCODED_URL']    ?? null) // Otra variante en IIS
      ?? ($_SERVER['REQUEST_URI']      ?? null) // Apache/NGINX
      ?? (
        ($_SERVER['PHP_SELF'] ?? '/')
        . ((isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '') ? ('?' . $_SERVER['QUERY_STRING']) : '')
      );

    return $scheme . '://' . $host . $uri;
  }

  // Redirección segura: usa header() si no se han enviado, si no, fallback JS
  private function safeRedirect(string $url, int $code = 303): void {
    if (!headers_sent()) {
      header('Location: ' . $url, true, $code);
      exit;
    }
    // Fallback cuando ya hubo salida (por ej. menu.php imprimió algo)
    echo '<script>location.replace(' . json_encode($url) . ');</script>';
    exit;
  }

  public function render(string $lang = 'es', string $fallback = 'eu'): string {
    $language = $GLOBALS['language'] ?? [];
    $errors = [];
    $flash  = null;

    // 1) MANEJO DE POST ANTES DE CUALQUIER SALIDA
    if (( $_SERVER['REQUEST_METHOD'] ?? '' ) === 'POST' && ( $_POST['form'] ?? '' ) === 'new_recommendation') {
      // TODO CSRF: if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { $errors[] = 'CSRF inválido.'; }

      // --- Captura y validación mínima ---
      $author    = trim($_POST['author']  ?? '');
      $title     = trim($_POST['title']   ?? '');
      $comment   = trim($_POST['comment'] ?? '');
      $ilang     = trim($_POST['lang']    ?? $lang); // 'es' o 'eu'
      $temaId    = !empty($_POST['tema_id'])    ? (int)$_POST['tema_id']    : null;
      $soporteId = !empty($_POST['soporte_id']) ? (int)$_POST['soporte_id'] : null;

      if ($author === '')  $errors[] = 'Falta el autor.';
      if ($title === '')   $errors[] = 'Falta el título.';
      if ($comment === '') $errors[] = 'Falta el comentario.';
      if (!in_array($ilang, ['es','eu'], true)) $errors[] = 'Idioma no válido.';

      // --- Usuario actual ---
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
          $row = $st->fetch(PDO::FETCH_ASSOC);
          if ($row) $currentUserId = (int)$row['id'];
        }
      }
      if (!$currentUserId) $errors[] = 'No se pudo identificar el usuario actual.';

      // --- Inserción ---
      if (!$errors) {
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

          // PRG: redirige a una ruta estable (recomendado)
          // $target = ($GLOBALS['globalConfig']['base_url'] ?? '') . '/community#recommendations';

          // O bien a la URL actual:
          $target = $this->currentUrl();

          $this->safeRedirect($target, 303);

        } catch (Throwable $e) {
          $errors[] = 'Error al crear la recomendación: ' . $e->getMessage();
          // No redirigimos: dejamos que la vista pinte los errores y repinte el formulario
        }
      }
      // Si hay errores, seguimos para renderizar la vista con $errors
    }

    // 2) Datos para render (ya sin redirecciones)
    $catRepo  = new CommunityCategories();
    $themes   = $catRepo->getChildren(10, $lang, $fallback); // padre "tema"
    $supports = $catRepo->getChildren(11, $lang, $fallback); // padre "soporte"

    $model = new Recommendations();
    $recommendations = $model->getRecommendations($lang, $fallback);

    // 3) Render
    ob_start();
    // disponibles en la vista: $errors, $flash, $themes, $supports, $recommendations, $lang
    include PRIVATE_PATH . '/modules/community/sections/recommendations/views/recommendations.php';
    return ob_get_clean();
  }
}
