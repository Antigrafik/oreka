<?php
require_once __DIR__ . '/../models/CommunityTrialAdmin.php';

class CommunityTrialAdminController
{
    public function updatePoints(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_POST['__action__'] ?? '') !== 'trial_update_points') return;

        global $pdo;

        $module = trim((string)($_POST['module_key'] ?? ''));
        $points = (int)($_POST['points'] ?? -1);

        try {
            if ($module !== 'trial') {
                throw new RuntimeException('Módulo inválido.');
            }
            if ($points < 0) {
                throw new RuntimeException('Los puntos deben ser un número >= 0.');
            }

            $pdo->beginTransaction();

            // UPDATE de la fila vigente (sin crear histórico)
            $up = $pdo->prepare("
                UPDATE dbo.point_modules
                   SET points = ?, created_at = SYSDATETIME()  -- usa updated_at si la tienes
                 WHERE module_code = 'trial' AND effective_to IS NULL
            ");
            $up->execute([$points]);

            // Si no existe fila vigente, crearla
            if ($up->rowCount() === 0) {
                $ins = $pdo->prepare("
                    INSERT INTO dbo.point_modules (module_code, points, effective_from, created_at)
                    VALUES ('trial', ?, SYSDATETIME(), SYSDATETIME())
                ");
                $ins->execute([$points]);
            }

            $pdo->commit();
            $_SESSION['flash_success_admin'] = 'Puntos actualizados para Pruebas.';
        } catch (Throwable $e) {
            if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_error_admin'] = 'No se pudieron actualizar los puntos: ' . $e->getMessage();
        }

        // Redirección limpia al ancla del módulo
        $base = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $base . '#admin/community/trial');
        exit;
    }
}
