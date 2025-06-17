<?php

declare(strict_types=1);

namespace Libsql\Laravel\Database;

use Libsql\Connection;
use Libsql\Database;
use Libsql\Transaction;

class LibsqlDatabase
{
    protected Connection $db;
    protected Database $conn;

    private ?Transaction $tx;

    private string $connection_mode;

    private bool $in_transaction = false;

    private array $lastInsertIds = [];

    private int $mode = \PDO::FETCH_ASSOC;

    public function __construct(array $config)
    {
        $config = $this->createConfig($config);
        $connectionMode = $this->detectConnectionMode($config);

        $this->db = $this->buildConnection($connectionMode, $config);
        $this->in_transaction = false;
    }

    private function createConfig(array $config): array
    {
        return [
            'path' => $config['database'] ?? null,
            'url' => $config['url'] ?? null,
            'authToken' => $config['password'] ?? null,
            'encryptionKey' => $config['encryptionKey'] ?? null,
            'syncInterval' => $config['syncInterval'] ?? 0,
            'disable_read_your_writes' => $config['read_your_writes'] ?? true,
            'webpki' => $config['webpki'] ?? false,
        ];
    }

    private function buildConnection(string $mode, array $config): Connection
    {
        $db = match ($mode) {
            'local' => new Database(path: $config['path']),
            'remote' => new Database(url: $config['url'], authToken: $config['authToken']),
            'remote_replica' => new Database(
                path: $config['path'],
                url: $config['url'],
                authToken: $config['authToken'],
                syncInterval: $config['syncInterval'],
                readYourWrites: $config['disable_read_your_writes'],
                webpki: $config['webpki']
            ),
            default => new Database(':memory:')
        };

        return $db->connect();
    }

    private function detectConnectionMode(array $config): string
    {
        $database = $config['path'];
        $url = $config['url'];

        $mode = 'unknown';

        if ($database === ':memory:' || empty($url)) {
            $mode = 'memory';
        } elseif (empty($database) && !empty($url)) {
            $mode = 'remote';
        } elseif (!empty($database) && empty($url)) {
            $mode = 'local';
        } elseif (!empty($database) && !empty($url)) {
            $mode = 'remote_replica';
        }

        $this->connection_mode = $mode;

        return $mode;
    }

    public function version(): string
    {
        return '3.45.1';
    }

    public function inTransaction(): bool
    {
        return $this->in_transaction;
    }

    public function sync(): void
    {
        if ($this->connection_mode !== 'remote_replica') {
            throw new \Exception("[Libsql:{$this->connection_mode}] Sync is only available for Remote Replica Connection.", 1);
        }
        $this->conn->sync();
    }

    public function getConnectionMode(): string
    {
        return $this->connection_mode;
    }

    public function setFetchMode(int $mode, mixed ...$args): bool
    {
        $this->mode = $mode;

        return true;
    }

    public function beginTransaction(): bool
    {
        if ($this->inTransaction()) {
            throw new \PDOException('Already in a transaction');
        }

        $this->in_transaction = true;
        $this->tx = $this->db->transaction();

        return true;
    }

    public function prepare(string $sql): LibsqlStatement
    {
        return new LibsqlStatement(
            ($this->inTransaction() ? $this->tx : $this->db)->prepare($sql),
            $sql
        );
    }

    public function exec(string $queryStatement): int
    {
        $statement = $this->prepare($queryStatement);
        $statement->execute();

        return $statement->rowCount();
    }

    public function query(string $sql, array $params = [])
    {
        $results = $this->db->query($sql, $params)->fetchArray();
        $rowValues = array_values($results);

        return match ($this->mode) {
            \PDO::FETCH_BOTH => array_merge($results, $rowValues),
            \PDO::FETCH_ASSOC, \PDO::FETCH_NAMED => $results,
            \PDO::FETCH_NUM => $rowValues,
            \PDO::FETCH_OBJ => $results,
            default => throw new \PDOException('Unsupported fetch mode.'),
        };
    }

    public function setLastInsertId(?string $name = null, ?int $value = null): void
    {
        if ($name === null) {
            $name = 'id';
        }

        $this->lastInsertIds[$name] = $value;
    }

    public function lastInsertId(?string $name = null): int|string
    {
        if ($name === null) {
            $name = 'id';
        }

        return isset($this->lastInsertIds[$name])
            ? (string) $this->lastInsertIds[$name]
            : $this->db->lastInsertId();

    }

    public function escapeString($input)
    {
        if ($input === null) {
            return 'NULL';
        }

        return \SQLite3::escapeString($input);
    }

    public function quote($input)
    {
        if ($input === null) {
            return 'NULL';
        }

        return "'" . $this->escapeString($input) . "'";
    }

    public function commit(): bool
    {
        if (!$this->inTransaction()) {
            throw new \PDOException('No active transaction');
        }

        $this->tx->commit();
        $this->in_transaction = false;

        return true;
    }

    public function rollBack(): bool
    {
        if (!$this->inTransaction()) {
            throw new \PDOException('No active transaction');
        }

        $this->tx->rollback();
        $this->in_transaction = false;

        return true;
    }

}
