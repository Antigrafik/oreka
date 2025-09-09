<?php
require_once __DIR__ . '/../../../config/db_connect.php';

class Forum
{
    public function getAll(string $lang = 'eu', ?string $fallback = null): array
    {
        global $pdo;

        // Si no pasan fallback o es igual que lang, usa el “otro” idioma como reserva
        if ($fallback === null || $fallback === $lang) {
            $fallback = ($lang === 'es') ? 'eu' : 'es';
        }

        $sql = "
                SELECT 
                    COALESCE(ct.name, ctf.name)       AS category_name,
                    COALESCE(t.title, tf.title)       AS forum_title,
                    COALESCE(t.content, tf.content)   AS description,
                    f.date_start,
                    f.date_finish
                FROM forum f
                LEFT JOIN link  AS lk ON lk.id_forum = f.id
                LEFT JOIN category_link AS cl ON cl.id_link = lk.id
                LEFT JOIN category      AS c  ON c.id = cl.id_category

                LEFT JOIN category_translation AS ct
                    ON ct.id_category = c.id AND ct.lang = ?
                LEFT JOIN category_translation AS ctf
                    ON ctf.id_category = c.id AND ctf.lang = ?

                LEFT JOIN translation AS t
                    ON t.id_link = lk.id AND t.lang = ?
                LEFT JOIN translation AS tf
                    ON tf.id_link = lk.id AND tf.lang = ?

                WHERE f.status = 'activo'
                AND (f.date_finish IS NULL OR f.date_finish > GETDATE())
                ORDER BY f.date_start ASC;
        ";

        $st = $pdo->prepare($sql);
        $st->execute([$lang, $fallback, $lang, $fallback]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
