<?php
require_once __DIR__ . '/../../../config/db_connect.php';

class Community {
    public function getRecommendations(string $lang = 'eu', string $fallback = 'es'): array {
        global $pdo;

        $sql = "";

        $st = $pdo->prepare($sql);
        $st->execute([$lang]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getThemes(): array {
        global $pdo;

        $sql = "";

        $st = $pdo->prepare($sql);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSupports(): array {
        global $pdo;

        $sql = "";

        $st = $pdo->prepare($sql);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
}
