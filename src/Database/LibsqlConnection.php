<?php

declare(strict_types=1);

namespace Libsql\Laravel\Database;

use Illuminate\Database\Connection;
use Illuminate\Filesystem\Filesystem;
use Libsql\Transaction;

class LibsqlConnection extends Connection
{
    protected LibsqlDatabase $db;

    protected Transaction $tx;

    /**
     * The active PDO connection used for reads.
     *
     * @var LibsqlDatabase|\Closure
     */
    protected $readPdo;

    protected array $lastInsertIds = [];

    protected array $bindings = [];

    protected int $mode = \PDO::FETCH_OBJ;

    public function __construct(LibsqlDatabase $db, string $database = ':memory:', string $tablePrefix = '', array $config = [])
    {
        $libsqlDb = function () use ($db) {
            return $db;
        };
        parent::__construct($libsqlDb, $database, $tablePrefix, $config);

        $this->db = $db;
        $this->schemaGrammar = $this->getDefaultSchemaGrammar();
    }

    public function sync(): void
    {
        $this->db->sync();
    }

    public function getConnectionMode(): string
    {
        return $this->db->getConnectionMode();
    }

    public function inTransaction(): bool
    {
        return $this->db->inTransaction();
    }

    public function setFetchMode(int $mode, mixed ...$args): bool
    {
        $this->mode = $mode;

        return true;
    }

    public function getServerVersion(): string
    {
        return $this->db->version();
    }

    public function getPdo(): LibsqlDatabase
    {
        return $this->db;
    }

    /**
     * Set the active PDO connection used for reads.
     *
     * @param LibsqlDatabase|\Closure $pdo
     * @return \Libsql\Laravel\Database\LibsqlConnection
     */
    public function setReadPdo($pdo): self
    {
        $this->readPdo = $pdo;

        return $this;
    }

    public function createReadPdo(array $config): ?LibsqlDatabase
    {
        $db = function () use ($config) {
            return new LibsqlDatabase($config);
        };
        $this->setReadPdo($db);

        return $db();
    }

    public function selectOne($query, $bindings = [], $useReadPdo = true)
    {
        $records = $this->select($query, $bindings, $useReadPdo);

        return array_shift($records);
    }

    public function select($query, $bindings = [], $useReadPdo = true)
    {
        $bindings = array_map(function ($binding) {
            return is_bool($binding) ? (int) $binding : $binding;
        }, $bindings);

        $data = $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return [];
            }

            $statement = $this->getPdo()->prepare($query);
            $results = (array) $statement->query($bindings);

            return $results;
        });

        $rowValues = array_values($data);

        return match ($this->mode) {
            \PDO::FETCH_BOTH => array_merge($data, $rowValues),
            \PDO::FETCH_ASSOC, \PDO::FETCH_NAMED => $data,
            \PDO::FETCH_NUM => $rowValues,
            \PDO::FETCH_OBJ => arrayToStdClass($data),
            default => throw new \PDOException('Unsupported fetch mode.'),
        };
    }

    public function insert($query, $bindings = []): bool
    {
        return $this->affectingStatement($query, $bindings) > 0;
    }

    public function update($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    public function delete($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    public function affectingStatement($query, $bindings = [])
    {
        $bindings = array_map(function ($binding) {
            return is_bool($binding) ? (int) $binding : $binding;
        }, $bindings);

        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return 0;
            }

            $statement = $this->getPdo()->prepare($query);

            foreach ($bindings as $key => $value) {
                $type = is_resource($value) ? \PDO::PARAM_LOB : \PDO::PARAM_STR;
                $statement->bindValue($key, $value, $type);
            }

            $statement->execute();

            $this->recordsHaveBeenModified(($count = $statement->rowCount()) > 0);

            return $count;
        });
    }

    #[\ReturnTypeWillChange]
    protected function getDefaultSchemaGrammar(): LibsqlSchemaGrammar
    {
        return new LibsqlSchemaGrammar($this);
    }

    public function getSchemaBuilder(): LibsqlSchemaBuilder
    {
        if ($this->schemaGrammar === null) {
            $this->useDefaultSchemaGrammar();
        }

        return new LibsqlSchemaBuilder($this->db, $this);
    }

    public function getDefaultPostProcessor(): LibsqlQueryProcessor
    {
        return new LibsqlQueryProcessor();
    }

    public function useDefaultPostProcessor()
    {
        $this->postProcessor = $this->getDefaultPostProcessor();
    }

    protected function getDefaultQueryGrammar()
    {
        return new LibsqlQueryGrammar($this);
    }

    public function useDefaultQueryGrammar()
    {
        $this->queryGrammar = $this->getDefaultQueryGrammar();
    }

    public function query()
    {
        $grammar = $this->getQueryGrammar();
        $processor = $this->getPostProcessor();

        return new LibsqlQueryBuilder(
            $this,
            $grammar,
            $processor
        );
    }

    public function getSchemaState(?Filesystem $files = null, ?callable $processFactory = null): LibsqlSchemaState
    {
        return new LibSQLSchemaState($this, $files, $processFactory);
    }

    public function isUniqueConstraintError(\Exception $exception): bool
    {
        return (bool) preg_match('#(column(s)? .* (is|are) not unique|UNIQUE constraint failed: .*)#i', $exception->getMessage());
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

        if (is_string($input)) {
            return "'" . $this->escapeString($input) . "'";
        }

        if (is_resource($input)) {
            return $this->escapeBinary(stream_get_contents($input));
        }

        return $this->escapeBinary($input);
    }
}
