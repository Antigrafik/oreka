<?php
require_once __DIR__ . '/../../../config/db_connect.php';

class Community
{
    /** Recomendaciones (con tema y soporte) */
    public function getRecommendations(): array {
        global $pdo;
        $sql = "
            SELECT
                r.id,
                r.title,
                r.author,
                r.content,
                r.created_at,
                t.name  AS theme,
                s.name  AS support
            FROM dbo.recommendation r
            LEFT JOIN dbo.recommendation_theme   t ON t.id = r.id_recommendation_theme
            LEFT JOIN dbo.recommendation_support s ON s.id = r.id_recommendation_support
            ORDER BY r.id DESC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Catálogo de temas */
    public function getThemes(): array {
        global $pdo;
        return $pdo->query("SELECT id, name FROM dbo.recommendation_theme ORDER BY name ASC")
                   ->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Catálogo de soportes */
    public function getSupports(): array {
        global $pdo;
        return $pdo->query("SELECT id, name FROM dbo.recommendation_support ORDER BY name ASC")
                   ->fetchAll(PDO::FETCH_ASSOC);
    }
}
