<?php
namespace Roolith\Drivers;

use PDO;
use PDOException;
use Roolith\Constants\DbConstant;
use Roolith\Exceptions\Exception;
use Roolith\Interfaces\DriverInterface;

class PdoDriver implements DriverInterface
{
    protected $pdo;
    protected $debugMode;
    protected $whereCondition;

    public function __construct()
    {
        $this->whereCondition = '';
        $this->debugMode = false;
    }

    /**
     * @inheritDoc
     */
    public function connect($config)
    {
        if (count($config) === 0) {
            return false;
        }

        try {
            $this->pdo = $this->getPdo($config);
        } catch (PDOException $PDOException) {
            throw new Exception($PDOException->getMessage() .' '. $PDOException->getTraceAsString());
        }

        return $this->pdo instanceof PDO;
    }

    protected function getPdo($config)
    {
        $type = isset($config['type']) ? strtolower($config['type']) : strtolower(DbConstant::DEFAULT_TYPE);
        $host = $config['host'];
        $port = isset($config['port']) ? $config['port'] : DbConstant::DEFAULT_PORT[DbConstant::DEFAULT_TYPE];
        $dbname = $config['name'];
        $user = $config['user'];
        $pass = $config['pass'];

        $dsn = $type.":host=$host;port=$port;dbname=$dbname";
        $opt = [];

        if ($type === 'mysql') {
            $opt = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
            ];
        }

