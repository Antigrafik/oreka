<?php
require_once __DIR__ . '/../models/BannerAdmin.php';

class BannerAdminController
{
    public function save(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $action = $_POST['__action__'] ?? '';
        if ($action !== 'banner_save') return;

        $adm = new BannerAdmin();

        $id         = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : null;
        $type       = $_POST['type'] ?? 'ad';
        $isRaffle   = ($type === 'raffle');
        $prize      = $isRaffle ? ($_POST['prize'] ?? '') : null;
        $dateStart  = $_POST['date_start']  ?? '';
        $dateFinish = $_POST['date_finish'] ?? '';

        $titleEs    = $_POST['title_es']   ?? '';
        $contentEs  = $_POST['content_es'] ?? '';
        $titleEu    = $_POST['title_eu']   ?? '';
        $contentEu  = $_POST['content_eu'] ?? '';

        $mode   = $_POST['mode'] ?? 'draft'; // 'draft' | 'schedule'
        $status = ($mode === 'schedule') ? 'scheduled' : 'draft';

        try {
            // Fechas → 'Y-m-d H:i:s'
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

            // Validación sencilla de textos
            $clean = function($html){
                $txt = strip_tags((string)$html);
                $txt = preg_replace('/\x{00A0}/u', ' ', $txt);
                return trim(preg_replace('/\s+/u', ' ', $txt));
            };
            if ($clean($titleEs) === '' || $clean($contentEs) === '' || $clean($titleEu) === '' || $clean($contentEu) === '') {
                throw new RuntimeException('Rellena título y contenido en Español y Euskera.');
            }
            if ($isRaffle && $clean($prize) === '') {
                throw new RuntimeException('El premio es obligatorio para Sorteo.');
            }

            // Guardar
            $adm->saveBanner(
                $id, $isRaffle, $prize,
                $dateStartSql, $dateFinishSql,
                $titleEs, $contentEs, $titleEu, $contentEu,
                $status
            );

            $_SESSION['flash_success_admin'] =
                $status === 'scheduled'
                    ? ($id ? 'Banner programado correctamente.' : 'Banner creado y programado.')
                    : ($id ? 'Banner guardado como borrador.'  : 'Banner creado como borrador.');

        } catch (Throwable $e) {
            $_SESSION['flash_error_admin'] = 'Error al guardar: ' . $e->getMessage();
        }

        // Redirección limpia (sin ?edit=)
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
        header('Location: ' . $scheme . '://' . $host . $path . '#admin/banner');
        exit;
    }

    public function delete(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        $action = $_POST['__action__'] ?? '';
        if ($action !== 'banner_delete') return;

        try {
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            if ($id > 0) {
                (new BannerAdmin())->deleteBanner($id);
                $_SESSION['flash_success_admin'] = 'Banner eliminado.';
            }
        } catch (Throwable $e) {
            $_SESSION['flash_error_admin'] = 'Error al eliminar: ' . $e->getMessage();
        }

        $base = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $base . '#admin/banner');
        exit;
    }
}
