<?php
require_once __DIR__ . '/../shared/models/CommunityCategories.php';

// Controllers de secciones
require_once __DIR__ . '/../sections/recommendations/controllers/RecommendationsController.php';
// Cuando los crees:
// require_once __DIR__ . '/../sections/routines/controllers/RoutinesController.php';
// require_once __DIR__ . '/../sections/trials/controllers/TrialsController.php';

class CommunityController
{
  public function render(array $opts = []): string {
    $lang     = defined('DEFAULT_LANG') ? strtolower(DEFAULT_LANG) : 'es';
    $fallback = 'es';
    $sections = $opts['sections'] ?? ['recommendations']; // añade 'routines','trials' cuando estén listos

    $language = $GLOBALS['language'] ?? [];

    // Render de secciones (cada una devuelve su HTML)
    $htmlSections = [];

    if (in_array('recommendations', $sections, true)) {
      $htmlSections['recommendations'] = (new RecommendationsController())->render($lang, $fallback);
    }
    // if (in_array('routines', $sections, true)) {
    //   $htmlSections['routines'] = (new RoutinesController())->render($lang, $fallback);
    // }
    // if (in_array('trials', $sections, true)) {
    //   $htmlSections['trials'] = (new TrialsController())->render($lang, $fallback);
    // }

    // Pasamos todo a la vista contenedora
    ob_start();
    include __DIR__ . '/../views/community.php';
    return ob_get_clean();
  }
}
