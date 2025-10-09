<?php
require_once __DIR__ . '/../models/CommunityRoutinesAdmin.php';

class CommunityRoutinesAdminController
{
    public function updatePoints(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_POST['__action__'] ?? '') !== 'routines_update_points') return;

        global $pdo;

        // En UI es plural; en DB es singular
        $moduleKey = trim((string)($_POST['module_key'] ?? ''));
        $dbModule  = 'routine';
        $points    = (int)($_POST['points'] ?? -1);

        try {
            if ($moduleKey !== 'routines') {
                throw new RuntimeException('Módulo inválido.');
            }
            if ($points < 0) {
                throw new RuntimeException('Los puntos deben ser un número >= 0.');
            }

            $pdo->beginTransaction();

            // UPDATE de la fila vigente (sin crear histórico)
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
            $_SESSION['flash_success_admin'] = 'Puntos actualizados para Rutinas.';
        } catch (Throwable $e) {
            if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_error_admin'] = 'No se pudieron actualizar los puntos: ' . $e->getMessage();
        }

        // Redirección limpia al ancla del módulo
        $base = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $base . '#admin/community/routines');
        exit;
    }

    public function addCategory(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_POST['__action__'] ?? '') !== 'routines_add_category') return;

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

            // 1) category
            $insCat = $pdo->prepare("
            INSERT INTO dbo.category (entity_type, status, created_at, updated_at)
            OUTPUT INSERTED.id
            VALUES ('routine', N'publicado', SYSDATETIME(), SYSDATETIME())
            ");
            $insCat->execute();
            $newCatId = (int)$insCat->fetchColumn();
            if ($newCatId <= 0) throw new RuntimeException('No se pudo crear la categoría.');

            // 2) relación con padre (26=Indoor, 27=Outdoor)
            $rel = $pdo->prepare("INSERT INTO dbo.category_relation (id_parent, id_child) VALUES (?, ?)");
            $rel->execute([$parentId, $newCatId]);

            // 3) traducciones es/eu
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
        header('Location: ' . $base . '#admin/community/routines');
        exit;
    }

    public function softDelete(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_POST['__action__'] ?? '') !== 'routines_soft_delete') return;

        global $pdo;

        $catId = (int)($_POST['category_id'] ?? 0);

        try {
            if ($catId <= 0) throw new RuntimeException('ID inválido.');

            $st = $pdo->prepare("
                UPDATE dbo.category
                SET status = N'eliminado',
                    updated_at = SYSDATETIME()
                WHERE id = ?
            ");
            $st->execute([$catId]);

            // Verificación rápida
            $chk = $pdo->prepare("SELECT status FROM dbo.category WHERE id = ?");
            $chk->execute([$catId]);
            if ($chk->fetchColumn() !== 'eliminado') {
                throw new RuntimeException('No se pudo cambiar el status.');
            }

            $_SESSION['flash_success_admin'] = 'Elemento marcado como eliminado.';
        } catch (Throwable $e) {
            $_SESSION['flash_error_admin'] = 'No se pudo eliminar: ' . $e->getMessage();
        }

        $base = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $base . '#admin/community/routines');
        exit;
    }

}
