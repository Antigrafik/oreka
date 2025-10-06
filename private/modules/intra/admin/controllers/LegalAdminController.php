<?php
require_once __DIR__ . '/../models/LegalAdmin.php';

class LegalAdminController
{
    public function save(): void
    {
        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        if (($_POST['__action__'] ?? '') !== 'save_legal') return;

        $adm         = new LegalAdmin();
        $adminLegalId = isset($_POST['admin_legal_id']) ? (int)$_POST['admin_legal_id'] : 0;
        $linkId       = isset($_POST['link_id'])        ? (int)$_POST['link_id']        : 0;

        $titleEs    = $_POST['title_es']   ?? '';
        $contentEs  = $_POST['content_es'] ?? '';
        $titleEu    = $_POST['title_eu']   ?? '';
        $contentEu  = $_POST['content_eu'] ?? '';
        $mode       = $_POST['mode']       ?? 'save'; // save | publish

        try {
            if ($adminLegalId <= 0 || $linkId <= 0) {
                $ids = $adm->getLatestLegalIds();
                $adminLegalId = (int)($ids['admin_legal_id'] ?? 0);
                $linkId       = (int)($ids['link_id'] ?? 0);
            }
            if ($adminLegalId <= 0 || $linkId <= 0) {
                throw new RuntimeException('No hay registro Legal inicializado.');
            }

            $curr   = $adm->getLatestLegal();
            $status = ($mode === 'publish') ? 1 : (int)($curr['status'] ?? 0);

            $adm->saveLegalUpdateByIdKeepOnEmpty(
                $adminLegalId,
                $linkId,
                $status,
                (string)$titleEs, (string)$contentEs,
                (string)$titleEu, (string)$contentEu
            );

            $_SESSION['flash_success_admin'] = ($mode === 'publish')
                ? 'Legal publicado.'
                : 'Legal actualizado.';

        } catch (Throwable $e) {
            $_SESSION['flash_error_admin'] = 'Error al guardar: ' . $e->getMessage();
        }

        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '#') . '#admin/legal');
        exit;
    }
}
