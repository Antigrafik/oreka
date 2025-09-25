<?php
require_once PRIVATE_PATH . '/config/db_connect.php';

class MeetingInsert
{
  /** Devuelve la categoría (id) donde entity_type='meeting', si existe */
  private function getMeetingCategoryId(): ?int {
    global $pdo;
    $st = $pdo->prepare("SELECT TOP 1 id FROM dbo.category WHERE entity_type = 'meeting' ORDER BY id ASC");
    $st->execute();
    if ($row = $st->fetch(PDO::FETCH_ASSOC)) return (int)$row['id'];
    return null;
  }

  public function addMeeting(array $d): int {
    global $pdo;

    $pdo->beginTransaction();
    try {
      // 1) meeting
      $sqlM = "INSERT INTO dbo.meeting (place, date_start, date_finish, email, created_at, updated_at)
               VALUES (:place, :date_start, NULL, :email, GETDATE(), GETDATE())";
      $st = $pdo->prepare($sqlM);
      $st->execute([
        ':place'      => $d['place'],
        ':date_start' => $d['date'] . ($d['time'] ? (' ' . $d['time'] . ':00') : ' 00:00:00'),
        ':email'      => $d['email'],
      ]);
      $meetingId = (int)$pdo->lastInsertId();

      // 2) link
      $sqlL = "INSERT INTO dbo.link (id_meeting) VALUES (:id_meeting)";
      $st = $pdo->prepare($sqlL);
      $st->execute([':id_meeting' => $meetingId]);
      $linkId = (int)$pdo->lastInsertId();

    // 3) translation (guardamos actividad como título y contenido)
    $title = substr(trim((string)$d['activity']), 0, 255);   // usar substr en lugar de mb_substr
    if ($title === '') { 
        $title = 'Quedada deportiva'; 
    }

    $sqlT = "INSERT INTO dbo.translation (id_link, lang, title, content)
            VALUES (:id_link, :lang, :title, :content)";
    $st = $pdo->prepare($sqlT);
    $st->execute([
    ':id_link' => $linkId,
    ':lang'    => $d['lang'],
    ':title'   => $title,
    ':content' => $d['activity'],
    ]);

      // 4) category_link (si hay categoría meeting)
      if ($catId = $this->getMeetingCategoryId()) {
        $st = $pdo->prepare("INSERT INTO dbo.category_link (id_link, id_category) VALUES (:L,:C)");
        $st->execute([':L'=>$linkId, ':C'=>$catId]);
      }

      // 5) point (+10)
      $st = $pdo->prepare("INSERT INTO dbo.point (id_link, points, created_at) VALUES (:L, :P, GETDATE())");
      $st->execute([':L'=>$linkId, ':P'=>$d['points'] ?? 10]);
      $pointId = (int)$pdo->lastInsertId();

      // 6) user_activity (en_proceso)
      $st = $pdo->prepare("INSERT INTO dbo.user_activity (id_user, id_point, status) VALUES (:U,:P,:S)");
      $st->execute([':U'=>$d['user_id'], ':P'=>$pointId, ':S'=>'en_proceso']);

      $pdo->commit();
      return $meetingId;

    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      throw $e;
    }
  }
}
