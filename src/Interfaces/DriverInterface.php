<?php
namespace Roolith\Store\Interfaces;

use Roolith\Store\Exceptions\Exception;

interface DriverInterface
{
    /**
     * Establish database connection
     *
     * @param $config
     * ['host' => '', 'port' => '', 'name' => '', 'user' => '', 'pass' => '', 'type' => 'mysql']
     * @return bool
     * @throws Exception
     */
    public function connect($config): bool;

    /**
     * Disconnect from database
     *
     * @return bool
     */
    public function disconnect(): bool;

    /**
     * Database raw query
     *
     * @param $string
     * @param $method
     * @param $bindings named or positional bound values
     * @return array
     * ['total' => 0, 'data' => [], 'debug' => ['string' => '', 'value' => [], 'method' => '']]
     * @throws Exception
     */
    public function query($string, $method = null, $bindings = []): array;

    /**
     * Database raw execute
     *
     * @param $string
     * @param array $bindings named or positional bound values
     * @return mixed
     * @throws Exception
     */
    public function execute(string $string, array $bindings = []): mixed;

    /**
     * Build condition string
     * example ['name' => 'id', 'value' => 1, 'type' => 'AND']
     * default type is AND
     *
     * @param $array
     * @return string
     */
    public function buildConditionQueryString($array): string;

    /**
     * Pure fragment builder without touching driver state.
     *
     * @param array $array ['name'=>col, 'value'=>val, 'expression'=>'=', 'operator'=>'AND']
     * @param int $counter placeholder counter start
     * @return array{0:string,1:array,2:int} [fragment, bindings, nextCounter]
     */
    public function buildConditionFragment(array $array, int $counter = 0): array;

    /**
     * Get bindings collected by buildConditionQueryString().
     *
     * @return array
     */
    public function getWhereBindings(): array;

    /**
     * Reset conditional query string
     *
     * @return bool
     */
    public function resetConditionalQueryString(): bool;

    /**
     * Database select query
     *
     * @param $table
     * @param $array
     * Example [
     * 'field' => ['name', 'username'],
     * 'condition' => 'WHERE id > 0',
     * 'bindings' => [':id' => 1],
     * 'limit' => '0, 10',
     * 'orderBy' => 'name',
     * 'groupBy' => 'name',
     * ]
     * @param $bindings additional bound values
     * @return iterable
     * @throws Exception
     */
    public function select($table, $array, $bindings = []): iterable;

    /**
     * Insert query
     *
     * @param $table string
     * @param $array array
     * example ['name' => 'John doe', 'email' => 'john@email.com']
     * @param array $uniqueArray
     * example ['email']
     * @return bool|array ['affectedRow' => 1, 'insertedId' => 1, 'isDuplicate' => 1]
     * @throws Exception
     */
    public function insert(
        string $table,
        array $array,
        array $uniqueArray = [],
    );

    /**
     * Update query (bound only, array where).
     *
     * Raw string where is intentionally unsupported to prevent injection.
     *
     * @param $table string
     * @param $array array
     * @param $whereArray array e.g. ['id' => 1]
     * @param array $uniqueArray
     * @return bool|array ['affectedRow' => 1, isDuplicate => 1]
     * @throws Exception
     */
    public function update(
        string $table,
        array $array,
        array $whereArray,
        array $uniqueArray = [],
    );

    /**
     * Delete query
     *
     * @param $table string
     * @param $whereArray array
     * @return bool|array ['affectedRow' => 1]
     * @throws Exception
     */
    public function delete(string $table, array $whereArray);

    /**
     * Set debug mode
     *
     * @param $mode bool
     * @return $this
     */
    public function setDebugMode(bool $mode): DriverInterface;

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
     * @throws Exception
     */
    public function beginTransaction(): bool;

    /**
     * Commit transaction
     *
     * Throws when no transaction is active.
     *
     * @return bool
     * @throws Exception
     */
    public function commit(): bool;

    /**
     * Roll back transaction
     *
     * Throws when no transaction is active.
     *
     * @return bool
     * @throws Exception
     */
    public function rollBack(): bool;

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
    public function clearDebugLog(): DriverInterface;

    /**
     * Get query suffix
     *
     * @param string $string
     * @param string $whereCondition
     * @param int $limit
     * @param int $offset
     * @return array [
     * 'condition' => '',
     * 'limit' => '',
     * 'string' => '',
     * ]
     */
    public function getQuerySuffix(
        string $string = "",
        string $whereCondition = "",
        int $limit = 0,
        int $offset = 0,
    ): array;
}
