<?php
require_once __DIR__ . '/../../../config/db_connect.php';

class Learn {
    public function getAll(string $lang = 'eu', string $fallback = 'es'): array {
        global $pdo;

        // Si no pasan fallback o es igual que lang, usa el “otro” idioma como reserva
        if ($fallback === null || $fallback === $lang) {
            $fallback = ($lang === 'es') ? 'eu' : 'es';
        }

        $sql = "
                SELECT
                COALESCE(ct.name, ctf.name)       AS category_name,
                COALESCE(tr.title, trf.title)     AS learn_title,
                COALESCE(tr.content, trf.content) AS description,
                lrn.duration,
                lrn.url,
                img.path                          AS image_path,
                lrn.created_at                    AS date_sort
                FROM learn AS lrn
                JOIN link  AS lk  ON lk.id_learn = lrn.id
                LEFT JOIN category_link        AS cl   ON cl.id_link     = lk.id
                LEFT JOIN category             AS c    ON c.id           = cl.id_category
                LEFT JOIN category_translation AS ct   ON ct.id_category = c.id AND ct.lang  = ?
                LEFT JOIN category_translation AS ctf  ON ctf.id_category = c.id AND ctf.lang = ?
                LEFT JOIN translation          AS tr   ON tr.id_link     = lk.id AND tr.lang = ?
                LEFT JOIN translation          AS trf  ON trf.id_link    = lk.id AND trf.lang = ?
                LEFT JOIN image                AS img  ON img.id         = lrn.id_image
                WHERE lrn.status = 'activo'
                AND (c.status IS NULL OR c.status <> 'hidden')
                ORDER BY lrn.created_at DESC;
        ";

        $st  = $pdo->prepare($sql);
        $st->execute([$lang, $fallback, $lang, $fallback]); // 4 parámetros, en este orden
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
