<?php
require_once __DIR__ . '/../../../config/db_connect.php';

class Learn {
    public function getAll(string $lang = 'eu', string $fallback = 'es'): array {
        global $pdo;

        $sql = "
            SELECT
                l.id,
                COALESCE(ct.name,  ctf.name)  AS category_name,
                COALESCE(ct.slug,  ctf.slug)  AS category_slug,
                COALESCE(t.title, COALESCE(ct.name, ctf.name), CONCAT('Curso #', l.id)) AS title,
                COALESCE(t.[content], ct.[description], ctf.[description]) AS description,
                l.url,
                l.duration,
                l.status
            FROM dbo.learn AS l
            LEFT JOIN dbo.categories AS c
                   ON c.id = l.id_category
            LEFT JOIN dbo.category_translations AS ct
                   ON ct.id_category = c.id AND ct.lang = ?   -- lang
            LEFT JOIN dbo.category_translations AS ctf
                   ON ctf.id_category = c.id AND ctf.lang = ?  -- fallback
            LEFT JOIN dbo.[link] AS lk
                   ON lk.id_learn = l.id
            LEFT JOIN dbo.translation AS t
                   ON t.id_link = lk.id AND t.lang = ?         -- lang
            WHERE ISNULL(l.status,'active') <> 'hidden'
            ORDER BY l.id DESC";

        $st = $pdo->prepare($sql);
        $st->execute([$lang, $fallback, $lang]); // 3 parámetros posicionales (ODBC)
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
