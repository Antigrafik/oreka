<?php
require_once PRIVATE_PATH . '/config/db_connect.php';

class Routines
{
  /** Hijos de un padre (por ejemplo 38) con fallback de idioma */
  public function getChildrenCategories(int $parentId, string $lang = 'es', string $fallback = 'eu'): array {
    global $pdo;
    $sql = "
      SELECT
        c.id,
        COALESCE(ct.name, ctf.name)  AS name,
        COALESCE(ct.slug, ctf.slug)  AS slug
      FROM category_relation cr
      JOIN category c ON c.id = cr.id_child
      LEFT JOIN category_translation ct
        ON ct.id_category = c.id AND ct.lang = ?
      LEFT JOIN category_translation ctf
        ON ctf.id_category = c.id AND ctf.lang = ?
      WHERE cr.id_parent = ?
      ORDER BY name ASC";
    $st = $pdo->prepare($sql);
    $st->execute([$lang, $fallback, $parentId]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
  }

  /** Crea rutina + link + point + user_activity + category_link (todo en una transacción) */
  public function createRoutineWithActivity(array $payload): array {
    global $pdo;
    // payload: id_user, id_category (hija seleccionada), frequency, duration
    $idUser     = (int)$payload['id_user'];
    $idCategory = (int)$payload['id_category'];
    $frequency  = (int)$payload['frequency'];
    $duration   = (int)$payload['duration'];

    if ($idUser <= 0 || $idCategory <= 0 || $frequency < 1 || $frequency > 7 || $duration < 1) {
      return ['ok' => false, 'msg' => 'Datos inválidos'];
    }

    try {
      $pdo->beginTransaction();

      // 1) routine
      $sql1 = "INSERT INTO routine (frequency, duration, created_at, updated_at)
               VALUES (?, ?, GETDATE(), GETDATE());";
      $st1 = $pdo->prepare($sql1);
      $st1->execute([$frequency, $duration]);
      // SQL Server + PDO: lastInsertId() en tablas IDENTITY
      $idRoutine = (int)$pdo->lastInsertId();

      // 2) link (apunta a routine)
      $sql2 = "INSERT INTO link (id_routine) VALUES (?);";
      $st2 = $pdo->prepare($sql2);
      $st2->execute([$idRoutine]);
      $idLink = (int)$pdo->lastInsertId();

      // 3) category_link (relacionar con la categoría elegida)
      $sql3 = "INSERT INTO category_link (id_link, id_category) VALUES (?, ?);";
      $st3 = $pdo->prepare($sql3);
      $st3->execute([$idLink, $idCategory]);

      // 4) point (10 puntos por la rutina)
      $sql4 = "INSERT INTO point (id_link, points, created_at) VALUES (?, ?, GETDATE());";
      $st4  = $pdo->prepare($sql4);
      $st4->execute([$idLink, 10]);
      $idPoint = (int)$pdo->lastInsertId();

      // 5) user_activity
      $sql5 = "INSERT INTO user_activity (id_user, id_point, status) VALUES (?, ?, ?);";
      $st5  = $pdo->prepare($sql5);
      $st5->execute([$idUser, $idPoint, 'en_proceso']);

      $pdo->commit();
      return ['ok' => true, 'msg' => 'Rutina guardada', 'id_routine' => $idRoutine, 'id_link' => $idLink];
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) $pdo->rollBack();
      return ['ok' => false, 'msg' => 'Error al guardar la rutina: ' . $e->getMessage()];
    }
  }

  /** Resuelve el id_user a partir de REMOTE_USER o sesión */
  public function currentUserId(): ?int {
    global $pdo;
    // Prioriza sesión si la tienes; de lo contrario usa REMOTE_USER (IIS/NTLM)
    $username = $_SESSION['username'] ?? ($_SERVER['REMOTE_USER'] ?? null);
    if (!$username) return null;

    // Si viene DOMAIN\user, quédate con la parte final
    if (strpos($username, '\\') !== false) {
      $parts = explode('\\', $username);
      $username = end($parts);
    }

    // En tu tabla user, asumo columna "name" guarda ese identificador
    $st = $pdo->prepare("SELECT id FROM [user] WHERE name = ?");
    $st->execute([$username]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['id'] : null;
  }
}
