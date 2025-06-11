<?php
class Learn {
    public function getAll() {
        global $pdo;

        if (!isset($pdo)) {
            // Devolver datos de prueba o un array vacío
            return [
                ['id' => 1, 'title' => 'Foro de ejemplo', 'created_at' => date('Y-m-d')],
                ['id' => 2, 'title' => 'Otro tema', 'created_at' => date('Y-m-d')]
            ];
        }

        $stmt = $pdo->query("SELECT * FROM learn ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<div id="learn">
    <h1>LEARN</h1>
    <p>En esta sección solo se muestran los cursos.</p>
    <p>Este apartado redirecciona a la web de los cursos.</p>
    <p>Los puntos obtenidos nos los facilita la empresa de cursos.</p>
</div>