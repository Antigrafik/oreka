<?php
require_once PRIVATE_PATH . '/config/db_connect.php';

class Meeting
{
  private function normalizeFallback(string $lang, ?string $fallback): string {
    if ($fallback === null || $fallback === $lang) return ($lang === 'es') ? 'eu' : 'es';
    return $fallback;
  }

  public function getMeetings(string $lang='es', string $fallback='eu'): array {
    global $pdo;
    $fallback = $this->normalizeFallback($lang, $fallback);

    $sql = "
      SELECT
        COALESCE(t.[content], tf.[content])  AS activity,
        m.place,
        CONVERT(varchar(16), m.date_start, 120) AS date_start,
        m.email,
        m.created_at,
        l.id AS link_id
      FROM dbo.meeting m
      JOIN dbo.link    l  ON l.id_meeting = m.id
      LEFT JOIN dbo.translation t  ON t.id_link = l.id AND t.lang = ?
      LEFT JOIN dbo.translation tf ON tf.id_link = l.id AND tf.lang = ?
      ORDER BY m.created_at DESC";
    $st = $pdo->prepare($sql);
    $st->execute([$lang,$fallback]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public function expirePastMeetings(): void {
      global $pdo;
      // Si hay hora, cámbialo a: m.date_start < GETDATE()
      $sql = "
        UPDATE ua
          SET ua.status = 'finalizado'
        FROM dbo.user_activity AS ua
        JOIN dbo.point  AS p ON p.id = ua.id_point
        JOIN dbo.[link] AS l ON l.id = p.id_link
        JOIN dbo.meeting AS m ON m.id = l.id_meeting
        WHERE ua.status = 'en_proceso'
          AND CAST(m.date_start AS date) < CAST(GETDATE() AS date);
      ";
      $pdo->exec($sql);
    }

    /** Listar SOLO quedadas en proceso (status en_proceso). */
    public function getOpenMeetings(string $lang = 'es', string $fallback = 'eu'): array {
      global $pdo;

      // por si llaman con mismo lang, fija fallback distinto
      if ($fallback === null || $fallback === $lang) {
        $fallback = ($lang === 'es') ? 'eu' : 'es';
      }

      $sql = "
        SELECT
          m.id,
          m.place,
          m.date_start,
          m.email,
          COALESCE(t.title, tf.title) AS activity
        FROM dbo.meeting AS m
        JOIN dbo.[link] AS l   ON l.id_meeting = m.id
        JOIN dbo.point  AS p   ON p.id_link    = l.id
        JOIN dbo.user_activity AS ua
            ON ua.id_point = p.id AND ua.status = 'en_proceso'
        LEFT JOIN dbo.translation AS t
            ON t.id_link = l.id AND t.lang = ?
        LEFT JOIN dbo.translation AS tf
            ON tf.id_link = l.id AND tf.lang = ?
        ORDER BY m.date_start ASC, m.id ASC;
      ";
      $st = $pdo->prepare($sql);
      $st->execute([$lang, $fallback]);
      return $st->fetchAll(PDO::FETCH_ASSOC);
    }
  }

