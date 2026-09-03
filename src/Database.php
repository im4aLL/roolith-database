<?php
namespace Roolith\Store;

use Closure;
use Roolith\Store\Drivers\PdoDriver;
use Roolith\Store\Exceptions\Exception;
use Roolith\Store\Exceptions\InvalidArgumentException;
use Roolith\Store\Interfaces\DatabaseInterface;
use Roolith\Store\Interfaces\DriverInterface;
use Roolith\Store\Interfaces\PaginatorInterface;
use Roolith\Store\Responses\DeleteResponse;
use Roolith\Store\Responses\InsertResponse;
use Roolith\Store\Responses\UpdateResponse;

class Database implements DatabaseInterface
{
    protected DriverInterface|null $driver;
    protected array|null $result = null;
    protected int $total = 0;
    protected string $tableName = "";
    protected Closure|null $queryFn = null;
    protected string $whereCondition = "";
    protected string|null $orderBy = null;
    protected int|null $queryLimit = null;
    protected int $queryOffset = 0;

    public function __construct($config = [], ?DriverInterface $driver = null)
    {
        $this->whereCondition = "";
        $this->driver = $driver;

        if (count($config) > 0) {
            $this->connect($config);
        }
    }

    /**
     * @inheritDoc
     */
    public function connect($config): bool
    {
        if (!$this->driver) {
            $this->driver = new PdoDriver();
        }

        return $this->driver->connect($config);
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): bool
    {
        if (!$this->driver) {
            return false;
        }

        return $this->driver->disconnect();
    }

    /**
     * @inheritDoc
     */
    public function reset(): static
    {
        $this->result = null;
        $this->total = 0;
        $this->whereCondition = "";
        $this->queryFn = null;
        $this->tableName = "";
        $this->orderBy = null;
        $this->queryLimit = null;
        $this->queryOffset = 0;

        if ($this->driver) {
            $this->driver->resetConditionalQueryString();
        }

        return $this;
    }

    protected function resetResult(): void
    {
        $this->result = null;
        $this->total = 0;
    }

    protected function requireDriver(): DriverInterface
    {
        if (!$this->driver) {
            throw new Exception("Not connected. Call connect() first.");
        }

        return $this->driver;
    }

    protected function requireTable(): string
    {
        if ($this->tableName === "") {
            throw new Exception("No table selected. Call table() first.");
        }

        return $this->tableName;
    }

    protected function clearQueryState(): void
    {
        $this->queryFn = null;
        $this->whereCondition = "";
        $this->orderBy = null;
        $this->queryLimit = null;
        $this->queryOffset = 0;

        if ($this->driver) {
            $this->driver->resetConditionalQueryString();
        }
    }

    /**
     * @inheritDoc
     */
    public function get(): array
    {
        $this->requireDriver();

        try {
            if (is_callable($this->queryFn)) {
                call_user_func($this->queryFn, $this->whereCondition);
            } else {
                $this->select([])->get();
            }

            return $this->result ?? [];
        } finally {
            $this->clearQueryState();
        }
    }

