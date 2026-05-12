<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use App\Exceptions\DatabaseException;

/**
 * PDO database wrapper with prepared statements.
 */
class Database
{
    private ?PDO $pdo = null;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            $cfg = $this->config;
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $cfg['host'] ?? 'localhost',
                $cfg['db']   ?? '',
                $cfg['charset'] ?? 'utf8mb4'
            );
            try {
                $this->pdo = new PDO($dsn, $cfg['user'] ?? 'root', $cfg['pass'] ?? '', [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                throw new DatabaseException('Database connection failed: ' . $e->getMessage(), 0, $e);
            }
        }
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->pdo()->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new DatabaseException('Query failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $row = $this->query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }

    public function lastInsertId(): string
    {
        return $this->pdo()->lastInsertId();
    }
}
