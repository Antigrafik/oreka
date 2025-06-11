<?php
class Forum {
    public function getAll() {
        global $pdo;

        if (!isset($pdo)) {
            // Devolver datos de prueba o un array vacío
            return [
                ['id' => 1, 'title' => 'Foro de ejemplo', 'created_at' => date('Y-m-d')],
                ['id' => 2, 'title' => 'Otro tema', 'created_at' => date('Y-m-d')]
            ];
        }

        $stmt = $pdo->query("SELECT * FROM forum ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<div id="forum">
    <h1>FORUM</h1>
    <p>Apareceran enlaces a otras pagina</p>
    <p>¿Cómo se consiqguen los puntos en esta sección?</p>
    <p>Calendario de eventos</p>
    <p>izquierda: calendario</p>
    <p>derecha: eventos</p>
    <p>En esta sección se puede interactuar ¿Se puede dar a Asistir?</p>
    <p>¿enviar mail de recordatorio?</p>
    <p>¿Podemos acceder a su mail?</p>
</div>