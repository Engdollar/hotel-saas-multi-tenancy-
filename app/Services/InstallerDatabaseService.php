<?php

namespace App\Services;

use PDO;
use PDOException;

class InstallerDatabaseService
{
    public function testMySqlConnection(array $config): array
    {
        if (! extension_loaded('pdo_mysql')) {
            return [
                'passes' => false,
                'message' => 'The pdo_mysql PHP extension is not installed, so MySQL connections cannot be tested.',
            ];
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['db_host'],
            (int) $config['db_port'],
            $config['db_database'],
        );

        try {
            $pdo = new PDO($dsn, $config['db_username'], $config['db_password'] ?? '', [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);

            $pdo->query('SELECT 1');

            return [
                'passes' => true,
                'message' => 'Database connection successful. The installer can reach the configured MySQL database.',
            ];
        } catch (PDOException $exception) {
            return [
                'passes' => false,
                'message' => $exception->getMessage(),
            ];
        }
    }
}