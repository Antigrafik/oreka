<?php
require_once PRIVATE_PATH . '/config/db_connect.php';  // ✅ en vez de __DIR__ . '/../../../../config/db_connect.php'

class RecommendationInsert
{
    public function addRecommendation(array $data): int
    {
        global $pdo;

        // Iniciar transacción porque tocas varias tablas
        $pdo->beginTransaction();
        try {
            // 1) Insertar en recommendation
            $sqlRec = "INSERT INTO dbo.recommendation (author, likes, created_at)
                       VALUES (:author, 0, GETDATE());";
            $st = $pdo->prepare($sqlRec);
            $st->execute([':author' => $data['author']]);
            $recommendationId = $pdo->lastInsertId();

            // 2) Insertar en link
            $sqlLink = "INSERT INTO dbo.link (id_recommendation) VALUES (:recId);";
            $st = $pdo->prepare($sqlLink);
            $st->execute([':recId' => $recommendationId]);
            $linkId = $pdo->lastInsertId();

            // 3) Insertar en translation (título + contenido)
            $sqlTrans = "INSERT INTO dbo.translation (id_link, lang, title, content)
                         VALUES (:linkId, :lang, :title, :content);";
            $st = $pdo->prepare($sqlTrans);
            $st->execute([
                ':linkId' => $linkId,
                ':lang'   => $data['lang'],
                ':title'  => $data['title'],
                ':content'=> $data['comment']
            ]);

            // 4) Insertar en point (+10 puntos)
            $sqlPoint = "INSERT INTO dbo.point (id_link, points, created_at)
                         VALUES (:linkId, 10, GETDATE());";
            $st = $pdo->prepare($sqlPoint);
            $st->execute([':linkId' => $linkId]);
            $pointId = $pdo->lastInsertId();

            // 5) Insertar en user_activity
            $sqlUA = "INSERT INTO dbo.user_activity (id_user, id_point, status)
                      VALUES (:userId, :pointId, :status);";
            $st = $pdo->prepare($sqlUA);
            $st->execute([
                ':userId'  => $data['user_id'],
                ':pointId' => $pointId,
                ':status'  => 'finalizado'
            ]);

            // 6) Insertar en category_link (tema y soporte)
            if (!empty($data['tema_id'])) {
                $sqlCatLink = "INSERT INTO dbo.category_link (id_link, id_category) VALUES (:linkId, :catId);";
                $st = $pdo->prepare($sqlCatLink);
                $st->execute([
                    ':linkId' => $linkId,
                    ':catId'  => $data['tema_id']
                ]);
            }

            if (!empty($data['soporte_id'])) {
                $sqlCatLink = "INSERT INTO dbo.category_link (id_link, id_category) VALUES (:linkId, :catId);";
                $st = $pdo->prepare($sqlCatLink);
                $st->execute([
                    ':linkId' => $linkId,
                    ':catId'  => $data['soporte_id']
                ]);
            }
            // Confirmar transacción
            $pdo->commit();
            return $recommendationId;

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}