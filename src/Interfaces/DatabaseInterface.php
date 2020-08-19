<?php
namespace Roolith\Interfaces;

use Roolith\Exceptions\Exception;

interface DatabaseInterface
{
    /**
     * Establish database connection
     *
     * @param $config
     * ['host' => '', 'port' => '', 'name' => '', 'user' => '', 'pass' => '', 'type' => 'MySQL']
     * @return bool
     * @throws Exception
     */
    public function connect($config);

    /**
     * Disconnect from database
     *
     * @return bool
     */
    public function disconnect();

    /**
     * Reset all states
     *
     * @return $this
     */
    public function reset();

    /**
     * Return records
     *
     * @return iterable
     * should return array of records or empty array
     */
    public function get();

    /**
     * Return first item of records
     *
     * @return mixed
     */
    public function first();

    /**
     * Get total count of result
     *
     * @return int
     */
    public function count();

    /**
     * Add where condition to existing query
     *
     * @param $name
     * @param $value
     * @param $expression string
     * @return $this
     */
    public function where($name, $value, $expression);

    /**
     * Add or where condition to existing query
     *
     * @param $name
     * @param $value
     * @param $expression string
     * @return $this
     */
    public function orWhere($name, $value, $expression);

    /**
     * Get data by id
     *
     * @param $id
     * @return mixed
     */
    public function find($id);

    /**
     * Retrieve array of items
     *
     * @param $nameArray
     * @return iterable
     */
    public function pluck($nameArray);

    /**
     * Pagination
     *
     * @param $number
     * @return PaginatorInterface
     *
     {
        "total": 50,
        "perPage": 15,
        "currentPage": 1,
        "lastPage": 4,
        "firstPage_url": "http://example.com?page=1",
        "lastPage_url": "http://example.com?page=4",
        "nextPage_url": "http://example.com?page=2",
        "prevPage_url": null,
        "path": "http://example.com",
        "from": 1,
        "to": 15,
        "data":[
            // records
        ]
     }
     */
    public function paginate($number);

    /**
     * Database raw query
     *
     * @param $string
     * @param $method
     * @return $this
     */
    public function query($string, $method = null);

    /**
     * Set table name
     *
     * @param $name
     * @return $this
     */
    public function table($name);

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
    public function select($array);

    /**
     * Insert query
     *
     * @param $array
     * example ['name' => 'John doe', 'email' => 'john@email.com']
     * @param $uniqueArray
     * example ['email']
     * @return InsertResponseInterface
     */
    public function insert($array, $uniqueArray = []);

    /**
     * Update query
     *
     * @param $array
     * @param $whereArray
     * @param array $uniqueArray
     * @return UpdateResponseInterface
     */
    public function update($array, $whereArray, $uniqueArray = []);

    /**
     * Delete query
     *
     * @param $whereArray
     * @return DeleteResponseInterface
     */
    public function delete($whereArray);

    /**
     * Turn on debug mode
     *
     * @return $this
     */
    public function debugMode();
}
