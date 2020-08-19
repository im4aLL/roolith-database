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
     * @inheritDoc
     */
    public function query($string, $method = DbConstant::DEFAULT_PDO_FETCH_METHOD)
    {
        $result = [
            'total' => null,
            'data' => null,
            'debug' => null,
        ];

        try {
            $qry = $this->pdo->prepare($string);
            $qry->execute();
            $qry->setFetchMode($method);

            if ($this->startsWith($string, 'SELECT')) {
                $result['data'] = $qry->fetchAll();
            }

            $result['total'] = $qry->rowCount();
            $result['debug'] = ['string' => $string, 'value' => NULL, 'method' => $method];
        } catch (PDOException $PDOException) {
            throw new Exception($PDOException->getMessage() .' '. $PDOException->getTraceAsString());
        }

        return $result;
    }

    protected function startsWith($haystack, $needle)
    {
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== FALSE;
    }

    /**
     * @inheritDoc
     */
    public function buildConditionQueryString($array)
    {
        // TODO: Implement buildConditionQueryString() method.
    }

    /**
     * @inheritDoc
     */
    public function limitNumberOfRowsString($total, $offset)
    {
        // TODO: Implement limitNumberOfRowsString() method.
    }

    /**
     * @inheritDoc
     */
    public function select($table, $array)
    {
        $result = [
            'total' => null,
            'data' => null,
            'debug' => null,
        ];

        $fieldString = $this->buildFieldSelectString($array);
        $qryStr = $this->buildQueryString($table, $fieldString, $array);

        try {
            $qry = $this->pdo->prepare($qryStr);
            $qry->execute();

            if (isset($array['method'])) {
                $qry->setFetchMode($array['method']);
            } else {
                $qry->setFetchMode(DbConstant::DEFAULT_PDO_FETCH_METHOD);
            }

            $result['data'] = $qry->fetchAll();
            $result['total'] = $qry->rowCount();
            $result['debug'] = ['string' => $qryStr, 'value' => $array, 'method' => (isset($qryArray['method']) ? $array['method'] : DbConstant::DEFAULT_PDO_FETCH_METHOD)];
        }
        catch (PDOException $PDOException){
            throw new Exception($PDOException->getMessage() . ' Query: '.$qryStr.' '.$PDOException->getTraceAsString());
        }

        return $result;
    }

    protected function buildFieldSelectString($array)
    {
        return (isset($array['field']) && count($array['field']) > 0) ? implode(', ', $array['field']): '*';
    }

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
            'debug' => null,
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
                $qry = $this->pdo->prepare($qryStr);
                $qry->execute($executeArray);

                $result['data']['affectedRow'] = $qry->rowCount();
                $result['data']['insertedId'] = $this->pdo->lastInsertId();
                $result['debug'] = ['string' => $qryStr, 'value' => $executeArray, 'method' => null];
            }
            catch (PDOException $PDOException){
                throw new Exception($PDOException->getMessage() . ' Query: '.$qryStr.' '.$PDOException->getTraceAsString());
            }
        }

        return $result;
    }

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
            'debug' => null,
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
            if(is_array($whereArray)) {
                $affectedTo = [];

                foreach($whereArray as $key=>$val){
                    $affectedTo[] = $key." = '".$val."'";
                }

                $whereCond = ' WHERE '.implode(" AND ", $affectedTo);
            } else {
                $whereCond = ' WHERE '.$whereArray;
            }

            $qryStr = 'UPDATE '.$table.' SET '. $fieldsString . $whereCond;

            try {
                $qry = $this->pdo->prepare($qryStr);
                $qry->execute($executeArray);

                $result['data']['affectedRow'] = $qry->rowCount();
                $result['debug'] = ['string' => $qryStr, 'value' => $executeArray, 'method' => null];
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
    public function delete($table, $whereArray)
    {
        $result = [
            'data' => [
                'affectedRow' => 0,
            ],
            'debug' => null,
        ];

        if($whereArray !== null || (is_array($whereArray) && count($whereArray)) > 0 ){
            if(is_array($whereArray)) {
                $affectedTo = array();
                foreach($whereArray as $key=>$val) {
                    $affectedTo[] = $key." = '".$val."'";
                }
                $whereCond = 'WHERE '.implode(" AND ", $affectedTo);
            }
            else {
                $whereCond = 'WHERE '.$whereArray;
            }

            $qryStr = 'DELETE FROM '.$table.' '.$whereCond;

            try {
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
}
