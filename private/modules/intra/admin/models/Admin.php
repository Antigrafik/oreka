<?php
require_once __DIR__ . '/../../../../config/db_connect.php';

class Admin {
    public function getAll(string $lang = 'eu', string $fallback = 'es'): array {
        global $pdo;

        $sql = "
            SELECT
                l.id,
                COALESCE(MAX(ct.name),  MAX(ctf.name))  AS category_name,
                COALESCE(MAX(ct.slug),  MAX(ctf.slug))  AS category_slug,
                COALESCE(MAX(t.title),
                         COALESCE(MAX(ct.name), MAX(ctf.name)),
                         CONCAT('Curso #', l.id))          AS title,
                COALESCE(MAX(t.[content]),
                         MAX(ct.[description]),
                         MAX(ctf.[description]))           AS description,
                l.url,
                l.duration,
                l.status
            FROM learn AS l
            LEFT JOIN link            AS lk  ON lk.id_learn = l.id
            LEFT JOIN category_link   AS cl  ON cl.id_link  = lk.id
            LEFT JOIN category        AS c   ON c.id        = cl.id_category
            LEFT JOIN category_translation AS ct
                   ON ct.id_category = c.id AND ct.lang  = ?   -- lang
            LEFT JOIN category_translation AS ctf
                   ON ctf.id_category = c.id AND ctf.lang = ?   -- fallback
            LEFT JOIN translation     AS t   ON t.id_link   = lk.id AND t.lang = ?  -- lang
            WHERE ISNULL(l.status,'active') <> 'hidden'
            GROUP BY l.id, l.url, l.duration, l.status
            ORDER BY l.id DESC";

        $st = $pdo->prepare($sql);
        $st->execute([$lang, $fallback, $lang]); // ODBC usa ? posicionales
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
