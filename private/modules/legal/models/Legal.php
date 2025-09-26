<?php
class Legal {
  public function getHtml(string $lang='es'): string {
    global $pdo;

    $sql = "
      SELECT TOP (1) t.title, t.content
      FROM [admin_legal] al
      JOIN [link]        l ON l.id_admin_legal = al.id
      JOIN [translation] t ON t.id_link = l.id AND t.lang = :lang
      WHERE al.status = 1
      ORDER BY al.id DESC
    ";
    $st = $pdo->prepare($sql);
    $st->execute([':lang' => $lang]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    // solo si no existe esa traducción, intenta la otra
    if (!$row) {
      $fallback = ($lang === 'es') ? 'eu' : 'es';
      $st->execute([':lang' => $fallback]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
    }

    if (!$row) {
      return '<div class="container empty"><p>Legal no disponible.</p></div>';
    }

    $title = htmlspecialchars($row['title'] ?? '');
    return '<article class="container"><h1>'.$title.'</h1>'.($row['content'] ?? '').'</article>';
  }
}
