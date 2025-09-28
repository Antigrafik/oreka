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
        $st->execute([$lang, $fallback, $lang]); // ODBC usa ? posicionales
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ==== LEGAL ==== */

    /**
     * Devuelve la última fila (por id DESC) de admin_legal + link.
     * Si no existe, crea un registro base publicado y su link.
     */
    public function getLatestLegalIds(): array {
        global $pdo;

        $row = $pdo->query("
            SELECT TOP (1) al.id AS admin_legal_id, ISNULL(al.status,0) AS status, l.id AS link_id
            FROM [admin_legal] al
            JOIN [link] l ON l.id_admin_legal = al.id
            ORDER BY al.id DESC
        ")->fetch(PDO::FETCH_ASSOC);

        if ($row) return $row;

        // No hay aún: creamos base mínima
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

    /**
     * Carga la última legal con traducciones ES/EU para precargar el editor.
     * Estructura: ['status'=>int, 'es'=>['title','content'], 'eu'=>['title','content'], 'admin_legal_id'=>int, 'link_id'=>int]
     */
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

    /**
     * Guarda "in-place": actualiza status y hace UPSERT de traducciones ES/EU.
     */
    public function saveLegalUpdate(int $status, string $titleEs, string $contentEs, string $titleEu, string $contentEu): void {
        global $pdo;

        $ids    = $this->getLatestLegalIds();
        $adminId = (int)$ids['admin_legal_id'];
        $linkId  = (int)$ids['link_id'];

        $pdo->beginTransaction();
        try {
            // Actualiza estado
            $up = $pdo->prepare("UPDATE [admin_legal] SET [status] = ?, [updated_at] = SYSDATETIME() WHERE id = ?");
            $up->execute([$status, $adminId]);

            // UPSERT de ES y EU (SQL Server MERGE)
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

    /* (Opcional) Mantén tu método de versionado si lo quieres usar en el futuro */
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
}
