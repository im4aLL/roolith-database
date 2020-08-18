<?php
namespace Roolith\Interfaces;


use Roolith\Exceptions\Exception;

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
    public function connect($config);

    /**
     * Disconnect from database
     *
     * @return bool
     */
    public function disconnect();

    /**
     * Database raw query
     *
     * @param $string
     * @param $method
     * @return array
     * ['total' => 0, 'data' => [], 'debug' => ['string' => '', 'value' => [], 'method' => '']]
     * @throws Exception
     */
    public function query($string, $method = null);

    /**
     * Build condition string
     * example ['name' => 'id', 'value' => 1, 'type' => 'AND']
     * default type is AND
     *
     * @param $array
     * @return string
     */
    public function buildConditionQueryString($array);

    /**
     * Get limit query string
     *
     * @param $total
     * @param $offset
     * @return string
     */
    public function limitNumberOfRowsString($total, $offset);

    /**
     * Database select query
     *
     * @param $table
     * @param $array
     * Example [
     * 'field' => ['name', 'username'],
     * 'condition' => 'WHERE id > 0',
     * 'limit' => '0, 10',
     * 'orderBy' => 'name',
     * 'groupBy' => 'name',
     * ]
     * @return array
     */
    public function select($table, $array);

    /**
     * Insert query
     *
     * @param $array array
     * example ['name' => 'John doe', 'email' => 'john@email.com']
     * @param $uniqueArray
     * example ['email']
     * @return bool|array
     * ['affectedRow' => 1, insertedId => 1, isDuplicate => 1]
     */
    public function insert($array, $uniqueArray = []);

    /**
     * Update query
     *
     * @param $table string
     * @param $array array
     * @param $whereArray array
     * @param array $uniqueArray array
     * @return bool|array
     * ['affectedRow' => 1, isDuplicate => 1]
     */
    public function update($table, $array, $whereArray, $uniqueArray = []);

    /**
     * Delete query
     *
     * @param $table string
     * @param $whereArray array
     * @return bool|array
     * ['affectedRow' => 1]
     */
    public function delete($table, $whereArray);
}