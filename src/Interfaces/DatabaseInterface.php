<?php
namespace Roolith\Store\Interfaces;

use Roolith\Store\Responses\DeleteResponse;
use Roolith\Store\Responses\InsertResponse;
use Roolith\Store\Responses\UpdateResponse;

interface DatabaseInterface
{
    /**
     * Establish database connection
     *
     * @param $config
     * ['host' => '', 'port' => '', 'name' => '', 'user' => '', 'pass' => '', 'type' => 'MySQL']
     * @return bool
     */
    public function connect($config): bool;

    /**
     * Disconnect from a database
     *
     * @return bool
     */
    public function disconnect(): bool;

    /**
     * Reset all states
     *
     * @return $this
     */
    public function reset(): DatabaseInterface;

    /**
     * Return records
     *
     * @return array
     * should return an array of records or empty array
     */
    public function get(): array;

    /**
     * Return first item of records
     *
     * @return false|object
     */
    public function first(): object|bool;

    /**
     * Get total count of a result
     *
     * @return int
     */
    public function count(): int;

    /**
     * Add where condition to an existing query
     *
     * Supports both where($col, $val) / where($col, $val, $expr)
     * and where($col, $op, $val).
     *
     * @param $name
     * @param $value
     * @param $expression string operator or bound value when $value is an operator
     * @return $this
     */
    public function where(
        $name,
        $value,
        $expression = "=",
    ): DatabaseInterface;

    /**
     * Add or where condition to an existing query
     *
     * Supports both orWhere($col, $val) / orWhere($col, $val, $expr)
     * and orWhere($col, $op, $val).
     *
     * @param $name
     * @param $value
     * @param $expression string operator or bound value when $value is an operator
     * @return $this
     */
    public function orWhere(
        $name,
        $value,
        $expression = "=",
    ): DatabaseInterface;

    /**
     * Get data by id
     *
     * @param $id
     * @return object|false
     */
    public function find($id): object|bool;

    /**
     * Retrieve an array of items
     *
     * @param $nameArray
     * @return array
     */
    public function pluck($nameArray): array;

    /**
     * Set ORDER BY for the next select/get.
     *
     * @param string $column
     * @param string $direction ASC|DESC
     * @return $this
     */
    public function orderBy(string $column, string $direction = "ASC"): DatabaseInterface;

    /**
     * Set LIMIT/OFFSET for the next select/get.
     *
     * Paginate() limit/offset takes precedence when used.
     *
     * @param int $limit
     * @param int $offset
     * @return $this
     */
    public function limit(int $limit, int $offset = 0): DatabaseInterface;

    /**
     * Set OFFSET for the next select/get.
     *
     * @param int $offset
     * @return $this
     */
    public function offset(int $offset): DatabaseInterface;

    /**
     * Pagination
     *
     * @param $array [
        'perPage' => 1,
        'pageUrl' => 'http://localhost/roolith-database/demo',
        'primaryColumn' => 'id',
        'currentPage' => 1,
        'pageParam' => 'page',
     ]
     * @return PaginatorInterface
     *
     {
        "total": 50,
        "perPage": 15,
        "currentPage": 1,
        "lastPage": 4,
        "firstPageUrl": "http://example.com?page=1",
        "lastPageUrl": "http://example.com?page=4",
        "nextPageUrl": "http://example.com?page=2",
        "prevPageUrl": null,
        "path": "http://example.com",
        "from": 1,
        "to": 15,
        "data":[
            // records
        ]
     }
     */
    public function paginate(array $array): PaginatorInterface;

    /**
     * Database raw query
     *
     * @param $string
     * @param $method
     * @param $bindings named or positional bound values
     * @return $this
     */
    public function query($string, $method = null, $bindings = []): DatabaseInterface;

    /**
     * Database raw execute
     *
     * @param string $query
     * @param array $bindings named or positional bound values
     * @return mixed
     */
    public function execute(string $query, array $bindings = []): mixed;

    /**
     * Set table name
     *
     * @param $name
     * @return $this
     */
    public function table($name): DatabaseInterface;

    /**
     * Database select query
     *
     * 'condition' is a trusted SQL literal escape hatch, never interpolate
     * input into it. Pass variables via 'bindings' instead:
     * ['condition' => 'WHERE id > :min', 'bindings' => [':min' => 0]].
     *
     * @param $array
     * Example [
     * 'field' => ['name', 'username'],
     * 'condition' => 'WHERE id > :min',
     * 'bindings' => [':min' => 0],
     * 'limit' => '0, 10',
     * 'orderBy' => 'name',
     * 'groupBy' => 'name',
     * ]
     * @param $bindings additional bound values for raw conditions
     * @return $this
     */
    public function select($array, $bindings = []): DatabaseInterface;

    /**
     * Insert query
     *
     * @param $array
     * example ['name' => 'John doe', 'email' => 'john@email.com']
     * @param array $uniqueArray
     * example ['email']
     * @return InsertResponse
     */
    public function insert($array, array $uniqueArray = []): InsertResponse;

    /**
     * Update query (bound only, array where).
     *
     * Raw string where is intentionally unsupported to prevent injection.
     *
     * @param $array
     * @param array $whereArray e.g. ['id' => 1]
     * @param array $uniqueArray
     * @return UpdateResponse
     */
    public function update(
        $array,
        array $whereArray,
        array $uniqueArray = [],
    ): UpdateResponse;

    /**
     * Delete query
     *
     * @param $whereArray
     * @return DeleteResponse
     */
    public function delete($whereArray): DeleteResponse;

    /**
     * Turn on debug mode
     *
     * @param bool $mode
     * @return $this
     */
    public function debugMode(bool $mode = true): DatabaseInterface;

    /**
     * Whether a transaction is active.
     *
     * @return bool
     */
    public function inTransaction(): bool;

    /**
     * Begin transaction
     *
     * Nesting is unsupported: throws when already in a transaction.
     *
     * @return bool
     */
    public function beginTransaction(): bool;

    /**
     * Commit transaction
     *
     * Throws when no transaction is active.
     *
     * @return bool
     */
    public function commit(): bool;

    /**
     * Roll back transaction
     *
     * Throws when no transaction is active.
     *
     * @return bool
     */
    public function rollBack(): bool;

    /**
     * Run callback inside a transaction.
     *
     * Commits on success, rolls back and rethrows on failure.
     * Nesting is unsupported.
     *
     * @param callable $callback function (DatabaseInterface $db): mixed
     * @return mixed
     */
    public function transaction(callable $callback): mixed;

    /**
     * Get collected debug queries (no output side-effects).
     *
     * @return array<int, array{query:string, bindings:mixed}>
     */
    public function getDebugLog(): array;

    /**
     * Clear collected debug queries.
     *
     * @return $this
     */
    public function clearDebugLog(): DatabaseInterface;
}
