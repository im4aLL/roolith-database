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
     * Reset conditional query string
     *
     * @return bool
     */
    public function resetConditionalQueryString();

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
     * @return iterable
     * @throws Exception
     */
    public function select($table, $array);

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
    public function insert($table, $array, $uniqueArray = []);

    /**
     * Update query
     *
     * @param $table string
     * @param $array array
     * @param $whereArray array
     * @param array $uniqueArray
     * @return bool|array ['affectedRow' => 1, isDuplicate => 1]
     * @throws Exception
     */
    public function update($table, $array, $whereArray, $uniqueArray = []);

    /**
     * Delete query
     *
     * @param $table string
     * @param $whereArray array
     * @return bool|array ['affectedRow' => 1]
     * @throws Exception
     */
    public function delete($table, $whereArray);

    /**
     * Set debug mode
     *
     * @param $mode bool
     * @return $this
     */
    public function setDebugMode($mode);

    /**
     * Get query suffix
     *
     * @param string $string
     * @param string $whereCondition
     * @param int $limit
     * @param int $offset
     * @return array [
        'condition' => '',
        'limit' => '',
        'string' => '',
     ]
     */
    public function getQuerySuffix($string = '', $whereCondition = '', $limit = 0, $offset = 0);
}
