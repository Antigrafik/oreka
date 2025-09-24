<?php
require_once __DIR__ . '/../shared/models/CommunityCategories.php';

// Controllers de secciones
require_once __DIR__ . '/../sections/recommendations/controllers/RecommendationsController.php';
require_once __DIR__ . '/../sections/routines/controllers/RoutinesController.php';
require_once __DIR__ . '/../sections/trial/controllers/TrialController.php';

class CommunityController
{
  public function render(array $opts = []): string {
    $lang     = defined('DEFAULT_LANG') ? strtolower(DEFAULT_LANG) : 'es';
    $fallback = 'es';
    $sections = $opts['sections'] ?? ['recommendations', 'routines', 'trial'];

    $language = $GLOBALS['language'] ?? [];

    // Render de secciones (cada una devuelve su HTML)
    $htmlSections = [];

    if (in_array('recommendations', $sections, true)) {
      $htmlSections['recommendations'] = (new RecommendationsController())->render($lang, $fallback);
    }
    if (in_array('routines', $sections, true)) {
      $htmlSections['routines'] = (new RoutinesController())->render($lang, $fallback);
    }
    if (in_array('trial', $sections, true)) {
      $htmlSections['trial'] = (new TrialController())->render($lang, $fallback);
    }

    // Pasamos todo a la vista contenedora
    ob_start();
    include __DIR__ . '/../views/community.php';
    return ob_get_clean();
  }
}
