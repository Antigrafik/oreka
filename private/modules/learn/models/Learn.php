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
                COALESCE(tr.title, trf.title)         AS learn_title,
                COALESCE(tr.lang, trf.lang)           AS language_used,
                lrn.status,
                lrn.duration,
                lrn.visible,
                lrn.timecreated,
                lrn.module_url,
                lrn.otherlangidentifier,
                lrn.image_url,
                lrn.identifier
            FROM learn AS lrn
            JOIN link AS lk ON lk.id_learn = lrn.id
            LEFT JOIN translation AS tr   ON tr.id_link = lk.id AND tr.lang = ?
            LEFT JOIN translation AS trf  ON trf.id_link = lk.id AND trf.lang = ?
            WHERE lrn.status = 'active' AND lrn.visible = 'True'
            ORDER BY lrn.timecreated DESC;
        ";

        $st  = $pdo->prepare($sql);
        $st->execute([$lang, $fallback]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
