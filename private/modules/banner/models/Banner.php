<?php
require_once __DIR__ . '/../../../config/db_connect.php';
 
class BannerModel {

    public function syncStatuses(): void {
        global $pdo;

        $sql = "
        DECLARE @now datetime2 = CAST(SYSDATETIMEOFFSET() AT TIME ZONE 'Central European Standard Time' AS datetime2);
 
        ;WITH Calc AS (
            SELECT
                b.id,
                CASE
                    WHEN b.status = 'draft' THEN 'draft'
                    WHEN b.date_start IS NULL OR b.date_finish IS NULL THEN 'scheduled'
                    WHEN @now <  b.date_start  THEN 'scheduled'
                    WHEN @now <= b.date_finish THEN 'running'
                    ELSE 'finished'
                END AS new_status
            FROM banner b
        )
        UPDATE b
            SET b.status = c.new_status
        FROM banner b
        JOIN Calc c ON c.id = b.id
        WHERE b.status <> 'draft'
          AND b.status <> c.new_status;";
 
        $pdo->exec($sql);
    }
 
    public function getActive(string $lang = 'es', string $fallback = 'es'): ?array {
        global $pdo;
 
        $sql = "
        SELECT TOP (1)
            b.id,
            b.is_raffle,
            b.prize,
            b.date_start,
            b.date_finish,
            b.status,
            COALESCE(te.title,  tf.title)   AS title,
            COALESCE(te.content,tf.content) AS content
        FROM banner b
        LEFT JOIN link l    ON l.id_banner = b.id
        LEFT JOIN translation te ON te.id_link = l.id AND te.lang = ?
        LEFT JOIN translation tf ON tf.id_link = l.id AND tf.lang = ?
        WHERE
            (
               b.status = 'running'
               OR (b.status = 'scheduled'
                   AND b.date_start IS NOT NULL
                   AND b.date_finish IS NOT NULL
                   AND SYSDATETIME() >= b.date_start
                   AND SYSDATETIME() <= b.date_finish)
            )
        ORDER BY b.date_start DESC, b.id DESC";
        $st = $pdo->prepare($sql);
        $st->execute([$lang, $fallback]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;
 
        $row['title']   = (string)($row['title'] ?? '');
        $row['content'] = (string)($row['content'] ?? '');
        $row['is_raffle'] = filter_var(($row['is_raffle'] ?? false), FILTER_VALIDATE_BOOLEAN);
 
        return $row;
    }
}
