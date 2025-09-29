<?php
require_once __DIR__ . '/../models/Meeting.php';
require_once __DIR__ . '/../models/MeetingInsert.php';

class MeetingController
{
  private function baseUrl(): string {
    $scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $pathOnly = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
    return $scheme . '://' . $host . $pathOnly;
  }
  private function sectionUrl(): string { return $this->baseUrl() . '#community-meeting'; }

  private function safeRedirect(string $url, int $code = 303): void {
    if (!headers_sent()) { header('Location: '.$url, true, $code); exit; }
    echo '<script>location.replace(' . json_encode($url) . ');</script>'; exit;
  }

  public function render(string $lang = 'es', string $fallback = 'eu'): string {
    // === POST SOLO si viene de este formulario ===
    if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
        && (($_POST['form'] ?? '') === 'meeting_submit')) {

      $errors = [];

      $ilang     = trim($_POST['lang']    ?? $lang);           // es / eu
      $activity  = trim($_POST['activity'] ?? '');             // irá a translation.content
      $place     = trim($_POST['place']    ?? '');
      $date      = trim($_POST['date']     ?? '');             // YYYY-MM-DD
      $time      = trim($_POST['time']     ?? '');             // HH:MM (opcional)
      $emailLoc  = trim($_POST['email_local'] ?? '');          // antes de @kutxabank.es
      $email     = $emailLoc !== '' ? ($emailLoc . '@kutxabank.es') : null;

      if (!in_array($ilang, ['es','eu'], true)) $errors[] = 'Idioma no válido.';
      if ($activity === '') $errors[] = 'Indica la actividad deportiva.';
      if ($place === '')    $errors[] = 'Indica el lugar.';
      if ($date === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $errors[] = 'Fecha no válida.';
      if ($time !== '' && !preg_match('/^\d{2}:\d{2}$/', $time))       $errors[] = 'Hora no válida.';
      if ($email && !preg_match('/^[a-z0-9._%+\-]+@kutxabank\.es$/i', $email)) $errors[] = 'Email no válido.';

      // Usuario actual (igual que en recommendations)
      $currentUserId = $_SESSION['user_id'] ?? null;
      if (!$currentUserId) {
        global $pdo;
        $remote = $_SERVER['REMOTE_USER'] ?? null;
        if ($remote) {
          if (strpos($remote, '\\') !== false) $remote = substr($remote, strrpos($remote, '\\') + 1);
          $st = $pdo->prepare("SELECT id FROM dbo.[user] WHERE [usuario] = :usuario");
          $st->execute([':usuario' => $remote]);
          if ($row = $st->fetch(PDO::FETCH_ASSOC)) $currentUserId = (int)$row['id'];
        }
      }
      if (!$currentUserId) $errors[] = 'No se pudo identificar el usuario actual.';

      if ($errors) {
        $_SESSION['errors_meeting'] = $errors;
        $_SESSION['old_meeting'] = [
          'lang'=>$ilang,'activity'=>$activity,'place'=>$place,'date'=>$date,'time'=>$time,'email_local'=>$emailLoc
        ];
        $this->safeRedirect($this->sectionUrl(), 303);
      }

      try {
        (new MeetingInsert())->addMeeting([
          'lang'      => $ilang,
          'activity'  => $activity,
          'place'     => $place,
          'date'      => $date,
          'time'      => $time ?: null,
          'email'     => $email,
          'user_id'   => $currentUserId,
          'points'    => 10,                 // por si en el futuro lo parametrizas
        ]);

        $_SESSION['flash_msg_meeting'] = 'Enhorabuena, acabas de sumar 10 puntos por tu esfuerzo.';
        $this->safeRedirect($this->sectionUrl(), 303);

      } catch (Throwable $e) {
        $_SESSION['errors_meeting'] = ['Error al crear la quedada: ' . $e->getMessage()];
        $_SESSION['old_meeting']    = [
          'lang'=>$ilang,'activity'=>$activity,'place'=>$place,'date'=>$date,'time'=>$time,'email_local'=>$emailLoc
        ];
        $this->safeRedirect($this->sectionUrl(), 303);
      }
    }

    // === GET: listar
    $model = new Meeting();
    $model->expirePastMeetings();
    $meetings = $model->getOpenMeetings($lang, $fallback);

    ob_start();
    include __DIR__ . '/../views/meeting.php';
    return ob_get_clean();
  }
}
