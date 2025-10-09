<?php
require_once __DIR__ . '/../models/CommunityRecommendationsAdmin.php';

class CommunityRecommendationsAdminController
{
    public function updatePoints(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_POST['__action__'] ?? '') !== 'recommendations_update_points') return;

        global $pdo;

        // En UI el module_key es plural, en DB el module_code es singular
        $moduleKey = trim((string)($_POST['module_key'] ?? ''));
        $dbModule  = 'recommendation'; // <--- clave usada en point_modules
        $points    = (int)($_POST['points'] ?? -1);

        try {
            if ($moduleKey !== 'recommendations') {
                throw new RuntimeException('Módulo inválido.');
            }
            if ($points < 0) {
                throw new RuntimeException('Los puntos deben ser un número >= 0.');
            }

            $pdo->beginTransaction();

            // Actualizar la fila vigente (sin crear histórico)
            $up = $pdo->prepare("
                UPDATE dbo.point_modules
                   SET points = ?, created_at = SYSDATETIME() -- o updated_at si la tienes
                 WHERE module_code = ? AND effective_to IS NULL
            ");
            $up->execute([$points, $dbModule]);

            // Si no existe fila vigente, crearla
            if ($up->rowCount() === 0) {
                $ins = $pdo->prepare("
                    INSERT INTO dbo.point_modules (module_code, points, effective_from, created_at)
                    VALUES (?, ?, SYSDATETIME(), SYSDATETIME())
                ");
                $ins->execute([$dbModule, $points]);
            }

            $pdo->commit();
            $_SESSION['flash_success_admin'] = 'Puntos actualizados para Recomendaciones.';
        } catch (Throwable $e) {
            if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_error_admin'] = 'No se pudieron actualizar los puntos: ' . $e->getMessage();
        }

        // Redirección limpia al ancla del módulo
        $base = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $base . '#admin/community/recommendations');
        exit;
    }

    public function addCategory(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_POST['__action__'] ?? '') !== 'recommendations_add_category') return;

        global $pdo;

        $parentId = (int)($_POST['parent_id'] ?? 0);
        $nameEs   = trim((string)($_POST['name_es'] ?? ''));
        $nameEu   = trim((string)($_POST['name_eu'] ?? ''));

        try {
            if ($parentId <= 0) throw new RuntimeException('Padre inválido.');
            if ($nameEs === '' || $nameEu === '') throw new RuntimeException('Ambos idiomas son obligatorios.');

            $slugify = function (string $s): string {
                $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
                $s = preg_replace('~[^a-zA-Z0-9]+~', '-', $s);
                $s = trim($s, '-');
                return strtolower($s ?: 'item');
            };

            $pdo->beginTransaction();

            // 1) Crear categoría hija y recuperar su ID (SQL Server)
            $insCat = $pdo->prepare("
                INSERT INTO dbo.category (entity_type, status, created_at, updated_at)
                OUTPUT INSERTED.id
                VALUES ('recommendation', 'publicado', SYSDATETIME(), SYSDATETIME())
            ");
            $insCat->execute();
            $newCatId = (int)$insCat->fetchColumn();
            if ($newCatId <= 0) throw new RuntimeException('No se pudo crear la categoría.');

            // 2) Relacionar con su padre
            $rel = $pdo->prepare("INSERT INTO dbo.category_relation (id_parent, id_child) VALUES (?, ?)");
            $rel->execute([$parentId, $newCatId]);

            // 3) Traducciones ES y EU
            $insTr = $pdo->prepare("
                INSERT INTO dbo.category_translation (id_category, lang, name, description, slug)
                VALUES (?, ?, ?, NULL, ?)
            ");
            $insTr->execute([$newCatId, 'es', $nameEs, $slugify($nameEs)]);
            $insTr->execute([$newCatId, 'eu', $nameEu, $slugify($nameEu)]);

            $pdo->commit();
            $_SESSION['flash_success_admin'] = 'Elemento añadido correctamente.';
        } catch (Throwable $e) {
            if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_error_admin'] = 'No se pudo añadir: ' . $e->getMessage();
        }

        $base = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $base . '#admin/community/recommendations');
        exit;
    }

    public function softDelete(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_POST['__action__'] ?? '') !== 'recommendations_soft_delete') return;

        global $pdo;

        $catId = (int)($_POST['category_id'] ?? 0);

        try {
            if ($catId <= 0) throw new RuntimeException('ID inválido.');

            // IMPORTANTE en SQL Server: usa N'...' para NVARCHAR
            $pdo->beginTransaction();

            $upd = $pdo->prepare("
                UPDATE dbo.category
                SET status = N'eliminado',
                    updated_at = SYSDATETIME()
                WHERE id = ?
            ");
            $upd->execute([$catId]);

            // Verificación directa (NO confiar en rowCount con SQL Server)
            $chk = $pdo->prepare("SELECT status FROM dbo.category WHERE id = ?");
            $chk->execute([$catId]);
            $newStatus = $chk->fetchColumn();

            if ($newStatus !== 'eliminado') {
                throw new RuntimeException('No se pudo actualizar el estado (status actual: ' . (string)$newStatus . ').');
            }

            $pdo->commit();
            $_SESSION['flash_success_admin'] = 'Elemento marcado como eliminado.';
        } catch (Throwable $e) {
            if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_error_admin'] = 'Error al eliminar: ' . $e->getMessage();
        }

        $base = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $base . '#admin/community/recommendations');
        exit;
    }

    public function toggleRecStatus(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_POST['__action__'] ?? '') !== 'recommendations_toggle_status') return;

        global $pdo;
        $recId = (int)($_POST['rec_id'] ?? 0);

        $payload = ['ok' => false];
        try {
            if ($recId <= 0) throw new RuntimeException('ID inválido.');

            $pdo->beginTransaction();
            $st = $pdo->prepare("
                UPDATE dbo.recommendation
                SET [status]  = CASE WHEN [status] = N'publicado' THEN N'borrador' ELSE N'publicado' END,
                    updated_at = SYSDATETIME()
                WHERE id = ?
            ");
            $st->execute([$recId]);
            $pdo->commit();

            $payload['ok'] = true;
        } catch (Throwable $e) {
            if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
            $payload['error'] = $e->getMessage();
        }

        // --- Respuesta ---
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') === 0;

        if ($isAjax) {
            // Limpia cualquier salida previa para no corromper el JSON
            while (function_exists('ob_get_level') && ob_get_level() > 0) { ob_end_clean(); }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'    => (bool)($payload['ok'] ?? false),
                'error' => $payload['error'] ?? null,
            ]);
            exit;
        }

        // No-AJAX: flash + redirect
        $_SESSION[ !empty($payload['ok']) ? 'flash_success_admin' : 'flash_error_admin' ]
            = !empty($payload['ok'])
                ? 'Estado actualizado.'
                : ('No se pudo actualizar: ' . ($payload['error'] ?? ''));

        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }

    public function hardDeleteRecommendation(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_POST['__action__'] ?? '') !== 'recommendations_hard_delete') return;

        global $pdo;
        $recId = (int)($_POST['rec_id'] ?? 0);

        $payload = ['ok' => false];
        try {
            if ($recId <= 0) throw new RuntimeException('ID inválido.');

            // Obtener link.id del recommendation
            $st = $pdo->prepare("SELECT id FROM dbo.[link] WHERE id_recommendation = ?");
            $st->execute([$recId]);
            $linkId = (int)($st->fetchColumn() ?: 0);

            $pdo->beginTransaction();

            if ($linkId > 0) {
                // 1) user_activity dependiente de point
                $delUA = $pdo->prepare("
                    DELETE ua
                    FROM dbo.user_activity ua
                    JOIN dbo.point p ON p.id = ua.id_point
                    WHERE p.id_link = ?
                ");
                $delUA->execute([$linkId]);

                // 2) points
                $delP = $pdo->prepare("DELETE FROM dbo.point WHERE id_link = ?");
                $delP->execute([$linkId]);

                // 3) category_link
                $delCL = $pdo->prepare("DELETE FROM dbo.category_link WHERE id_link = ?");
                $delCL->execute([$linkId]);

                // 4) translation
                $delTr = $pdo->prepare("DELETE FROM dbo.translation WHERE id_link = ?");
                $delTr->execute([$linkId]);

                // 5) link
                $delL = $pdo->prepare("DELETE FROM dbo.[link] WHERE id = ?");
                $delL->execute([$linkId]);
            }

            // 6) recommendation
            $delR = $pdo->prepare("DELETE FROM dbo.recommendation WHERE id = ?");
            $delR->execute([$recId]);

            $pdo->commit();
            $payload['ok'] = true;
        } catch (Throwable $e) {
            if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
            $payload['error'] = $e->getMessage();
        }

        // --- Respuesta ---
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strcasecmp($_SERVER['HTTP_X_REQUESTED_WITH'], 'XMLHttpRequest') === 0;

        if ($isAjax) {
            // Limpia cualquier salida previa para no corromper el JSON
            while (function_exists('ob_get_level') && ob_get_level() > 0) { ob_end_clean(); }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok'    => (bool)($payload['ok'] ?? false),
                'error' => $payload['error'] ?? null,
            ]);
            exit;
        }

        // No-AJAX: flash + redirect
        $_SESSION[ !empty($payload['ok']) ? 'flash_success_admin' : 'flash_error_admin' ]
            = !empty($payload['ok'])
                ? 'Recomendación eliminada.'
                : ('No se pudo eliminar: ' . ($payload['error'] ?? ''));

        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }

}
