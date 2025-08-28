<?php
namespace Roolith\Store\Drivers;

use PDO;
use PDOException;
use Roolith\Store\Constants\DbConstant;
use Roolith\Store\Exceptions\Exception;
use Roolith\Store\Interfaces\DriverInterface;

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
    public function connect($config): bool
    {
        if (is_array($config) && count($config) === 0) {
            throw new Exception('Invalid configuration!');
        }

        try {
            $this->pdo = $this->getPdo($config);
        } catch (PDOException $PDOException) {
            throw new Exception($PDOException->getMessage() .' '. $PDOException->getTraceAsString());
        }

        return true;
    }

    protected function getPdo($config): PDO
    {
        if (is_string($config)) {
            return $this->getPdoByDsn($config);
        }

        $type = isset($config['type']) ? strtolower($config['type']) : strtolower(DbConstant::DEFAULT_TYPE);
        $user = $config['user'];
        $pass = $config['pass'];
        $host = $config['host'];
        $port = $config['port'] ?? DbConstant::DEFAULT_PORT[DbConstant::DEFAULT_TYPE];
        $dbname = $config['name'];

        $dsn = $type.":host=$host;port=$port;dbname=$dbname";

        return $this->getPdoByDsn($dsn, $type, $user, $pass);
    }

    protected function getPdoByDsn($dsn, $type = null, $user = null, $pass = null): PDO
    {
        $opt = [];

        if ($type === 'mysql') {
            $opt = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
            ];
        }

        if ($type !== null && $user !== null && $pass !== null) {
            return new PDO($dsn, $user, $pass, $opt);
        }

        return new PDO($dsn);
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): bool
    {
        $this->pdo = null;

        return true;
    }

    /**
     * Reset
     *
     * @return $this
     */
    public function reset(): PdoDriver
    {
        $this->whereCondition = '';

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function query($string, $method = DbConstant::DEFAULT_PDO_FETCH_METHOD): array
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

            if (str_starts_with(strtolower(trim($string)), 'select')) {
                $result['data'] = $qry->fetchAll();
            }

            $result['total'] = $qry->rowCount();
        } catch (PDOException $PDOException) {
            throw new Exception($PDOException->getMessage() .' '. $PDOException->getTraceAsString());
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function buildConditionQueryString($array): string
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
    public function resetConditionalQueryString(): bool
    {
        $this->whereCondition = '';

        return true;
    }

    /**
     * @inheritDoc
     */
    public function select($table, $array): iterable
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
    protected function buildFieldSelectString($array): string
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
    protected function buildQueryString($table, $fieldString, $array): string
    {
        $qryStr = 'SELECT '.$fieldString.' FROM `'.$table.'` '.($array['condition'] ?? '');

        if(isset($array['groupBy'])) {
            $qryStr .= ' GROUP BY '.$array['groupBy'];
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
    public function insert(string $table, array $array, array $uniqueArray = [])
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
    protected function isAlreadyExists($table, array $array = [], array $uniqueArray = [], array $whereArray = []): bool
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
    public function update(string $table, array $array, array $whereArray, array $uniqueArray = [])
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

        if($result['data']['isDuplicate'] === false) {
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
    protected function prepareWhereArray($whereArray): string
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
    public function delete(string $table, array $whereArray)
    {
        $result = [
            'data' => [
                'affectedRow' => 0,
            ],
        ];

        if(count($whereArray) > 0){
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
    public function setDebugMode(bool $mode): DriverInterface
    {
        $this->debugMode = $mode;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getQuerySuffix(string $string = '', string $whereCondition = '', int $limit = 0, int $offset = 0): array
    {
        $resultArray = [
            'condition' => '',
            'limit' => '',
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
