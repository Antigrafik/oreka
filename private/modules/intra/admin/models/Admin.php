<?php

class Admin
{
    /* ==== EXISTENTE: listado para Aula (no tocar) ==== */
    public function getAll(string $lang = 'eu', string $fallback = 'es'): array {
        global $pdo;

        $sql = "
            SELECT
                l.id,
                COALESCE(MAX(ct.name),  MAX(ctf.name))  AS category_name,
                COALESCE(MAX(ct.slug),  MAX(ctf.slug))  AS category_slug,
                COALESCE(MAX(t.[title]),
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
                   ON ct.id_category = c.id AND ct.lang  = ?
            LEFT JOIN category_translation AS ctf
                   ON ctf.id_category = c.id AND ctf.lang = ?
            LEFT JOIN translation     AS t   ON t.id_link   = lk.id AND t.lang = ?
            WHERE ISNULL(l.status,'active') <> 'hidden'
            GROUP BY l.id, l.url, l.duration, l.status
            ORDER BY l.id DESC";

        $st = $pdo->prepare($sql);
        $st->execute([$lang, $fallback, $lang]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ==== TOGGLES DE MÓDULO ==== */

    public function getModuleVisibility(array $keys): array {
        global $pdo;
        if (empty($keys)) return [];
        $place = implode(',', array_fill(0, count($keys), '?'));
        $sql = "SELECT module_key, CONVERT(INT, show_module) AS show_module
                FROM [module_toggle]
                WHERE module_key IN ($place)";
        $st  = $pdo->prepare($sql);
        $st->execute($keys);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        $map  = [];
        foreach ($rows as $r) $map[$r['module_key']] = ((int)$r['show_module'] === 1);
        foreach ($keys as $k) if (!array_key_exists($k, $map)) $map[$k] = true; // por defecto visible
        return $map;
    }

    public function setModuleVisible(string $key, bool $visible): void {
        global $pdo;
        $key = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $key));
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
}
