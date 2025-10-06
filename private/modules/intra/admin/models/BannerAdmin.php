<?php

class BannerAdmin
{
    public function getBannerHistory(): array {
        global $pdo;
        $sql = "
        SELECT b.id, b.is_raffle, b.prize, b.date_start, b.date_finish, b.status, b.created_at, b.updated_at,
               es.title  AS title_es,
               eu.title  AS title_eu
        FROM banner b
        LEFT JOIN link l          ON l.id_banner = b.id
        LEFT JOIN translation es  ON es.id_link  = l.id AND es.lang = 'es'
        LEFT JOIN translation eu  ON eu.id_link  = l.id AND eu.lang = 'eu'
        ORDER BY b.id DESC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBanner(int $id): ?array {
        global $pdo;
        $sql = "
        SELECT TOP (1) b.*, l.id AS link_id
        FROM banner b
        LEFT JOIN link l ON l.id_banner = b.id
        WHERE b.id = ?";
        $row = $pdo->prepare($sql);
        $row->execute([$id]);
        $b = $row->fetch(PDO::FETCH_ASSOC);
        if (!$b) return null;

        $out = [
            'id'          => (int)$b['id'],
            'is_raffle'   => (int)$b['is_raffle'],
            'prize'       => (string)($b['prize'] ?? ''),
            'date_start'  => (string)$b['date_start'],
            'date_finish' => (string)$b['date_finish'],
            'link_id'     => $b['link_id'] ? (int)$b['link_id'] : null,
            'es' => ['title'=>'','content'=>''],
            'eu' => ['title'=>'','content'=>''],
        ];

        if ($b['link_id']) {
            $ts = $pdo->prepare("SELECT lang, title, content FROM translation WHERE id_link = ? AND lang IN ('es','eu')");
            $ts->execute([$b['link_id']]);
            foreach ($ts->fetchAll(PDO::FETCH_ASSOC) as $t) {
                $lng = strtolower($t['lang']);
                $out[$lng] = ['title'=>$t['title'] ?? '', 'content'=>$t['content'] ?? ''];
            }
        }
        return $out;
    }

    public function findOverlappingBanner(string $startSql, string $finishSql, ?int $excludeId = null): ?array {
        global $pdo;
        $sql = "
            SELECT TOP (1) id, date_start, date_finish
            FROM dbo.banner
            WHERE date_start < ? AND date_finish > ?
            " . ($excludeId ? "AND id <> ?" : "") . "
            ORDER BY id DESC
        ";
        $st = $pdo->prepare($sql);
        $params = [$finishSql, $startSql];
        if ($excludeId) $params[] = $excludeId;
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function saveBanner(
        ?int $id, bool $isRaffle, ?string $prize,
        string $dateStart, string $dateFinish,
        string $titleEs, string $contentEs, string $titleEu, string $contentEu,
        string $status
    ): int {
        global $pdo;

        $pdo->beginTransaction();
        try {
            if ($id) {
                $up = $pdo->prepare("
                    UPDATE dbo.banner
                    SET is_raffle = ?, prize = ?, date_start = ?, date_finish = ?,
                        [status] = ?, updated_at = SYSDATETIME()
                    WHERE id = ?
                ");
                $up->execute([$isRaffle ? 1 : 0, $isRaffle ? $prize : null, $dateStart, $dateFinish, $status, $id]);

                $q = $pdo->prepare("SELECT id FROM dbo.[link] WHERE id_banner = ?");
                $q->execute([$id]);
                $linkId = $q->fetchColumn();
                if (!$linkId) {
                    $insL = $pdo->prepare("INSERT INTO dbo.[link] (id_banner) OUTPUT INSERTED.id VALUES (?)");
                    $insL->execute([$id]);
                    $linkId = (int)$insL->fetchColumn();
                }
            } else {
                $insB = $pdo->prepare("
                    INSERT INTO dbo.banner (is_raffle, prize, date_start, date_finish, [status], created_at, updated_at)
                    OUTPUT INSERTED.id
                    VALUES (?, ?, ?, ?, ?, SYSDATETIME(), SYSDATETIME())
                ");
                $insB->execute([$isRaffle ? 1 : 0, $isRaffle ? $prize : null, $dateStart, $dateFinish, $status]);
                $id = (int)$insB->fetchColumn();

                $insL = $pdo->prepare("INSERT INTO dbo.[link] (id_banner) OUTPUT INSERTED.id VALUES (?)");
                $insL->execute([$id]);
                $linkId = (int)$insL->fetchColumn();
            }

            $merge = $pdo->prepare("
                MERGE dbo.translation AS t
                USING (VALUES
                    (?, 'es', ?, ?),
                    (?, 'eu', ?, ?)
                ) AS s (id_link, lang, title, content)
                ON (t.id_link = s.id_link AND t.lang = s.lang)
                WHEN MATCHED THEN UPDATE SET title = s.title, content = s.content
                WHEN NOT MATCHED THEN INSERT (id_link, lang, title, content) VALUES (s.id_link, s.lang, s.title, s.content);
            ");
            $merge->execute([$linkId, $titleEs, $contentEs, $linkId, $titleEu, $contentEu]);

            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function deleteBanner(int $id): void {
        global $pdo;
        $pdo->beginTransaction();
        try {
            $lid = $pdo->prepare("SELECT id FROM link WHERE id_banner = ?");
            $lid->execute([$id]);
            $linkId = $lid->fetchColumn();

            if ($linkId) {
                $pdo->prepare("DELETE FROM translation WHERE id_link = ?")->execute([$linkId]);
                $pdo->prepare("DELETE FROM link WHERE id = ?")->execute([$linkId]);
            }
            $pdo->prepare("DELETE FROM banner WHERE id = ?")->execute([$id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
