<?php
declare(strict_types=1);

class MySpace
{
    /** @var PDO */
    private $pdo;

    public function __construct()
    {
        global $pdo;
        if (!$pdo instanceof PDO) {
            throw new RuntimeException('DB connection ($pdo) not available in MySpace model.');
        }
        $this->pdo = $pdo;
    }

}
