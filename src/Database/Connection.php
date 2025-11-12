<?php

namespace App\Database;

use PDO;
use PDOException;
use RuntimeException;

class Connection
{
    /**
     * @param array{host:string, database:string, username:string, password:string, charset?:string} $config
     */
    public static function make(array $config): PDO
    {
        $charset = $config['charset'] ?? 'utf8mb4';
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', $config['host'], $config['database'], $charset);

        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            throw new RuntimeException('Unable to connect to the database: ' . $exception->getMessage(), 0, $exception);
        }

        return $pdo;
    }
}