        return new PDO($dsn, $user, $pass, $opt);
    }

    /**
     * @inheritDoc
     */
    public function disconnect()
    {
        $this->pdo = null;

        return true;
    }

    /**
     * Reset
     *
     * @return $this
     */
    public function reset()
    {
        $this->whereCondition = '';

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function query($string, $method = DbConstant::DEFAULT_PDO_FETCH_METHOD)
    {
        $this->reset();

        $result = [
            'total' => null,
            'data' => null,
        ];

        try {
            if ($this->debugMode) {
                echo $string;
                echo PHP_EOL;
            }

            $qry = $this->pdo->prepare($string);
            $qry->execute();
            $qry->setFetchMode($method);

            if ($this->startsWith($string, 'SELECT')) {
                $result['data'] = $qry->fetchAll();
            }

            $result['total'] = $qry->rowCount();
        } catch (PDOException $PDOException) {
            throw new Exception($PDOException->getMessage() .' '. $PDOException->getTraceAsString());
        }

        return $result;
    }

    /**
     * If string starts with
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    protected function startsWith($haystack, $needle)
    {
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    /**
     * @inheritDoc
     */
    public function buildConditionQueryString($array)
    {
        if (!isset($array['expression'])) {
            $array['expression'] = '=';
        }

        if (strlen($this->whereCondition) > 0) {
            $this->whereCondition .= ' '.$array['operator'].' ';
        }

        $this->whereCondition .= "`".$array['name']."` ".$array['expression']." '".$array['value']."'";

        return $this->whereCondition;
    }

    /**
     * @inheritDoc
     */
    public function resetConditionalQueryString()
    {
        $this->whereCondition = '';

        return true;
    }

    /**
     * @inheritDoc
     */
    public function select($table, $array)
    {
        $result = [
            'total' => null,
            'data' => null,
        ];

        $fieldString = $this->buildFieldSelectString($array);
        $qryStr = $this->buildQueryString($table, $fieldString, $array);

        try {
            if ($this->debugMode) {
                echo $qryStr;
                echo PHP_EOL;
            }

            $qry = $this->pdo->prepare($qryStr);
            $qry->execute();

            if (isset($array['method'])) {
                $qry->setFetchMode($array['method']);
            } else {
                $qry->setFetchMode(DbConstant::DEFAULT_PDO_FETCH_METHOD);
            }

            $result['data'] = $qry->fetchAll();
            $result['total'] = $qry->rowCount();
        }
        catch (PDOException $PDOException){
            throw new Exception($PDOException->getMessage() . ' Query: '.$qryStr.' '.$PDOException->getTraceAsString());
        }

        return $result;
    }

    /**
     * Build select field string
     *
     * @param $array
     * @return string
     */
    protected function buildFieldSelectString($array)
    {
        return (isset($array['field']) && count($array['field']) > 0) ? implode(', ', $array['field']): '*';
    }

    /**
     * Build select field query string
     *
     * @param $table
     * @param $fieldString
     * @param $array
     * @return string
     */
    protected function buildQueryString($table, $fieldString, $array)
    {
        $qryStr = 'SELECT '.$fieldString.' FROM `'.$table.'` '.((isset($array['condition']) && $array['condition'] !== null) ? $array['condition'] : '');

        if(isset($array['groupbBy'])) {
            $qryStr .= ' GROUP BY '.$array['groupbBy'];
        }

        if(isset($array['orderBy'])) {
            $qryStr .= ' ORDER BY '.$array['orderBy'];
        }

        if(isset($array['limit'])) {
            $qryStr .= ' LIMIT '.$array['limit'];
        }

        return $qryStr;
    }

    /**
     * @inheritDoc
     */
    public function insert($table, $array, $uniqueArray = [])
    {
        $result = [
            'data' => [
                'affectedRow' => 0,
                'insertedId' => 0,
                'isDuplicate' => false,
            ],
        ];

        $fields = [];
        $executeArray = [];

        foreach ($array as $key => $val) {
            $fields[] = ':'.$key;
            $executeArray[':'.$key] = $val;
        }

        $fieldString = implode(',', $fields);
        $rawFieldsStr = implode(',', str_replace(':', '', $fields));

        $result['data']['isDuplicate'] = $this->isAlreadyExists($table, $array, $uniqueArray);

        if($result['data']['isDuplicate'] === false) {
            $qryStr = 'INSERT INTO '.$table.' ('.$rawFieldsStr.') VALUES('.$fieldString.')';

            try {
                if ($this->debugMode) {
                    echo $qryStr;
                    echo PHP_EOL;
                    print_r($executeArray);
                    echo PHP_EOL;
                }

                $qry = $this->pdo->prepare($qryStr);
                $qry->execute($executeArray);

                $result['data']['affectedRow'] = $qry->rowCount();
                $result['data']['insertedId'] = $this->pdo->lastInsertId();
            }
            catch (PDOException $PDOException){
                throw new Exception($PDOException->getMessage() . ' Query: '.$qryStr.' '.$PDOException->getTraceAsString());
            }
        }

        return $result;
    }

    /**
     * If record already exists
     *
     * @param $table
     * @param array $array
     * @param array $uniqueArray
     * @param array $whereArray
     * @return bool
     */
    protected function isAlreadyExists($table, $array = [], $uniqueArray = [], $whereArray = [])
    {
        $result = false;

        if( count($uniqueArray) > 0 ) {
            $condition = [];
            foreach ($uniqueArray as $fieldName) {
                $condition[] = $fieldName." = '".$array[$fieldName]."' ";
            }

            $extendedCondition = [];
            if (count($whereArray) > 0) {
                foreach($whereArray as $whereKey => $whereVal) {
                    $extendedCondition[] = $whereKey." != '".$whereVal."' ";
                }
            }

            $cQryStr = "SELECT ".$uniqueArray[0]." FROM ".$table." WHERE ".implode('AND ',$condition);
            if( count($extendedCondition) > 0 ) {
                $cQryStr .= "AND ".implode('AND ', $extendedCondition);
            }

            $cQry = $this->pdo->query($cQryStr);

            if( $cQry->rowCount() > 0 ) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function update($table, $array, $whereArray, $uniqueArray = [])
    {
        $result = [
            'data' => [
                'affectedRow' => 0,
                'isDuplicate' => false,
            ],
        ];

        $fields = [];
        $executeArray = [];

        foreach ($array as $key => $val) {
            $fields[] = $key.' = :'.$key;
            $executeArray[':'.$key] = $val;
        }

        $fieldsString = implode(', ',$fields);
        $result['data']['isDuplicate'] = $this->isAlreadyExists($table, $array, $uniqueArray, $whereArray);

        if($result['data']['isDuplicate'] === false && ($whereArray !== null || (is_array($whereArray) && count($whereArray) > 0))) {
            $whereCond = $this->prepareWhereArray($whereArray);

            $qryStr = 'UPDATE '.$table.' SET '. $fieldsString . $whereCond;

            try {
                if ($this->debugMode) {
                    echo $qryStr;
                    echo PHP_EOL;
                    print_r($executeArray);
                    echo PHP_EOL;
                }

                $qry = $this->pdo->prepare($qryStr);
                $qry->execute($executeArray);

                $result['data']['affectedRow'] = $qry->rowCount();
            }
            catch (PDOException $PDOException){
                throw new Exception($PDOException->getMessage() . ' Query: '.$qryStr.' '.$PDOException->getTraceAsString());
            }
        }

        return $result;
    }

    /**
     * Prepare where array
     *
     * @param $whereArray string|array
     * @return string
     */
    protected function prepareWhereArray($whereArray)
    {
        if(is_array($whereArray)) {
            $affectedTo = [];

            foreach($whereArray as $key=>$val){
                $affectedTo[] = $key." = '".$val."'";
            }

            $whereCond = ' WHERE '.implode(" AND ", $affectedTo);
        } else {
            $whereCond = ' WHERE '.$whereArray;
        }

        return $whereCond;
    }

    /**
     * @inheritDoc
     */
    public function delete($table, $whereArray)
    {
        $result = [
            'data' => [
                'affectedRow' => 0,
            ],
        ];

        if($whereArray !== null || (is_array($whereArray) && count($whereArray)) > 0 ){
            $whereCond = $this->prepareWhereArray($whereArray);

            $qryStr = 'DELETE FROM '.$table.' '.$whereCond;

            try {
                if ($this->debugMode) {
                    echo $qryStr;
                    echo PHP_EOL;
                }

                $qry = $this->pdo->prepare($qryStr);
                $qry->execute();

                $result['data']['affectedRow'] = $qry->rowCount();
                $result['debug'] = ['string' => $qryStr, 'value' => $whereArray, 'method' => null];

            }
            catch (PDOException $PDOException){
                throw new Exception($PDOException->getMessage() . ' Query: '.$qryStr.' '.$PDOException->getTraceAsString());
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function setDebugMode($mode)
    {
        $this->debugMode = $mode;
    }

    /**
     * @inheritDoc
     */
    public function getQuerySuffix($string = '', $whereCondition = '', $limit = 0, $offset = 0)
    {
        $resultArray = [
            'condition' => '',
            'limit' => '',
            'string' => '',
        ];

        if (strlen($whereCondition) > 0) {
            $string .= ' WHERE ' . $whereCondition;
            $resultArray['condition'] = 'WHERE ' . $whereCondition;
        }

        if ($limit > 0) {
            $string .= " LIMIT $limit";

            if ($offset > 0) {
                $string .= " OFFSET $offset";
            }

            $resultArray['limit'] = "$offset, $limit";
        }

        $resultArray['string'] = $string;

        return $resultArray;
    }
}
