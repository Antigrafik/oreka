<?php
require_once __DIR__ . '/../models/Admin.php';

class AdminController {
    public function render(): string {

        if (session_status() === PHP_SESSION_NONE) { session_start(); }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['__action__'] ?? '';
            $adm = new Admin();

            try {
                if ($action === 'toggle_module') {
                    $key      = strtolower(preg_replace('/[^a-z0-9_-]/i', '', (string)($_POST['module_key'] ?? '')));
                    $visible  = isset($_POST['visible']) && (int)$_POST['visible'] === 1;
                    $redirect = (string)($_POST['redirect'] ?? 'dashboard');

                    if ($key !== '') {
                        $adm->setModuleVisible($key, $visible);
                        $_SESSION['flash_success_admin'] = 'Guardado.';
                    }
                    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '#') . '#admin/' . $redirect);
                    exit;
                }

                if ($action === 'toggle_modules_batch') {
                    $vis = $_POST['vis'] ?? []; // vis[key]=1
                    $map = [];
                    foreach ($vis as $k => $v) {
                        $map[strtolower(preg_replace('/[^a-z0-9_-]/i', '', (string)$k))] = ((int)$v === 1);
                    }
                    if (!empty($map)) {
                        $adm->setModuleVisibleBatch($map);
                        $_SESSION['flash_success_admin'] = 'Guardado.';
                    }
                    $redirect = (string)($_POST['redirect'] ?? 'community');
                    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '#') . '#admin/' . $redirect);
                    exit;
                }

                if ($action === 'save_legal') {
                    // IDs explícitos para NO crear nada nuevo
                    $adminLegalId = isset($_POST['admin_legal_id']) ? (int)$_POST['admin_legal_id'] : 0;
                    $linkId       = isset($_POST['link_id'])        ? (int)$_POST['link_id']        : 0;

                    $titleEs    = $_POST['title_es']   ?? '';
                    $contentEs  = $_POST['content_es'] ?? '';
                    $titleEu    = $_POST['title_eu']   ?? '';
                    $contentEu  = $_POST['content_eu'] ?? '';
                    $mode       = $_POST['mode']       ?? 'save'; // save | publish

                    try {
                        // IDs que vienen de la vista; si faltan, usa los últimos existentes (sin crear nuevos)
                        if ($adminLegalId <= 0 || $linkId <= 0) {
                            $ids = $adm->getLatestLegalIds();
                            $adminLegalId = (int)($ids['admin_legal_id'] ?? 0);
                            $linkId       = (int)($ids['link_id'] ?? 0);
                        }
                        if ($adminLegalId <= 0 || $linkId <= 0) {
                            throw new RuntimeException('No hay registro Legal inicializado.');
                        }

                        // status: sólo cambia a publicado si lo has pedido
                        $curr   = $adm->getLatestLegal();
                        $status = ($mode === 'publish') ? 1 : (int)($curr['status'] ?? 0);

                        // Actualización robusta: en SQL, los strings vacíos NO pisan el contenido existente
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

                if ($action === 'banner_save') {
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

                    // NUEVO: modo → estado persistido
                    // 'draft' => BORRADOR ; 'schedule' => PROGRAMADO
                    $mode   = $_POST['mode'] ?? 'draft'; // 'draft' | 'schedule'
                    $status = ($mode === 'schedule') ? 'scheduled' : 'draft';

                    try {
                        // 1) Normalización fechas (como ya hacías)
                        if ($dateStart === '' || $dateFinish === '') {
                            throw new RuntimeException('Faltan fechas de inicio y/o fin.');
                        }

                        $norm = function (?string $s): string {
                            $s = trim((string)$s);
                            if ($s === '') return '';
                            $dt = DateTime::createFromFormat('Y-m-d\TH:i', $s);
                            if (!$dt) $dt = DateTime::createFromFormat('Y-m-d H:i', $s);
                            if (!$dt) $dt = date_create($s);
                            if (!$dt) throw new RuntimeException("Formato de fecha/hora inválido: $s");
                            return $dt->format('Y-m-d H:i:s');
                        };
                        $dateStartSql  = $norm($dateStart);
                        $dateFinishSql = $norm($dateFinish);

                        if (strtotime($dateStartSql) >= strtotime($dateFinishSql)) {
                            throw new RuntimeException('La fecha/hora de inicio debe ser anterior a la de fin.');
                        }

                        // 2) Validación de textos (como ya hacías)
                        $clean = function($html){
                            $txt = strip_tags((string)$html);
                            $txt = preg_replace('/\x{00A0}/u', ' ', $txt);
                            $txt = trim(preg_replace('/\s+/u', ' ', $txt));
                            return $txt;
                        };
                        $tEs = $clean($titleEs);
                        $cEs = $clean($contentEs);
                        $tEu = $clean($titleEu);
                        $cEu = $clean($contentEu);
                        if ($tEs === '' || $cEs === '' || $tEu === '' || $cEu === '') {
                            throw new RuntimeException('Rellena título y contenido en Español y Euskera.');
                        }

                        // 3) Premio si es sorteo
                        if ($isRaffle) {
                            if ($clean($prize) === '') {
                                throw new RuntimeException('El premio es obligatorio para Sorteo.');
                            }
                        }

                        // 4) Guardado con estado NUEVO
                        $adm->saveBanner(
                            $id, $isRaffle, $prize,
                            $dateStartSql, $dateFinishSql,
                            $titleEs, $contentEs, $titleEu, $contentEu,
                            $status // ← IMPORTANTE: nuevo parámetro
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

                if ($action === 'banner_delete') {
                    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                    try {
                        if ($id > 0) {
                            $adm->deleteBanner($id);
                            $_SESSION['flash_success_admin'] = 'Banner eliminado.';
                        }
                    } catch (Throwable $e) {
                        $_SESSION['flash_error_admin'] = 'Error al eliminar: ' . $e->getMessage();
                    }
                    $base = strtok($_SERVER['REQUEST_URI'], '?');  // corta todo lo que haya tras '?'
                    header('Location: ' . $base . '#admin/banner');
                    exit;
                }

            } catch (Throwable $e) {
                $base = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                header('Location: ' . $base . '#admin/banner');
                exit;
            }
        }

        $lang     = defined('DEFAULT_LANG') ? strtolower(DEFAULT_LANG) : 'es';
        $fallback = 'es';

        $model  = new Admin();
        $admins = $model->getAll($lang, $fallback);

        $moduleFlags = $model->getModuleFlags([
            'learn','forum','community','store','legal','banner', 
            'recommendations','routines','trial','meeting'
        ]);

        $language = $GLOBALS['language'] ?? [];

        ob_start();
        include __DIR__ . '/../views/admin.php';
        return ob_get_clean();
    }
}
