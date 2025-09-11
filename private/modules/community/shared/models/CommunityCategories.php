<?php

require_once PRIVATE_PATH . '/config/db_connect.php';

class CommunityCategories
{
  private function normalizeFallback(string $lang, ?string $fallback): string {
    if ($fallback === null || $fallback === $lang) {
      return ($lang === 'es') ? 'eu' : 'es';
    }
    return $fallback;
  }

  /** Hijos directos de un padre */
  public function getChildren(int $parentId, string $lang = 'es', string $fallback = 'eu'): array {
    global $pdo;

    $fallback = $this->normalizeFallback($lang, $fallback);

    $sql = "
      SELECT c.id,
             COALESCE(ct.name, ctf.name) AS name,
             COALESCE(ct.slug, ctf.slug) AS slug
      FROM dbo.category_relation r
      JOIN dbo.category c                 ON c.id = r.id_child
      LEFT JOIN dbo.category_translation ct  ON ct.id_category = c.id AND ct.lang = ?
      LEFT JOIN dbo.category_translation ctf ON ctf.id_category = c.id AND ctf.lang = ?
      WHERE r.id_parent = ?
      ORDER BY name, c.id;
    ";

    $st = $pdo->prepare($sql);
    $st->execute([$lang, $fallback, $parentId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }
}
