<?php

class ForumAdmin
{
    public function getForumHistory(): array {
        global $pdo;
        $sql = "
        SELECT f.id, f.date_start, f.date_finish, f.status, f.url,
               es.title  AS title_es,
               eu.title  AS title_eu
        FROM forum f
        LEFT JOIN link l          ON l.id_forum = f.id
        LEFT JOIN translation es  ON es.id_link = l.id AND es.lang = 'es'
        LEFT JOIN translation eu  ON eu.id_link = l.id AND eu.lang = 'eu'
        ORDER BY f.id DESC";
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getForum(int $id): ?array {
        global $pdo;
        $sql = "
        SELECT TOP (1) f.*, l.id AS link_id
        FROM forum f
        LEFT JOIN link l ON l.id_forum = f.id
        WHERE f.id = ?";
        $st = $pdo->prepare($sql);
        $st->execute([$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        $out = [
            'id'          => (int)$row['id'],
            'date_start'  => (string)$row['date_start'],
            'date_finish' => (string)$row['date_finish'],
            'url'         => (string)($row['url'] ?? ''),
            'link_id'     => $row['link_id'] ? (int)$row['link_id'] : null,
            'es' => ['title'=>'','content'=>''],
            'eu' => ['title'=>'','content'=>''],
        ];

        if ($row['link_id']) {
            $ts = $pdo->prepare("SELECT lang, title, content FROM translation WHERE id_link = ? AND lang IN ('es','eu')");
            $ts->execute([$row['link_id']]);
            foreach ($ts->fetchAll(PDO::FETCH_ASSOC) as $t) {
                $lng = strtolower($t['lang']);
                $out[$lng] = ['title'=>$t['title'] ?? '', 'content'=>$t['content'] ?? ''];
            }
        }
        return $out;
    }

    /** Útil si quieres bloquear solapes desde servidor (opcional) */
    public function findOverlappingActivity(string $startSql, string $finishSql, ?int $excludeId = null): ?array {
        global $pdo;
        $sql = "
            SELECT TOP (1) id, date_start, date_finish
            FROM dbo.forum
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

    public function saveForum(
        ?int $id,
        string $dateStart, string $dateFinish, ?string $url,
        string $titleEs, string $contentEs, string $titleEu, string $contentEu,
        string $status
    ): int {
        global $pdo;

        $pdo->beginTransaction();
        try {
            if ($id) {
                $up = $pdo->prepare("
                    UPDATE dbo.forum
                    SET date_start = ?, date_finish = ?, url = ?, [status] = ?, updated_at = SYSDATETIME()
                    WHERE id = ?
                ");
                $up->execute([$dateStart, $dateFinish, $url, $status, $id]);

                $q = $pdo->prepare("SELECT id FROM dbo.[link] WHERE id_forum = ?");
                $q->execute([$id]);
                $linkId = $q->fetchColumn();
                if (!$linkId) {
                    $insL = $pdo->prepare("INSERT INTO dbo.[link] (id_forum) OUTPUT INSERTED.id VALUES (?)");
                    $insL->execute([$id]);
                    $linkId = (int)$insL->fetchColumn();
                }
            } else {
                $ins = $pdo->prepare("
                    INSERT INTO dbo.forum (date_start, date_finish, url, [status], created_at, updated_at)
                    OUTPUT INSERTED.id
                    VALUES (?, ?, ?, ?, SYSDATETIME(), SYSDATETIME())
                ");
                $ins->execute([$dateStart, $dateFinish, $url, $status]);
                $id = (int)$ins->fetchColumn();

                $insL = $pdo->prepare("INSERT INTO dbo.[link] (id_forum) OUTPUT INSERTED.id VALUES (?)");
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

    public function deleteForum(int $id): void {
        global $pdo;
        $pdo->beginTransaction();
        try {
            $lid = $pdo->prepare("SELECT id FROM link WHERE id_forum = ?");
            $lid->execute([$id]);
            $linkId = $lid->fetchColumn();

            if ($linkId) {
                $pdo->prepare("DELETE FROM translation WHERE id_link = ?")->execute([$linkId]);
                $pdo->prepare("DELETE FROM link WHERE id = ?")->execute([$linkId]);
            }
            $pdo->prepare("DELETE FROM forum WHERE id = ?")->execute([$id]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