    /**
     * @inheritDoc
     */
    public function first(): object|bool
    {
        $result = $this->get();

        if ($this->total > 0 && count($result) > 0) {
            return $result[0];
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        $this->get();

        return $this->total;
    }

    /**
     * @inheritDoc
     */
    public function where(
        $name,
        $value,
        $expression = "=",
    ): DatabaseInterface {
        [$column, $op, $val] = $this->normalizeWhereArgs(
            func_get_args(),
        );

        $this->whereCondition = $this->requireDriver()->buildConditionQueryString([
            "name" => $column,
            "value" => $val,
            "operator" => "AND",
            "expression" => $op,
        ]);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function orWhere(
        $name,
        $value,
        $expression = "=",
    ): DatabaseInterface {
        [$column, $op, $val] = $this->normalizeWhereArgs(
            func_get_args(),
        );

        $this->whereCondition = $this->requireDriver()->buildConditionQueryString([
            "name" => $column,
            "value" => $val,
            "operator" => "OR",
            "expression" => $op,
        ]);

        return $this;
    }

    /**
     * Normalize where() args supporting both styles:
     * old: where($col, $val, $expr) and new: where($col, $op, $val).
     *
     * @param array $args
     * @return array{0:mixed,1:string,2:mixed} [column, operator, value]
     */
    protected function normalizeWhereArgs(array $args): array
    {
        if (count($args) <= 2) {
            return [$args[0] ?? null, "=", $args[1] ?? null];
        }

        [$name, $middle, $last] = $args;

        if (is_string($middle) && self::isWhereOperator($middle)) {
            return [$name, $middle, $last];
        }

        return [$name, (string) $last, $middle];
    }

    protected static function isWhereOperator(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }

        return in_array(
            strtoupper(trim($value)),
            ["=", "!=", "<>", "<", "<=", ">", ">=", "LIKE", "NOT LIKE", "IN", "NOT IN"],
            true,
        );
    }

    /**
     * @inheritDoc
     */
    public function orderBy(string $column, string $direction = "ASC"): DatabaseInterface
    {
        $direction = strtoupper(trim($direction));

        if (!in_array($direction, ["ASC", "DESC"], true)) {
            throw new InvalidArgumentException("Invalid order direction.");
        }

        $this->orderBy = $column . " " . $direction;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function limit(int $limit, int $offset = 0): DatabaseInterface
    {
        if ($limit < 0 || $offset < 0) {
            throw new InvalidArgumentException("Invalid limit or offset.");
        }

        $this->queryLimit = $limit;
        $this->queryOffset = $offset;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function offset(int $offset): DatabaseInterface
    {
        if ($offset < 0) {
            throw new InvalidArgumentException("Invalid offset.");
        }

        $this->queryOffset = $offset;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function find($id): object|bool
    {
        $driver = $this->requireDriver();
        $this->requireTable();

        $driver->resetConditionalQueryString();

        try {
            $conditionQueryString = $driver->buildConditionQueryString([
                "name" => "id",
                "value" => $id,
            ]);
            $bindings = $driver->getWhereBindings();

            return $this->select([
                "condition" => $driver->getQuerySuffix(
                    "",
                    $conditionQueryString,
                )["string"],
                "bindings" => $bindings,
            ])->first();
        } finally {
            $driver->resetConditionalQueryString();
            $this->whereCondition = "";
        }
    }

    /**
     * @inheritDoc
     */
    public function pluck($nameArray): array
    {
        $opt = [
            "field" => $nameArray,
        ];

        if ($this->whereCondition) {
            $opt["condition"] = $this->whereCondition;
            $opt["bindings"] = $this->requireDriver()->getWhereBindings();
        }

        return $this->select($opt)->get();
    }

    /**
     * @inheritDoc
     */
    public function paginate(array $array): PaginatorInterface
    {
        $this->requireDriver();

        $paginate = new Paginate($array);

        try {
            if ($paginate->limit() <= 0) {
                $paginate->setItems([]);

                return $paginate;
            }

            if (!is_callable($this->queryFn)) {
                $this->requireTable();
                $this->select([]);
            }

            call_user_func(
                $this->queryFn,
                $this->whereCondition,
                $paginate->limit(),
                $paginate->offset(),
            );

            $paginate->setItems($this->result ?? []);

            return $paginate;
        } finally {
            $this->clearQueryState();
        }
    }

    /**
     * @inheritDoc
     */
    public function table($name): DatabaseInterface
    {
        $this->tableName = $name;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function query($string, $method = null, $bindings = []): DatabaseInterface
    {
        $this->resetResult();

        $this->queryFn = function (
            $whereCondition = "",
            $limit = 0,
            $offset = 0,
        ) use ($string, $method, $bindings) {
            $string = $this->driver->getQuerySuffix(
                $string,
                $whereCondition,
                $limit,
                $offset,
            )["string"];

            $allBindings = array_merge(
                $bindings,
                $this->driver->getWhereBindings(),
            );

            $resultArray = $method
                ? $this->driver->query($string, $method, $allBindings)
                : $this->driver->query($string, null, $allBindings);
            $this->result = $resultArray["data"];
            $this->total = $resultArray["total"];
        };

        return $this;
    }

    /**
     * Database raw execute
     *
     * @param string $query
     * @param array $bindings named or positional bound values
     * @return mixed
     */
    public function execute(string $query, array $bindings = []): mixed
    {
        return $this->requireDriver()->execute($query, $bindings);
    }

    /**
     * @inheritDoc
     */
    public function select($array, $bindings = []): DatabaseInterface
    {
        $this->resetResult();

        $helperOrderBy = $this->orderBy;
        $helperLimit = $this->queryLimit;
        $helperOffset = $this->queryOffset;

        $this->queryFn = function (
            $whereCondition = "",
            $limit = 0,
            $offset = 0,
        ) use ($array, $bindings, $helperOrderBy, $helperLimit, $helperOffset) {
            if (!isset($array["orderBy"]) && $helperOrderBy !== null) {
                $array["orderBy"] = $helperOrderBy;
            }

            if (!isset($array["limit"]) && ($helperLimit !== null || $helperOffset > 0)) {
                $limitValue = $helperLimit ?? 9223372036854775807;
                $array["limit"] = $helperOffset > 0
                    ? $limitValue . " OFFSET " . $helperOffset
                    : (string) $limitValue;
            }

            $querySuffix = $this->driver->getQuerySuffix(
                "",
                $whereCondition,
                $limit,
                $offset,
            );

            if (strlen($querySuffix["limit"]) > 0) {
                $array["limit"] = $querySuffix["limit"];
            }

            if (strlen($whereCondition) > 0) {
                $whereString = $this->driver->getQuerySuffix(
                    "",
                    $whereCondition,
                )["string"];
                $array["condition"] = $this->mergeConditions(
                    $array["condition"] ?? "",
                    $whereString,
                );
            }

            $array["bindings"] = array_merge(
                $array["bindings"] ?? [],
                $bindings,
                $this->driver->getWhereBindings(),
            );

            $resultArray = $this->driver->select($this->tableName, $array);
            $this->result = $resultArray["data"];
            $this->total = $resultArray["total"];
        };

        return $this;
    }

    protected function mergeConditions(string $caller, string $whereString): string
    {
        $strip = static function (string $value): string {
            $value = trim($value);

            if (str_starts_with(strtolower($value), "where ")) {
                $value = trim(substr($value, 5));
            }

            return $value;
        };

        $caller = $strip($caller);
        $where = $strip($whereString);

        if ($caller === "") {
            return $where === "" ? "" : "WHERE " . $where;
        }

        if ($where === "") {
            return "WHERE " . $caller;
        }

        return "WHERE (" . $caller . ") AND (" . $where . ")";
    }

    /**
     * @inheritDoc
     */
    public function insert($array, array $uniqueArray = []): InsertResponse
    {
        $this->resetResult();
        $this->requireDriver();
        $table = $this->requireTable();

        $resultArray = $this->driver->insert(
            $table,
            $array,
            $uniqueArray,
        );

        return new InsertResponse($resultArray["data"]);
    }

    /**
     * @inheritDoc
     */
    public function update(
        $array,
        array $whereArray,
        array $uniqueArray = [],
    ): UpdateResponse {
        $this->resetResult();
        $this->requireDriver();
        $table = $this->requireTable();

        $resultArray = $this->driver->update(
            $table,
            $array,
            $whereArray,
            $uniqueArray,
        );

        return new UpdateResponse($resultArray["data"]);
    }

    /**
     * @inheritDoc
     */
    public function delete($whereArray): DeleteResponse
    {
        $this->resetResult();
        $this->requireDriver();
        $table = $this->requireTable();

        $resultArray = $this->driver->delete($table, $whereArray);

        return new DeleteResponse($resultArray["data"]);
    }

    /**
     * @inheritDoc
     */
    public function debugMode(bool $mode = true): DatabaseInterface
    {
        if ($this->driver) {
            $this->driver->setDebugMode($mode);
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function inTransaction(): bool
    {
        if (!$this->driver) {
            return false;
        }

        return $this->driver->inTransaction();
    }

    /**
     * @inheritDoc
     */
    public function beginTransaction(): bool
    {
        return $this->requireDriver()->beginTransaction();
    }

    /**
     * @inheritDoc
     */
    public function commit(): bool
    {
        return $this->requireDriver()->commit();
    }

    /**
     * @inheritDoc
     */
    public function rollBack(): bool
    {
        return $this->requireDriver()->rollBack();
    }

    /**
     * @inheritDoc
     */
    public function transaction(callable $callback): mixed
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();

            return $result;
        } catch (\Throwable $e) {
            try {
                $this->rollBack();
            } catch (\Throwable) {
            }

            throw $e;
        }
    }

    /**
     * @inheritDoc
     */
    public function getDebugLog(): array
    {
        if (!$this->driver) {
            return [];
        }

        return $this->driver->getDebugLog();
    }

    /**
     * @inheritDoc
     */
    public function clearDebugLog(): DatabaseInterface
    {
        if ($this->driver) {
            $this->driver->clearDebugLog();
        }

        return $this;
    }
}
