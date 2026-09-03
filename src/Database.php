<?php
namespace Roolith\Store;

use Closure;
use Roolith\Store\Drivers\PdoDriver;
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

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get(): array
    {
        try {
            if (is_callable($this->queryFn)) {
                call_user_func($this->queryFn, $this->whereCondition);
            } else {
                $this->select([])->get();
            }

            return $this->result ?? [];
        } finally {
            $this->queryFn = null;
            $this->whereCondition = "";
            $this->driver->resetConditionalQueryString();
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
        string $expression = "=",
    ): DatabaseInterface {
        $this->whereCondition = $this->driver->buildConditionQueryString([
            "name" => $name,
            "value" => $value,
            "operator" => "AND",
            "expression" => $expression,
        ]);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function orWhere(
        $name,
        $value,
        string $expression = "=",
    ): DatabaseInterface {
        $this->whereCondition = $this->driver->buildConditionQueryString([
            "name" => $name,
            "value" => $value,
            "operator" => "OR",
            "expression" => $expression,
        ]);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function find($id): object|bool
    {
        $this->driver->resetConditionalQueryString();
        $conditionQueryString = $this->driver->buildConditionQueryString([
            "name" => "id",
            "value" => $id,
        ]);
        $bindings = $this->driver->getWhereBindings();
        $this->driver->resetConditionalQueryString();

        return $this->select([
            "condition" => $this->driver->getQuerySuffix(
                "",
                $conditionQueryString,
            )["string"],
            "bindings" => $bindings,
        ])->first();
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
        }

        return $this->select($opt)->get();
    }

    /**
     * @inheritDoc
     */
    public function paginate($array): PaginatorInterface
    {
        $paginate = new Paginate($array);

        try {
            if (is_callable($this->queryFn)) {
                call_user_func(
                    $this->queryFn,
                    $this->whereCondition,
                    $paginate->limit(),
                    $paginate->offset(),
                );
            } else {
                $this->select([])->get();
            }

            $paginate->setItems($this->result ?? []);

            return $paginate;
        } finally {
            $this->queryFn = null;
            $this->whereCondition = "";
            $this->driver->resetConditionalQueryString();
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
        $this->reset();

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
        return $this->driver->execute($query, $bindings);
    }

    /**
     * @inheritDoc
     */
    public function select($array, $bindings = []): DatabaseInterface
    {
        $this->reset();

        $this->queryFn = function (
            $whereCondition = "",
            $limit = 0,
            $offset = 0,
        ) use ($array, $bindings) {
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
                $array["condition"] = $this->driver->getQuerySuffix(
                    "",
                    $whereCondition,
                )["string"];
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

    /**
     * @inheritDoc
     */
    public function insert($array, array $uniqueArray = []): InsertResponse
    {
        $this->reset();

        $resultArray = $this->driver->insert(
            $this->tableName,
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
        $whereArray,
        array $uniqueArray = [],
    ): UpdateResponse {
        $this->reset();

        $resultArray = $this->driver->update(
            $this->tableName,
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
        $this->reset();

        $resultArray = $this->driver->delete($this->tableName, $whereArray);

        return new DeleteResponse($resultArray["data"]);
    }

    /**
     * @inheritDoc
     */
    public function debugMode($mode = true): DatabaseInterface
    {
        if ($this->driver) {
            $this->driver->setDebugMode($mode);
        }

        return $this;
    }
}
