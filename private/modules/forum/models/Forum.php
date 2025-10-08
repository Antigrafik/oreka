<?php
require_once __DIR__ . '/../../../config/db_connect.php';

class Forum
{
    /**
     * @param string      $lang     Idioma principal (eu|es)
     * @param string|null $fallback Idioma reserva (si null, se calcula al vuelo)
     * @param int|null    $userId   Para marcar si el usuario ya está apuntado
     */
    public function getAll(string $lang = 'eu', ?string $fallback = null, ?int $userId = null): array
    {
        global $pdo;

        if ($fallback === null || $fallback === $lang) {
            $fallback = ($lang === 'es') ? 'eu' : 'es';
        }

        // Si no hay usuario logado, usamos 0 para que nunca coincida en la subconsulta
        $uid = $userId ?? 0;

        $sql = "
            SELECT 
                f.id,
                f.url,
                f.date_start,
                f.date_finish,
                lk.id AS link_id,
                COALESCE(ct.name, ctf.name)     AS category_name,
                COALESCE(t.title, tf.title)     AS forum_title,
                COALESCE(t.content, tf.content) AS description,
                CASE WHEN EXISTS (
                    SELECT 1
                    FROM link l2
                    LEFT JOIN point p2         ON p2.id_link = l2.id
                    LEFT JOIN user_activity ua ON ua.id_point = p2.id AND ua.id_user = ?
                    WHERE l2.id_forum = f.id
                    AND ua.status = 'en_proceso'
                ) THEN 1 ELSE 0 END AS joined_by_user
            FROM forum f
            LEFT JOIN link  AS lk  ON lk.id_forum = f.id
            LEFT JOIN category_link AS cl ON cl.id_link = lk.id
            LEFT JOIN category      AS c  ON c.id = cl.id_category

            LEFT JOIN category_translation AS ct
                ON ct.id_category = c.id AND ct.lang = ?
            LEFT JOIN category_translation AS ctf
                ON ctf.id_category = c.id AND ctf.lang = ?

            LEFT JOIN translation AS t
                ON t.id_link = lk.id AND t.lang = ?
            LEFT JOIN translation AS tf
                ON tf.id_link = lk.id AND tf.lang = ?

            WHERE f.status IN ('scheduled', 'running')
            AND (f.date_finish IS NULL OR f.date_finish > GETDATE())
            ORDER BY f.date_start ASC;
        ";

        $st = $pdo->prepare($sql);
        $st->execute([
            $uid,          // para la subconsulta EXISTS
            $lang,         // ct.lang
            $fallback,     // ctf.lang
            $lang,         // t.lang
            $fallback      // tf.lang
        ]);

        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Garantiza que existe un point para el link dado y devuelve su id.
     */
    public function ensurePointForLink(int $linkId): int
    {
        global $pdo;

        // ¿ya existe?
        $q = $pdo->prepare("SELECT TOP (1) id FROM point WHERE id_link = ?");
        $q->execute([$linkId]);
        $pid = $q->fetchColumn();
        if ($pid) return (int)$pid;

        // crear (points = 0 por defecto; ajusta si necesitas)
        $ins = $pdo->prepare("
            INSERT INTO point (id_link, points, created_at)
            OUTPUT INSERTED.id
            VALUES (?, 0, SYSDATETIME())
        ");
        $ins->execute([$linkId]);
        return (int)$ins->fetchColumn();
    }

    /**
     * Marca ‘Apuntado’ para un usuario en una actividad (forum.id).
     */
    public function join(int $forumId, int $userId): bool
    {
        global $pdo;

        if ($userId <= 0 || $forumId <= 0) return false;

        $pdo->beginTransaction();
        try {
            // 1) Link del foro
            $st = $pdo->prepare("SELECT id FROM link WHERE id_forum = ?");
            $st->execute([$forumId]);
            $linkId = $st->fetchColumn();
            if (!$linkId) {
                $ins = $pdo->prepare("INSERT INTO link (id_forum) OUTPUT INSERTED.id VALUES (?)");
                $ins->execute([$forumId]);
                $linkId = (int)$ins->fetchColumn();
            } else {
                $linkId = (int)$linkId;
            }

            // 2) ¿ya está apuntado este usuario a este foro?
            $st = $pdo->prepare("
            SELECT TOP (1) ua.id
            FROM point p
            INNER JOIN user_activity ua ON ua.id_point = p.id AND ua.id_user = ?
            WHERE p.id_link = ?
                AND ua.status = 'en_proceso'
            ");
            $st->execute([$userId, $linkId]);
            if ($st->fetchColumn()) { $pdo->commit(); return true; }

            // 3) Crear point
            $insP = $pdo->prepare("
            INSERT INTO point (id_link, points, created_at)
            OUTPUT INSERTED.id
            VALUES (?, 0, GETDATE())
            ");
            $insP->execute([$linkId]);
            $pointId = (int)$insP->fetchColumn();

            // 4) Crear user_activity con status 'en_proceso'
            $insUA = $pdo->prepare("
            INSERT INTO user_activity (id_user, id_point, status)
            VALUES (?, ?, 'en_proceso')
            ");
            $insUA->execute([$userId, $pointId]);

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            return false;
        }
    }

}
