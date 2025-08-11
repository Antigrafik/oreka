<?php
require_once __DIR__ . '/../../../config/db_connect.php';

class Learn {
    public function getAll(): array {
        global $pdo;
        $sql = "
            SELECT 
                l.id,
                COALESCE(c.name, CONCAT('Curso #', l.id)) AS title,
                c.description                             AS description,
                l.url,
                i.path                                    AS image_path,
                l.status
            FROM dbo.learn l
            LEFT JOIN dbo.categories c ON c.id = l.id_category
            LEFT JOIN dbo.image i      ON i.id = l.id_image
            WHERE ISNULL(l.status,'active') <> 'hidden'
            ORDER BY l.id DESC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }
}
