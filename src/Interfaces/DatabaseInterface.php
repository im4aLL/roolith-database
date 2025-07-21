<?php
namespace Roolith\Store\Interfaces;

use Roolith\Store\Exceptions\Exception;
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
     * Disconnect from database
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
     * @return iterable
     * should return array of records or empty array
     */
    public function get(): iterable;

    /**
     * Return first item of records
     *
     * @return false|array
     */
    public function first();

    /**
     * Get total count of result
     *
     * @return int
     */
    public function count(): int;

    /**
     * Add where condition to existing query
     *
     * @param $name
     * @param $value
     * @param $expression string
     * @return $this
     */
    public function where($name, $value, string $expression = '='): DatabaseInterface;

    /**
     * Add or where condition to existing query
     *
     * @param $name
     * @param $value
     * @param $expression string
     * @return $this
     */
    public function orWhere($name, $value, string $expression = '='): DatabaseInterface;

    /**
     * Get data by id
     *
     * @param $id
     * @return object|false
     */
    public function find($id);

    /**
     * Retrieve array of items
     *
     * @param $nameArray
     * @return iterable
     */
    public function pluck($nameArray): iterable;

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
     * @return $this
     */
    public function query($string, $method = null): DatabaseInterface;

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
     * @param $array
     * Example [
        'field' => ['name', 'username'],
        'condition' => 'WHERE id > 0',
        'limit' => '0, 10',
        'orderBy' => 'name',
        'groupBy' => 'name',
     ]
     * @return $this
     */
    public function select($array): DatabaseInterface;

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
     * Update query
     *
     * @param $array
     * @param $whereArray
     * @param array $uniqueArray
     * @return UpdateResponse
     */
    public function update($array, $whereArray, array $uniqueArray = []): UpdateResponse;

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
     * @return $this
     */
    public function debugMode(): DatabaseInterface;
}
