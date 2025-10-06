<?php

class LegalAdmin
{
    public function getLatestLegalIds(): array {
        global $pdo;
        $row = $pdo->query("
            SELECT TOP (1) al.id AS admin_legal_id, ISNULL(al.status,0) AS status, l.id AS link_id
            FROM [admin_legal] al
            JOIN [link] l ON l.id_admin_legal = al.id
            ORDER BY al.id DESC
        ")->fetch(PDO::FETCH_ASSOC);

        if ($row) return $row;

        $pdo->beginTransaction();
        try {
            $pdo->prepare("INSERT INTO [admin_legal] ([status], [created_at], [updated_at]) VALUES (1, SYSDATETIME(), SYSDATETIME())")->execute();
            $adminId = (int)$pdo->query("SELECT CAST(SCOPE_IDENTITY() AS INT)")->fetchColumn();

            $st2 = $pdo->prepare("INSERT INTO [link] ([id_admin_legal]) VALUES (?)");
            $st2->execute([$adminId]);
            $linkId = (int)$pdo->query("SELECT CAST(SCOPE_IDENTITY() AS INT)")->fetchColumn();

            $pdo->commit();
            return ['admin_legal_id' => $adminId, 'status' => 1, 'link_id' => $linkId];
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function getLatestLegal(): array {
        global $pdo;
        $ids = $this->getLatestLegalIds();

        $res = [
            'status' => (int)$ids['status'],
            'es' => ['title' => '', 'content' => ''],
            'eu' => ['title' => '', 'content' => ''],
            'admin_legal_id' => (int)$ids['admin_legal_id'],
            'link_id'        => (int)$ids['link_id'],
        ];

        $ts = $pdo->prepare("SELECT lang, title, content FROM [translation] WHERE id_link = ? AND lang IN ('es','eu')");
        $ts->execute([$ids['link_id']]);
        foreach ($ts->fetchAll(PDO::FETCH_ASSOC) as $t) {
            $lng = strtolower($t['lang']);
            $res[$lng] = ['title' => (string)$t['title'], 'content' => (string)$t['content']];
        }
        return $res;
    }

    public function saveLegalUpdateByIdKeepOnEmpty(
        int $adminId, int $linkId, int $status,
        string $titleEs, string $contentEs,
        string $titleEu, string $contentEu
    ): void {
        global $pdo;
        if ($adminId <= 0 || $linkId <= 0) {
            throw new InvalidArgumentException('IDs de legal inválidos.');
        }

        $pdo->beginTransaction();
        try {
            $up = $pdo->prepare("
                UPDATE dbo.[admin_legal]
                SET [status] = ?, [updated_at] = SYSDATETIME()
                WHERE id = ?
            ");
            $up->execute([$status, $adminId]);

            $sql = "
                MERGE dbo.[translation] AS t
                USING (VALUES
                    (?, 'es', ?, ?),
                    (?, 'eu', ?, ?)
                ) AS s (id_link, lang, title, content)
                ON  (t.id_link = s.id_link AND t.lang = s.lang)
                WHEN MATCHED THEN
                    UPDATE SET
                        title   = COALESCE(NULLIF(s.title,   ''), t.title),
                        content = COALESCE(NULLIF(s.content, ''), t.content)
                WHEN NOT MATCHED AND (
                        NULLIF(s.title,'') IS NOT NULL OR
                        NULLIF(s.content,'') IS NOT NULL
                ) THEN
                    INSERT (id_link, lang, title, content)
                    VALUES (s.id_link, s.lang,
                            COALESCE(NULLIF(s.title,   ''), ''),
                            COALESCE(NULLIF(s.content, ''), ''))
                ;
            ";
            $m = $pdo->prepare($sql);
            $m->execute([$linkId, $titleEs, $contentEs, $linkId, $titleEu, $contentEu]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
