<?php

declare(strict_types=1);

namespace Libsql\Laravel\Database;

use Illuminate\Support\Carbon;
use Libsql\Statement;

class LibsqlStatement
{
    protected int $affectedRows = 0;

    protected int $mode = \PDO::FETCH_OBJ;

    protected array $bindings = [];

    protected array|object $response = [];

    protected array $lastInsertIds = [];

    public function __construct(
        private Statement $statement,
        protected string $query
    ) {
    }

    public function setFetchMode(int $mode, mixed ...$args): bool
    {
        $this->mode = $mode;

        return true;
    }

    public function bindValue($parameter, $value = null, $type = \PDO::PARAM_STR): self
    {
        if (is_int($parameter)) {
            $this->bindings[$parameter] = $value;
        } elseif (is_string($parameter)) {
            $this->bindings[$parameter] = $value;
        } else {
            throw new \InvalidArgumentException('Parameter must be an integer or string.');
        }

        $this->bindings = $this->parameterCasting($this->bindings);

        return $this;
    }

    public function prepare(string $query): self
    {
        return new self($this->statement, $query);
    }

    public function query(array $parameters = []): mixed
    {
        if (empty($parameters)) {
            $parameters = $this->parameterCasting($this->bindings);

            foreach ($parameters as $key => $value) {
                $this->statement->bind([$key => $value]);
            }

            $results = $this->statement->query()->fetchArray();
            $rows = decodeDoubleBase64($results);
            $rowValues = array_values($rows);

            return match ($this->mode) {
                \PDO::FETCH_BOTH => array_merge($rows, $rowValues),
                \PDO::FETCH_ASSOC, \PDO::FETCH_NAMED => $rows,
                \PDO::FETCH_NUM => $rowValues,
                \PDO::FETCH_OBJ => (object) $rows,
                default => throw new \PDOException('Unsupported fetch mode.'),
            };
        }

        $parameters = $this->parameterCasting($parameters);
        foreach ($parameters as $key => $value) {
            $this->statement->bind([$key => $value]);
        }
        $result = $this->statement->query()->fetchArray();
        $rows = decodeDoubleBase64($result);

        return match ($this->mode) {
            \PDO::FETCH_ASSOC => collect($rows),
            \PDO::FETCH_OBJ => (object) $rows,
            \PDO::FETCH_NUM => array_values($rows),
            default => collect($rows)
        };
    }

    public function execute(array $parameters = []): bool
    {
        try {

            if (empty($parameters)) {
                $parameters = $this->bindings;
            }

            foreach ($parameters as $key => $value) {
                $this->statement->bind([$key => $value]);
            }

            if (str_starts_with(strtolower($this->query), 'select')) {
                $queryRows = $this->statement->query()->fetchArray();
                $this->affectedRows = count($queryRows);
            } else {
                $this->affectedRows = $this->statement->execute();
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    #[\ReturnTypeWillChange]
    public function fetch(int $mode = \PDO::FETCH_DEFAULT, int $cursorOrientation = \PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): array|false
    {
        if ($mode === \PDO::FETCH_DEFAULT) {
            $mode = $this->mode;
        }

        $parameters = $this->bindings;
        $parameters = $this->parameterCasting($parameters);
        foreach ($parameters as $key => $value) {
            $this->statement->bind([$key => $value]);
        }

        $result = $this->statement->query();
        $rows = $result->fetchArray();

        $row = $rows[$cursorOffset];
        $mode = \PDO::FETCH_ASSOC;

        if ($this->response === $row) {
            return false;
        }
        $this->response = $row;

        $rowValues = array_values($row);

        $response = match ($mode) {
            \PDO::FETCH_BOTH => array_merge($row, $rowValues),
            \PDO::FETCH_ASSOC, \PDO::FETCH_NAMED => $row,
            \PDO::FETCH_NUM => $rowValues,
            \PDO::FETCH_OBJ => (object) $row,
            default => throw new \PDOException('Unsupported fetch mode.'),
        };

        return $response;
    }

    #[\ReturnTypeWillChange]
    public function fetchAll(int $mode = \PDO::FETCH_DEFAULT, ...$args): array
    {
        if ($mode === \PDO::FETCH_DEFAULT) {
            $mode = $this->mode;
        }

        $parameters = $this->parameterCasting($this->bindings);
        foreach ($parameters as $key => $value) {
            $this->statement->bind([$key => $value]);
        }

        $result = $this->statement->query();
        $rows = $result->fetchArray();

        $allRows = $rows;
        $decodedRows = $this->parameterCasting($allRows);
        $rowValues = \array_map('array_values', $decodedRows);

        $data = match ($mode) {
            \PDO::FETCH_BOTH => array_merge($allRows, $rowValues),
            \PDO::FETCH_ASSOC, \PDO::FETCH_NAMED => $allRows,
            \PDO::FETCH_NUM => $rowValues,
            \PDO::FETCH_OBJ => (object) $allRows,
            default => throw new \PDOException('Unsupported fetch mode.'),
        };

        return $data;
    }

    public function getAffectedRows(): int
    {
        return $this->affectedRows;
    }

    public function nextRowset(): bool
    {
        // TFIDK: database is support for multiple rowset.
        return false;
    }

    public function rowCount(): int
    {
        return $this->affectedRows;
    }

    public function closeCursor(): void
    {
        $this->statement->reset();
    }

    private function parameterCasting(array $parameters): array
    {
        $parameters = collect(array_values($parameters))->map(function ($value) {
            $type = match (true) {
                is_string($value) && (!ctype_print($value) || !mb_check_encoding($value, 'UTF-8')) => 'blob',
                is_float($value) || is_float($value) => 'float',
                is_int($value) => 'integer',
                is_bool($value) => 'boolean',
                $value === null => 'null',
                $value instanceof Carbon => 'datetime',
                is_vector($value) => 'vector',
                default => 'text',
            };

            if ($type === 'blob') {
                $value = base64_encode(base64_encode($value));
            }

            if ($type === 'boolean') {
                $value = (int) $value;
            }

            if ($type === 'datetime') {
                $value = $value->toDateTimeString();
            }

            if ($type === 'vector') {
                $value = json_encode($value);
            }

            return $value;
        })->toArray();

        return $parameters;
    }
}
