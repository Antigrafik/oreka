<?php
require_once __DIR__ . '/../models/Learn.php';

class LearnController
{
    /** Devuelve el HTML renderizado del módulo Learn */
    public function render(): string
    {
        $learns = (new Learn())->getAll();

        // Renderizar la vista a un string
        ob_start();
        include __DIR__ . '/../views/learn.php'; // usa $learns
        return ob_get_clean();
    }
}

