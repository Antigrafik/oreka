<?php
class DB {
    private static $connection = null;

    public static function connect() {
        if (self::$connection === null) {
            $serverName = "localhost";
            $connectionOptions = [
                "Database" => "oreka",
                "Authentication" => SQLSRV_AUTH_WINDOWS,
                "TrustServerCertificate" => true,
                "Encrypt" => true
            ];

            self::$connection = sqlsrv_connect($serverName, $connectionOptions);

            if (!self::$connection) {
                die(print_r(sqlsrv_errors(), true));
            }
        }

        return self::$connection;
    }
}

