<?php
require_once __DIR__ . '/../../../../config/db_connect.php';

class Admin {

    /* ==== EXISTENTE: listado para Aula (no tocar) ==== */
    public function getAll(string $lang = 'eu', string $fallback = 'es'): array {
        global $pdo;

        $sql = "
            SELECT
                l.id,
                COALESCE(MAX(ct.name),  MAX(ctf.name))  AS category_name,
                COALESCE(MAX(ct.slug),  MAX(ctf.slug))  AS category_slug,
                COALESCE(MAX(t.title),
                         COALESCE(MAX(ct.name), MAX(ctf.name)),
                         CONCAT('Curso #', l.id))          AS title,
                COALESCE(MAX(t.[content]),
                         MAX(ct.[description]),
                         MAX(ctf.[description]))           AS description,
                l.url,
                l.duration,
                l.status
            FROM learn AS l
            LEFT JOIN link            AS lk  ON lk.id_learn = l.id
            LEFT JOIN category_link   AS cl  ON cl.id_link  = lk.id
            LEFT JOIN category        AS c   ON c.id        = cl.id_category
            LEFT JOIN category_translation AS ct
                   ON ct.id_category = c.id AND ct.lang  = ?   -- lang
            LEFT JOIN category_translation AS ctf
                   ON ctf.id_category = c.id AND ctf.lang = ?   -- fallback
            LEFT JOIN translation     AS t   ON t.id_link   = lk.id AND t.lang = ?  -- lang
            WHERE ISNULL(l.status,'active') <> 'hidden'
            GROUP BY l.id, l.url, l.duration, l.status
            ORDER BY l.id DESC";

        $st = $pdo->prepare($sql);
        $st->execute([$lang, $fallback, $lang]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ==== LEGAL (editor) ==== */

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

    public function saveLegalUpdate(int $status, string $titleEs, string $contentEs, string $titleEu, string $contentEu): void {
        global $pdo;
        $ids    = $this->getLatestLegalIds();
        $adminId = (int)$ids['admin_legal_id'];
        $linkId  = (int)$ids['link_id'];

        $pdo->beginTransaction();
        try {
            $up = $pdo->prepare("UPDATE [admin_legal] SET [status] = ?, [updated_at] = SYSDATETIME() WHERE id = ?");
            $up->execute([$status, $adminId]);

            $mergeSql = "
                MERGE [translation] AS t
                USING (VALUES
                   (?, 'es', ?, ?),
                   (?, 'eu', ?, ?)
                ) AS s (id_link, lang, title, content)
                ON (t.id_link = s.id_link AND t.lang = s.lang)
                WHEN MATCHED THEN
                   UPDATE SET title = s.title, content = s.content
                WHEN NOT MATCHED THEN
                   INSERT (id_link, lang, title, content) VALUES (s.id_link, s.lang, s.title, s.content);
            ";
            $m = $pdo->prepare($mergeSql);
            $m->execute([$linkId, $titleEs, $contentEs, $linkId, $titleEu, $contentEu]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /* ==== TOGGLES DE MÓDULO ==== */

    public function getModuleVisibility(array $keys): array {
        global $pdo;
        if (empty($keys)) return [];
        $place = implode(',', array_fill(0, count($keys), '?'));
        $sql = "SELECT module_key,
               CONVERT(INT, show_module) AS show_module,
        FROM [module_toggle]
        WHERE module_key IN ($place)";
        $st  = $pdo->prepare($sql);
        $st->execute($keys);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        $map  = [];
        foreach ($rows as $r) {
            $map[$r['module_key']] = ((int)$r['show_module'] === 1);
        }
        // Por defecto, visible
        foreach ($keys as $k) {
            if (!array_key_exists($k, $map)) $map[$k] = true;
        }
        return $map;
    }

    public function setModuleVisible(string $key, bool $visible): void {
        global $pdo;
        $key = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $key)); // saneo
        $val = $visible ? 1 : 0;
        $sql = "
            MERGE [module_toggle] AS t
            USING (SELECT ? AS module_key, ? AS show_module) AS s
            ON (t.module_key = s.module_key)
            WHEN MATCHED THEN UPDATE
                SET t.show_module = s.show_module,
                    t.updated_at  = SYSDATETIME()
            WHEN NOT MATCHED THEN
                INSERT (module_key, show_module, created_at, updated_at)
                VALUES (s.module_key, s.show_module, SYSDATETIME(), SYSDATETIME());
        ";
        $st = $pdo->prepare($sql);
        $st->execute([$key, $val]);
    }

    public function setModuleVisibleBatch(array $keyToVisible): void {
        global $pdo;
        $pdo->beginTransaction();
        try {
            foreach ($keyToVisible as $k => $v) {
                $this->setModuleVisible((string)$k, (bool)$v);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function saveLegal(int $status, string $titleEs, string $contentEs, string $titleEu, string $contentEu): int {
        global $pdo;

        $pdo->beginTransaction();
        try {
            $sql1 = "INSERT INTO [admin_legal] ([status], [created_at], [updated_at])
                     VALUES (?, SYSDATETIME(), SYSDATETIME())";
            $st1 = $pdo->prepare($sql1);
            $st1->execute([$status]);
            $adminLegalId = (int)$pdo->query("SELECT CAST(SCOPE_IDENTITY() AS INT)")->fetchColumn();

            $sql2 = "INSERT INTO [link] ([id_admin_legal]) VALUES (?)";
            $st2 = $pdo->prepare($sql2);
            $st2->execute([$adminLegalId]);
            $linkId = (int)$pdo->query("SELECT CAST(SCOPE_IDENTITY() AS INT)")->fetchColumn();

            $sqlT = "INSERT INTO [translation] (id_link, lang, title, content) VALUES (?,?,?,?)";
            $tp = $pdo->prepare($sqlT);
            $tp->execute([$linkId, 'es', $titleEs, $contentEs]);
            $tp->execute([$linkId, 'eu', $titleEu, $contentEu]);

            $pdo->commit();
            return $adminLegalId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function getModuleFlags(array $keys): array {
        global $pdo;
        if (empty($keys)) return [];
        $place = implode(',', array_fill(0, count($keys), '?'));
        $sql = "SELECT module_key, CONVERT(INT, show_module) AS show_module
                FROM [module_toggle]
                WHERE module_key IN ($place)";
        $st  = $pdo->prepare($sql);
        $st->execute($keys);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);

        // por defecto: true
        $out = [];
        foreach ($keys as $k) $out[$k] = true;
        foreach ($rows as $r) $out[(string)$r['module_key']] = ((int)$r['show_module'] === 1);
        return $out;
    }

}
