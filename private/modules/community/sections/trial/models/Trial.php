<?php
require_once PRIVATE_PATH . '/config/db_connect.php';

// Cargar .env (limpio)
$envPathFile = BASE_PATH . '/.env';
if (is_readable($envPathFile)) {
    foreach (file($envPathFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        if ($k === '') continue;
        // Quitar comillas envolventes
        $v = trim($v, " \t\n\r\0\x0B\"'");
        putenv("$k=$v");
        $_ENV[$k] = $v;
        $_SERVER[$k] = $v;
    }
}

class Trial
{
  private array $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
  private int $maxBytes     = 5 * 1024 * 1024; // 5 MB
  private int $pointsAward  = 10;

  private string $storageDirAbs;

  public function __construct()
  {
    $dir = getenv('TRIAL_STORAGE_DIR');
    if ($dir === false || $dir === '') {
      throw new RuntimeException('TRIAL_STORAGE_DIR no está definida en el entorno.');
    }
    $this->storageDirAbs = rtrim($dir, "\\/");
  }

  public function currentUserId(): ?int {
    global $pdo;
    $username = $_SESSION['username'] ?? ($_SERVER['REMOTE_USER'] ?? null);
    if (!$username) return null;
    if (strpos($username, '\\') !== false) {
      $parts = explode('\\', $username);
      $username = end($parts);
    }
    $st = $pdo->prepare("SELECT id FROM [user] WHERE usuario = ?");
    $st->execute([$username]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['id'] : null;
  }

  /** Devuelve id de la categoría para trials (entity_type='trial') o null si no existe */
  private function getTrialCategoryId(): ?int {
    global $pdo;
    $st = $pdo->prepare("SELECT TOP 1 id FROM category WHERE entity_type = 'trial' ORDER BY id ASC");
    $st->execute();
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['id'] : null;
  }

  public function createTrialWithUpload(array $payload): array {
    global $pdo;

    $idUser  = (int)($payload['id_user'] ?? 0);
    $content = trim((string)($payload['content'] ?? ''));
    $file    = $payload['file'] ?? null;

    if ($idUser <= 0) return ['ok' => false, 'msg' => 'Usuario no identificado'];
    if ($content === '') return ['ok' => false, 'msg' => 'Describe tu prueba deportiva.'];

    // --- Validación de subida con detalle ---
    if (!$file || !isset($file['error'])) {
      return ['ok' => false, 'msg' => 'No se recibió ningún archivo.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
      $errMap = [
        UPLOAD_ERR_INI_SIZE   => 'El archivo supera el límite configurado en el servidor (upload_max_filesize).',
        UPLOAD_ERR_FORM_SIZE  => 'El archivo supera el límite del formulario (MAX_FILE_SIZE).',
        UPLOAD_ERR_PARTIAL    => 'El archivo se subió solo parcialmente.',
        UPLOAD_ERR_NO_FILE    => 'No se subió ningún archivo.',
        UPLOAD_ERR_NO_TMP_DIR => 'Falta el directorio temporal del servidor.',
        UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
        UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP detuvo la subida.',
      ];
      $msg = $errMap[$file['error']] ?? ('Error de subida (código ' . (int)$file['error'] . ').');
      return ['ok' => false, 'msg' => $msg];
    }
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
      return ['ok' => false, 'msg' => 'Archivo inválido o temporal no accesible.'];
    }
    if (!isset($file['size']) || $file['size'] <= 0) {
      return ['ok' => false, 'msg' => 'El archivo está vacío o no tiene tamaño válido.'];
    }
    if ($file['size'] > $this->maxBytes) {
      return ['ok' => false, 'msg' => 'El archivo supera el límite de 5 MB.'];
    }

    // === Validación de extensión + MIME (sin reventar si no hay fileinfo) ===
    $originalName = $file['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, $this->allowedExt, true)) {
      return ['ok' => false, 'msg' => 'Extensión no permitida. Permitidas: jpg, jpeg, png, pdf.'];
    }

    // Intentar detectar el MIME real, con fallbacks seguros
    $mime = '';
    if (class_exists('finfo')) {
      $fi = new finfo(FILEINFO_MIME_TYPE);
      $mime = $fi->file($file['tmp_name']) ?: '';
    } elseif (function_exists('mime_content_type')) {
      $mime = mime_content_type($file['tmp_name']) ?: '';
    } elseif (in_array($ext, ['jpg','jpeg','png'], true) && function_exists('getimagesize')) {
      $img = @getimagesize($file['tmp_name']);
      if ($img && !empty($img['mime'])) $mime = $img['mime'];
    }

    $allowedMime = [
      'jpg'  => 'image/jpeg', 'jpeg' => 'image/jpeg',
      'png'  => 'image/png',
      'pdf'  => 'application/pdf'
    ];

    // Si pudimos detectar MIME, exige que coincida la familia esperada (image/* o application/pdf)
    if ($mime !== '' && isset($allowedMime[$ext])) {
      $expectedFamily = explode('/', $allowedMime[$ext])[0]; // image o application
      if (stripos($mime, $expectedFamily . '/') !== 0) {
        return ['ok' => false, 'msg' => 'Tipo de archivo no válido (MIME no coincide).'];
      }
    }

    // Preparar carpeta destino ABSOLUTA en C:\Oreka\data$
    $destDir = rtrim($this->storageDirAbs, "\\/");

    if (!is_dir($destDir)) {
      @mkdir($destDir, 0775, true); // En Windows, los permisos se ignoran
    }
    if (!is_dir($destDir) || !is_writable($destDir)) {
      return ['ok' => false, 'msg' => 'No se puede escribir en la carpeta de destino.'];
    }

    try {
      $pdo->beginTransaction();

      // 1) trial (sin imagen todavía)
      $sqlTrial = "INSERT INTO trial (content, created_at, updated_at) VALUES (?, GETDATE(), GETDATE())";
      $stT = $pdo->prepare($sqlTrial);
      $stT->execute([$content]);
      $idTrial = (int)$pdo->lastInsertId();

      // 2) link -> trial
      $sqlLink = "INSERT INTO link (id_trial) VALUES (?)";
      $stL = $pdo->prepare($sqlLink);
      $stL->execute([$idTrial]);
      $idLink = (int)$pdo->lastInsertId();

      // 3) Guardar archivo con nombre: idTrial-userId-YYYY-MM-DD.ext
      $date        = date('Y-m-d');
      $fileName    = $idTrial . '-' . $idUser . '-' . $date . '.' . $ext;
      $destPathAbs = $destDir . DIRECTORY_SEPARATOR . $fileName;

      if (!move_uploaded_file($file['tmp_name'], $destPathAbs)) {
        throw new RuntimeException('No se pudo mover el archivo subido.');
      }

      // Ruta que guardamos en BD:
      // Como ahora no está bajo /public, guardamos la ruta ABSOLUTA del sistema.
      // (Si prefieres sólo el nombre de archivo o una ruta relativa a un alias web, cámbialo aquí)
      $storedPath = $destPathAbs;

      // 4) image
      $trialCategoryId = $this->getTrialCategoryId(); // opcional
      $sqlImg = "INSERT INTO image (id_category, path) VALUES (?, ?)";
      $stI = $pdo->prepare($sqlImg);
      $stI->execute([$trialCategoryId, $storedPath]);
      $idImage = (int)$pdo->lastInsertId();

      // 5) actualizar trial con id_image
      $sqlTrialUpd = "UPDATE trial SET id_image = ? WHERE id = ?";
      $stTU = $pdo->prepare($sqlTrialUpd);
      $stTU->execute([$idImage, $idTrial]);

      // 6) category_link (si existe categoría entity_type='trial')
      if ($trialCategoryId) {
        $stCL = $pdo->prepare("INSERT INTO category_link (id_link, id_category) VALUES (?, ?)");
        $stCL->execute([$idLink, $trialCategoryId]);
      }

      // 7) point (10 puntos)
      $sqlPoint = "INSERT INTO point (id_link, points, created_at) VALUES (?, ?, GETDATE())";
      $stP = $pdo->prepare($sqlPoint);
      $stP->execute([$idLink, $this->pointsAward]);
      $idPoint = (int)$pdo->lastInsertId();

      // 8) user_activity (status=finalizado)
      $sqlUA = "INSERT INTO user_activity (id_user, id_point, status) VALUES (?, ?, ?)";
      $stUA = $pdo->prepare($sqlUA);
      $stUA->execute([$idUser, $idPoint, 'finalizado']);

      $pdo->commit();
      return ['ok' => true, 'id_trial' => $idTrial, 'id_link' => $idLink, 'points' => $this->pointsAward];
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      return ['ok' => false, 'msg' => 'Error al guardar la prueba: ' . $e->getMessage()];
    }
  }
}
