<?php
class Community {
    public function getAll() {
        global $pdo;

        if (!isset($pdo)) {
            // Devolver datos de prueba o un array vacío
            return [
                ['id' => 1, 'title' => 'Foro de ejemplo', 'created_at' => date('Y-m-d')],
                ['id' => 2, 'title' => 'Otro tema', 'created_at' => date('Y-m-d')]
            ];
        }

        $stmt = $pdo->query("SELECT * FROM community ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<div id="community">
    <h1>COMMUNITY</h1>
    <p>Aquí va los datos de la comunidad.</p>
    <p>Se puede interactuar con otros mediante LIKE</p>
    <p>Añadimos info desde aqui, con formulario (oculto, cuando le doy a añadir se muestra)</p>
    <h2>Tus recomendaciones</h2>
    <p>Subir recomendaciones a través de un formulario y que sean visibles para todo el mundo. Cuantas estarían visibles? Sería posible que la gente “votara” o diera likes a las recomendaciones de manera que se visibilicen las más votadas?</p>
    <p>Dudas con el “control” o no de lo que se sube. En principio sin censura, pero con una rutina de revisión por si acaso.</p>
    <h2>Tus rutinas de salud</h2>
    <p>Podría ser un formulario en el que se va eligiendo entre varias opciones y puede dar tener una especie de calculadora, de manera que de más puntos  cuando metes más horas..</p>
    <p>Además del espacio personal donde ves tus puntos,  debería haber un “contador general” que muestre cifras agregadas para que desde la entidad podamos generar noticias tipo “ este trim XXX horas de cardio”</p>
    <h2>Tus pruebas deportivas</h2>
    <p>Descripción de la prueba y foto?</p>
    <p>Puntos directos por participar ? A todas las pruebas lo mismo?</p>
</div>