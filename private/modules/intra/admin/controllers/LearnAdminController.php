<?php
require_once __DIR__ . '/../models/LearnAdmin.php';

class LearnAdminController
{
    public function updatePoints(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_POST['__action__'] ?? '') !== 'learn_update_points') return;

        global $pdo;

        $module = trim((string)($_POST['module_key'] ?? ''));
        $points = (int)($_POST['points'] ?? -1);

        try {
            if ($module !== 'learn') {
                throw new RuntimeException('Módulo inválido.');
            }
            if ($points < 0) {
                throw new RuntimeException('Los puntos deben ser un número >= 0.');
            }

            $pdo->beginTransaction();

            // Actualizar la fila vigente (sin crear nuevo id)
            $up = $pdo->prepare("
                UPDATE dbo.point_modules
                   SET points = ?, created_at = SYSDATETIME() -- o updated_at si la tienes
                 WHERE module_code = ? AND effective_to IS NULL
            ");
            $up->execute([$points, $module]);

            // Si no hay fila vigente (arranque), insertar una
            if ($up->rowCount() === 0) {
                $ins = $pdo->prepare("
                    INSERT INTO dbo.point_modules (module_code, points, effective_from, created_at)
                    VALUES (?, ?, SYSDATETIME(), SYSDATETIME())
                ");
                $ins->execute([$module, $points]);
            }

            $pdo->commit();
            $_SESSION['flash_success_admin'] = 'Puntos actualizados para el módulo learn.';
        } catch (Throwable $e) {
            if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_error_admin'] = 'No se pudieron actualizar los puntos: ' . $e->getMessage();
        }

        // Redirección limpia al ancla del módulo
        $base = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $base . '#admin/learn');
        exit;
    }
}
