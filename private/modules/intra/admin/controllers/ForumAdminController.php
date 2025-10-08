<?php
require_once __DIR__ . '/../models/ForumAdmin.php';

class ForumAdminController
{
    public function save(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_POST['__action__'] ?? '') !== 'forum_save') return;

        $adm = new ForumAdmin();

        $id         = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
        $dateStart  = $_POST['date_start']  ?? '';
        $dateFinish = $_POST['date_finish'] ?? '';
        $url        = trim((string)($_POST['url'] ?? ''));

        $titleEs    = $_POST['title_es']   ?? '';
        $contentEs  = $_POST['content_es'] ?? '';
        $titleEu    = $_POST['title_eu']   ?? '';
        $contentEu  = $_POST['content_eu'] ?? '';

        $mode   = $_POST['mode'] ?? 'draft'; // 'draft' | 'schedule'
        $status = ($mode === 'schedule') ? 'scheduled' : 'draft';

        try {
            // Normaliza fechas → 'Y-m-d H:i:s' (acepta 'YYYY-mm-ddTHH:ii' del input)
            if ($dateStart === '' || $dateFinish === '') {
                throw new RuntimeException('Faltan fechas de inicio y/o fin.');
            }
            $norm = function (?string $s): string {
                $s = trim((string)$s);
                if ($s === '') return '';
                $dt = DateTime::createFromFormat('Y-m-d\TH:i', $s) ?: DateTime::createFromFormat('Y-m-d H:i', $s) ?: date_create($s);
                if (!$dt) throw new RuntimeException("Formato de fecha/hora inválido: $s");
                return $dt->format('Y-m-d H:i:s');
            };
            $dateStartSql  = $norm($dateStart);
            $dateFinishSql = $norm($dateFinish);
            if (strtotime($dateStartSql) >= strtotime($dateFinishSql)) {
                throw new RuntimeException('La fecha/hora de inicio debe ser anterior a la de fin.');
            }

            // Valida textos
            $clean = function($html){
                $txt = strip_tags((string)$html);
                $txt = preg_replace('/\x{00A0}/u', ' ', $txt);
                return trim(preg_replace('/\s+/u', ' ', $txt));
            };
            if ($clean($titleEs) === '' || $clean($contentEs) === '' || $clean($titleEu) === '' || $clean($contentEu) === '') {
                throw new RuntimeException('Rellena título y contenido en Español y Euskera.');
            }

            // URL opcional, pero si viene debe ser http(s)
            if ($url !== '' && !preg_match('~^https?://~i', $url)) {
                throw new RuntimeException('La URL debe comenzar por http:// o https://');
            }

            // (opcional) bloquear solapes desde servidor
            // $over = $adm->findOverlappingActivity($dateStartSql, $dateFinishSql, $id);
            // if ($over) throw new RuntimeException("Ya existe una actividad en ese rango (#{$over['id']}).");

            // Guardar
            $adm->saveForum(
                $id,
                $dateStartSql, $dateFinishSql, ($url !== '' ? $url : null),
                $titleEs, $contentEs, $titleEu, $contentEu,
                $status
            );

            $_SESSION['flash_success_admin'] =
                $status === 'scheduled'
                    ? ($id ? 'Actividad programada correctamente.' : 'Actividad creada y programada.')
                    : ($id ? 'Actividad guardada como borrador.'  : 'Actividad creada como borrador.');

        } catch (Throwable $e) {
            $_SESSION['flash_error_admin'] = 'Error al guardar: ' . $e->getMessage();
        }

        // Redirección limpia (sin ?edit=)
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
        header('Location: ' . $scheme . '://' . $host . $path . '#admin/forum');
        exit;
    }

    public function delete(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_POST['__action__'] ?? '') !== 'forum_delete') return;

        try {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id > 0) {
                (new ForumAdmin())->deleteForum($id);
                $_SESSION['flash_success_admin'] = 'Actividad eliminada.';
            }
        } catch (Throwable $e) {
            $_SESSION['flash_error_admin'] = 'Error al eliminar: ' . $e->getMessage();
        }

        $base = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $base . '#admin/forum');
        exit;
    }

    public function updatePoints(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }
        if (($_POST['__action__'] ?? '') !== 'forum_update_points') return;

        global $pdo;

        $module = trim((string)($_POST['module_key'] ?? ''));
        $points = (int)($_POST['points'] ?? -1);

        try {
            if (!in_array($module, ['forum','learn','recommendation','routine','trial','meeting'], true)) {
                throw new RuntimeException('Módulo inválido.');
            }
            if ($points < 0) {
                throw new RuntimeException('Los puntos deben ser un número >= 0.');
            }

            $pdo->beginTransaction();

            // 1) Intentar actualizar la fila vigente (sin crear nueva)
            $up = $pdo->prepare("
                UPDATE dbo.point_modules
                SET points = ?, created_at = SYSDATETIME()  -- o updated_at si tienes esa columna
                WHERE module_code = ? AND effective_to IS NULL
            ");
            $up->execute([$points, $module]);

            // 2) Si no existe fila vigente, la creamos (caso inicial)
            if ($up->rowCount() === 0) {
                $ins = $pdo->prepare("
                    INSERT INTO dbo.point_modules (module_code, points, effective_from, created_at)
                    VALUES (?, ?, SYSDATETIME(), SYSDATETIME())
                ");
                $ins->execute([$module, $points]);
            }

            $pdo->commit();
            $_SESSION['flash_success_admin'] = 'Puntos actualizados para el módulo ' . $module . '.';
        } catch (Throwable $e) {
            if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
            $_SESSION['flash_error_admin'] = 'No se pudieron actualizar los puntos: ' . $e->getMessage();
        }

        $base = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $base . '#admin/forum');
        exit;
    }



}
