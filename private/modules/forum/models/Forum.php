<?php
require_once __DIR__ . '/../../../config/db_connect.php';

class Forum
{
    public function getAll(string $lang = 'eu', string $fallback = 'es'): array
    {
        global $pdo;

        $sql = "
            SELECT
                f.id,
                f.url,
                f.date_start,
                f.date_finish,
                f.status,
                COALESCE(
                    MAX(t.title),
                    COALESCE(MAX(ct.name), MAX(ctf.name)),
                    CONCAT('Evento #', f.id)
                ) AS title,
                COALESCE(
                    MAX(t.[content]),
                    MAX(ct.[description]),
                    MAX(ctf.[description])
                ) AS description
            FROM forum AS f
            LEFT JOIN link              AS lk  ON lk.id_forum  = f.id
            LEFT JOIN category_link     AS cl  ON cl.id_link   = lk.id
            LEFT JOIN category          AS c   ON c.id         = cl.id_category
            LEFT JOIN category_translation AS ct
                   ON ct.id_category = c.id AND ct.lang  = ?   -- lang
            LEFT JOIN category_translation AS ctf
                   ON ctf.id_category = c.id AND ctf.lang = ?   -- fallback
            LEFT JOIN translation       AS t   ON t.id_link    = lk.id AND t.lang = ?  -- lang
            WHERE ISNULL(f.status,'active') <> 'hidden'
            GROUP BY f.id, f.url, f.date_start, f.date_finish, f.status
            ORDER BY ISNULL(f.date_start, '9999-12-31') ASC, f.id DESC
        ";

        $st = $pdo->prepare($sql);
        $st->execute([$lang, $fallback, $lang]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
