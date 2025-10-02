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

    public function saveLegalUpdateById(int $adminId, int $linkId, int $status,
                                        string $titleEs, string $contentEs,
                                        string $titleEu, string $contentEu): void {
        global $pdo;
        if ($adminId <= 0 || $linkId <= 0) {
            throw new InvalidArgumentException('IDs de legal inválidos.');
        }

        $pdo->beginTransaction();
        try {
            // Actualiza la fila EXISTENTE de admin_legal
            $up = $pdo->prepare("UPDATE [admin_legal]
                                 SET [status] = ?, [updated_at] = SYSDATETIME()
                                 WHERE id = ?");
            $up->execute([$status, $adminId]);

            // Actualiza/crea traducciones sobre el MISMO link_id (sin crear link nuevo)
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
                   INSERT (id_link, lang, title, content)
                   VALUES (s.id_link, s.lang, s.title, s.content);
            ";
            $m = $pdo->prepare($mergeSql);
            $m->execute([$linkId, $titleEs, $contentEs, $linkId, $titleEu, $contentEu]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
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
            // Actualiza la fila existente de admin_legal (no crea nada)
            $up = $pdo->prepare("
                UPDATE dbo.[admin_legal]
                SET [status] = ?, [updated_at] = SYSDATETIME()
                WHERE id = ?
            ");
            $up->execute([$status, $adminId]);

            // MERGE de traducciones:
            //  - Si el valor recibido es cadena vacía, NO pisamos (usamos el valor actual t.title/t.content)
            //  - Sólo insertamos si al menos llega algo no vacío para ese idioma
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

        $out = [];
        foreach ($keys as $k) $out[$k] = true;
        foreach ($rows as $r) $out[(string)$r['module_key']] = ((int)$r['show_module'] === 1);
        return $out;
    }

    public function getBannerHistory(): array {
        global $pdo;
        $sql = "
        SELECT b.id, b.is_raffle, b.prize, b.date_start, b.date_finish, b.status, b.created_at, b.updated_at,
                es.title  AS title_es,
                eu.title  AS title_eu
        FROM banner b
        LEFT JOIN link l       ON l.id_banner = b.id
        LEFT JOIN translation es ON es.id_link = l.id AND es.lang = 'es'
        LEFT JOIN translation eu ON eu.id_link = l.id AND eu.lang = 'eu'
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

    public function saveBanner(?int $id, bool $isRaffle, ?string $prize, string $dateStart, string $dateFinish,
                            string $titleEs, string $contentEs, string $titleEu, string $contentEu): int {
        global $pdo;

        $pdo->beginTransaction();
        try {
            if ($id) {
                /* ---- UPDATE de banner existente ---- */
                $up = $pdo->prepare("
                    UPDATE banner
                    SET is_raffle = ?, prize = ?, date_start = ?, date_finish = ?, updated_at = SYSDATETIME()
                    WHERE id = ?
                ");
                $up->execute([$isRaffle ? 1 : 0, $isRaffle ? $prize : null, $dateStart, $dateFinish, $id]);

                // asegurar que existe su link
                $q = $pdo->prepare("SELECT id FROM dbo.[link] WHERE id_banner = ?");
                $q->execute([$id]);
                $linkId = $q->fetchColumn();
                if (!$linkId) {
                    $insL = $pdo->prepare("INSERT INTO dbo.[link] (id_banner) OUTPUT INSERTED.id VALUES (?)");
                    $insL->execute([$id]);
                    $linkId = (int)$insL->fetchColumn();
                }
            } else {
                /* ---- INSERT de banner nuevo + recuperar id con OUTPUT ---- */
                $insB = $pdo->prepare("
                    INSERT INTO dbo.banner (is_raffle, prize, date_start, date_finish, [status], created_at, updated_at)
                    OUTPUT INSERTED.id
                    VALUES (?, ?, ?, ?, 'draft', SYSDATETIME(), SYSDATETIME())");
                $insB->execute([$isRaffle ? 1 : 0, $isRaffle ? $prize : null, $dateStart, $dateFinish]);
                $id = (int)$insB->fetchColumn();

                // crear link para este banner y recuperar id_link de la misma forma
                $insL = $pdo->prepare("INSERT INTO dbo.[link] (id_banner) OUTPUT INSERTED.id VALUES (?)");
                $insL->execute([$id]);
                $linkId = (int)$insL->fetchColumn();
            }

            // MERGE de traducciones ES/EU sobre ese link
            $merge = $pdo->prepare("
            MERGE dbo.translation AS t
            USING (VALUES
                (?, 'es', ?, ?),
                (?, 'eu', ?, ?)
            ) AS s (id_link, lang, title, content)
            ON (t.id_link = s.id_link AND t.lang = s.lang)
            WHEN MATCHED THEN UPDATE SET title = s.title, content = s.content
            WHEN NOT MATCHED THEN INSERT (id_link, lang, title, content) VALUES (s.id_link, s.lang, s.title, s.content);");
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
