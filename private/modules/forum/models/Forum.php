<?php
require_once __DIR__ . '/../../../config/db_connect.php';

class Forum
{
    public function getAll(): array
    {
        global $pdo;
        $sql = "
            SELECT
                f.id,
                f.url,
                f.date_start,
                f.date_finish,
                f.status,
                COALESCE(c.name, CONCAT('Evento #', f.id)) AS title,
                c.description                               AS description
            FROM dbo.forum f
            LEFT JOIN dbo.categories c ON c.id = f.id_category
            WHERE ISNULL(f.status, 'active') <> 'hidden'
            ORDER BY ISNULL(f.date_start, '9999-12-31') ASC, f.id DESC
        ";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
