<?php
require_once __DIR__ . '/../../../config/db_connect.php';

class Community {

  /** Hijos directos de una categoría padre (por id). */
  public function getChildren(int $parentId, string $lang = 'es', string $fallback = 'eu'): array {
    global $pdo;

    if ($fallback === null || $fallback === $lang) {
      $fallback = ($lang === 'es') ? 'eu' : 'es';
    }

    $sql = "
      SELECT
        c.id,
        COALESCE(ct.name, ctf.name)  AS name,
        COALESCE(ct.slug, ctf.slug)  AS slug
      FROM dbo.category_relation r
      JOIN dbo.category c
        ON c.id = r.id_child
      LEFT JOIN dbo.category_translation ct
        ON ct.id_category = c.id AND ct.lang = ?
      LEFT JOIN dbo.category_translation ctf
        ON ctf.id_category = c.id AND ctf.lang = ?
      WHERE r.id_parent = ?
      ORDER BY name, c.id;
    ";

    $st = $pdo->prepare($sql);
    $st->execute([$lang, $fallback, $parentId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getRecommendations(string $lang = 'eu', string $fallback = 'es'): array {
    global $pdo;

    if ($fallback === null || $fallback === $lang) {
      $fallback = ($lang === 'es') ? 'eu' : 'es';
    }

    $sql = "
      SELECT
        COALESCE(t.title, tf.title)         AS title,
        COALESCE(t.[content], tf.[content]) AS [description],
        r.author                            AS content_author,
        recby.recommended_by,
        r.likes,
        tema.name                           AS tema,
        tema.id                             AS tema_id,
        soporte.name                        AS soporte,
        soporte.id                          AS soporte_id,
        r.created_at                        AS date_start,
        r.id                                AS recommendation_id,
        l.id                                AS link_id

      FROM dbo.recommendation AS r
      JOIN dbo.[link]         AS l   ON l.id_recommendation = r.id
      LEFT JOIN dbo.translation AS t   ON t.id_link = l.id AND t.[lang] = ?
      LEFT JOIN dbo.translation AS tf  ON tf.id_link = l.id AND tf.[lang] = ?

      /* ===== QUIÉN RECOMIENDA ===== */
      OUTER APPLY (
        SELECT TOP (1) u.[name] AS recommended_by
        FROM dbo.point p
        JOIN dbo.user_activity ua ON ua.id_point = p.id
        JOIN dbo.[user] u         ON u.id = ua.id_user
        WHERE p.id_link = l.id
        ORDER BY p.created_at ASC
      ) AS recby

      /* ===== TEMA (padre 10) ===== */
      OUTER APPLY (
        SELECT TOP (1)
          c2.id,
          COALESCE(ct.name, ctf.name) AS name
        FROM dbo.category_link        AS cl2
        JOIN dbo.category             AS c2   ON c2.id = cl2.id_category
        JOIN dbo.category_relation    AS r2   ON r2.id_child = c2.id
        JOIN dbo.category_translation AS p1   ON p1.id_category = r2.id_parent AND p1.slug IN (N'tema')
        LEFT JOIN dbo.category_translation AS ct
               ON ct.id_category = c2.id AND ct.[lang] = ?
        LEFT JOIN dbo.category_translation AS ctf
               ON ctf.id_category = c2.id AND ctf.[lang] = ?
        WHERE cl2.id_link = l.id
      ) AS tema

      /* ===== SOPORTE (padre 11) ===== */
      OUTER APPLY (
        SELECT TOP (1)
          c3.id,
          COALESCE(cs.name, csf.name) AS name
        FROM dbo.category_link        AS cl3
        JOIN dbo.category             AS c3   ON c3.id = cl3.id_category
        JOIN dbo.category_relation    AS r4   ON r4.id_child = c3.id
        JOIN dbo.category_translation AS p2   ON p2.id_category = r4.id_parent AND p2.slug IN (N'soporte')
        LEFT JOIN dbo.category_translation AS cs
               ON cs.id_category = c3.id AND cs.[lang] = ?
        LEFT JOIN dbo.category_translation AS csf
               ON csf.id_category = c3.id AND csf.[lang] = ?
        WHERE cl3.id_link = l.id
      ) AS soporte

      ORDER BY r.created_at DESC;
    ";

    $st = $pdo->prepare($sql);
    // Orden: t.lang, tf.lang, ct.lang, ctf.lang, cs.lang, csf.lang
    $st->execute([$lang, $fallback, $lang, $fallback, $lang, $fallback]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }
}
