<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$root = dirname(__DIR__, 2); // private/cron -> private
require_once $root . '/config/db_connect.php'; // Debe crear $pdo (PDO SQL Server)

$jsonPath = __DIR__ . '/sql_agent_learn.json';
if (!is_readable($jsonPath)) {
    http_response_code(500);
    die("No se puede leer el JSON en: {$jsonPath}\n");
}

$raw = file_get_contents($jsonPath);
$data = json_decode($raw, true);
if (!is_array($data) || !isset($data['contents']) || !is_array($data['contents'])) {
    http_response_code(400);
    die("Formato JSON no válido: se esperaba 'contents' como array.\n");
}

// Helpers
function b2i($v): int { return ($v === true || $v === 1 || $v === '1') ? 1 : 0; }
function nv($v, $default=null) { return isset($v) ? $v : $default; }

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    $pdo->beginTransaction();

    // LEARN
    $stmtFindLearnByIdentifier = $pdo->prepare("SELECT TOP 1 id FROM learn WHERE identifier = :identifier");
    $stmtFindLearnByCmid       = $pdo->prepare("SELECT TOP 1 id FROM learn WHERE cmid = :cmid");

    // INSERT con OUTPUT INSERTED.id para obtener el id en SQL Server
    $sqlInsertLearn = "
        INSERT INTO learn ([status], duration, cmid, identifier, timecreated, timemodified, visible, module_url, otherlangidentifier, image_url)
        OUTPUT INSERTED.id
        VALUES (:status, :duration, :cmid, :identifier, :timecreated, :timemodified, :visible, :module_url, :otherlangidentifier, :image_url)
    ";
    $stmtInsertLearn = $pdo->prepare($sqlInsertLearn);

    $stmtUpdateLearn = $pdo->prepare("
        UPDATE learn
           SET [status] = :status,
               duration = :duration,
               cmid = :cmid,
               timecreated = :timecreated,
               timemodified = :timemodified,
               visible = :visible,
               module_url = :module_url,
               otherlangidentifier = :otherlangidentifier,
               image_url = :image_url
         WHERE id = :id
    ");

    // LINK
    $stmtFindLinkByLearnId = $pdo->prepare("SELECT TOP 1 id FROM [link] WHERE id_learn = :id_learn");
    $stmtInsertLink = $pdo->prepare("
        INSERT INTO [link] (id_learn)
        OUTPUT INSERTED.id
        VALUES (:id_learn)
    ");

    // TRANSLATION
    $stmtFindTranslation = $pdo->prepare("
        SELECT TOP 1 id FROM translation WHERE id_link = :id_link AND lang = :lang
    ");
    $stmtInsertTranslation = $pdo->prepare("
        INSERT INTO translation (id_link, lang, title, content)
        VALUES (:id_link, :lang, :title, :content)
    ");
    $stmtUpdateTranslation = $pdo->prepare("
        UPDATE translation SET title = :title, content = :content WHERE id = :id
    ");

    $inserted = 0; $updated = 0; $t_insert = 0; $t_update = 0;

    foreach ($data['contents'] as $row) {
        $identifier          = (string) nv($row['identifier'], '');
        $cmid                = (int) nv($row['cmid'], 0);
        $status              = (string) nv($row['status'], 'active');
        $duration            = (int) nv($row['duration'], 0);
        $timecreated         = (int) nv($row['timecreated'], time());
        $timemodified        = (int) nv($row['timemodified'], time());
        $visible             = b2i(nv($row['visible'], 1));
        $module_url          = (string) nv($row['module_url'], '');
        $otherlangidentifier = (string) nv($row['otherlangidentifier'], '');
        $image_url           = (string) nv($row['image_url'], '');

        // Buscar LEARN por identifier (preferente) o cmid
        $learnId = null;
        if ($identifier !== '') {
            $stmtFindLearnByIdentifier->execute([':identifier' => $identifier]);
            $learnId = $stmtFindLearnByIdentifier->fetchColumn() ?: null;
        }
        if (!$learnId && $cmid > 0) {
            $stmtFindLearnByCmid->execute([':cmid' => $cmid]);
            $learnId = $stmtFindLearnByCmid->fetchColumn() ?: null;
        }

        if ($learnId) {
            $stmtUpdateLearn->execute([
                ':status' => $status,
                ':duration' => $duration,
                ':cmid' => $cmid,
                ':timecreated' => $timecreated,
                ':timemodified' => $timemodified,
                ':visible' => $visible,
                ':module_url' => $module_url,
                ':otherlangidentifier' => $otherlangidentifier,
                ':image_url' => $image_url,
                ':id' => $learnId,
            ]);
            $updated++;
        } else {
            $stmtInsertLearn->execute([
                ':status' => $status,
                ':duration' => $duration,
                ':cmid' => $cmid,
                ':identifier' => $identifier,
                ':timecreated' => $timecreated,
                ':timemodified' => $timemodified,
                ':visible' => $visible,
                ':module_url' => $module_url,
                ':otherlangidentifier' => $otherlangidentifier,
                ':image_url' => $image_url,
            ]);
            $learnId = (int)$stmtInsertLearn->fetchColumn(); // OUTPUT INSERTED.id
            $inserted++;
        }

        // Asegurar LINK
        $stmtFindLinkByLearnId->execute([':id_learn' => $learnId]);
        $linkId = $stmtFindLinkByLearnId->fetchColumn();
        if (!$linkId) {
            $stmtInsertLink->execute([':id_learn' => $learnId]);
            $linkId = (int)$stmtInsertLink->fetchColumn(); // OUTPUT INSERTED.id
        } else {
            $linkId = (int)$linkId;
        }

        // Upsert TRANSLATION
        $lang    = (string) nv($row['language'], '');
        $title   = (string) nv($row['module_name'], '');
        $content = '';

        if ($lang !== '') {
            $stmtFindTranslation->execute([':id_link' => $linkId, ':lang' => $lang]);
            $trId = $stmtFindTranslation->fetchColumn();

            if ($trId) {
                $stmtUpdateTranslation->execute([
                    ':title' => $title,
                    ':content' => $content,
                    ':id' => $trId,
                ]);
                $t_update++;
            } else {
                $stmtInsertTranslation->execute([
                    ':id_link' => $linkId,
                    ':lang' => $lang,
                    ':title' => $title,
                    ':content' => $content,
                ]);
                $t_insert++;
            }
        }
    }

    $pdo->commit();
    echo "OK\n";
    echo "LEARN → insertados: {$inserted}, actualizados: {$updated}\n";
    echo "TRANSLATION → insertados: {$t_insert}, actualizados: {$t_update}\n";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
