<?php
require_once __DIR__ . '/../models/Forum.php';

class ForumController
{
    /** Devuelve el HTML del bloque Foro para incrustarlo en home.php */
    public function render(): string
    {
        $events = (new Forum())->getAll();

        ob_start();
        include __DIR__ . '/../views/forum.php';  // usa $events
        return ob_get_clean();
    }
}
