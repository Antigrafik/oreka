<?php
declare(strict_types=1);

/**
 * Modelo de Mi Espacio.
 * Centraliza consultas de puntos, actividad e historiales del usuario.
 * Más adelante podemos dividir por dominios (Aula/Foro/etc.) si crece.
 */
class MySpace
{
    /** @var PDO */
    private $pdo;

    public function __construct()
    {
        // Usa el $pdo global del proyecto
        global $pdo;
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('DB connection ($pdo) not available in MySpace model.');
        }
        $this->pdo = $pdo;
    }

}
