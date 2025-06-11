<?php
class Banner {
    public function getAll() {
        global $pdo;

        if (!isset($pdo)) {
            // Devolver datos de prueba o un array vacío
            return [
                ['id' => 1, 'title' => 'Foro de ejemplo', 'created_at' => date('Y-m-d')],
                ['id' => 2, 'title' => 'Otro tema', 'created_at' => date('Y-m-d')]
            ];
        }

        $stmt = $pdo->query("SELECT * FROM banner ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<h1>BANNER</h1>
<p>Será visible, solo cuando se lo indiquemos y a quien se lo indiquemos.</p>
<p>En esta sección no se puede interactuar</p>