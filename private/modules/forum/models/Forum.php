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

                -- Título: traducción de link si existe; si no, nombre de categoría (ct/ctf);
                -- si tampoco, 'Evento #id'
                COALESCE(t.title, COALESCE(ct.name, ctf.name), CONCAT('Evento #', f.id)) AS title,

                -- Descripción: primero la del link; si no, la de la categoría (ct/ctf)
                COALESCE(t.[content], ct.[description], ctf.[description]) AS description

            FROM dbo.forum AS f
            LEFT JOIN dbo.categories AS c
                   ON c.id = f.id_category

            LEFT JOIN dbo.category_translations AS ct
                   ON ct.id_category = c.id AND ct.lang = ?     -- 1) lang
            LEFT JOIN dbo.category_translations AS ctf
                   ON ctf.id_category = c.id AND ctf.lang = ?    -- 2) fallback

            LEFT JOIN dbo.[link] AS lk
                   ON lk.id_forum = f.id
            LEFT JOIN dbo.translation AS t
                   ON t.id_link = lk.id AND t.lang = ?           -- 3) lang

            WHERE ISNULL(f.status, 'active') <> 'hidden'
            ORDER BY ISNULL(f.date_start, '9999-12-31') ASC, f.id DESC
        ";

        $st = $pdo->prepare($sql);
        $st->execute([$lang, $fallback, $lang]);  // orden = ?, ?, ?
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
