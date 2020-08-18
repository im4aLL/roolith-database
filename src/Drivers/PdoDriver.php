<?php
namespace Roolith\Drivers;

use PDO;
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
        } catch (\PDOException $PDOException) {
            throw new Exception($PDOException->getMessage());
        }

        return $this->pdo instanceof PDO;
    }

    protected function getPdo($config)
    {
        $type = isset($config['type']) ? $config['type'] : DbConstant::DEFAULT_TYPE;
        $host = $config['host'];
        $port = isset($config['port']) ? $config['port'] : DbConstant::DEFAULT_PORT[$type];
        $dbname = $config['name'];
        $user = $config['user'];
        $pass = $config['pass'];

        $dsn = "mysql:host=$host;port=$port;dbname=$dbname";
        $opt = array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'"
        );

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
    public function query($string, $method = PDO::FETCH_OBJ)
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

            return $result;
        } catch (\PDOException $PDOException) {
            throw new Exception($PDOException->getMessage());
        }
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
        // TODO: Implement select() method.
    }

    /**
     * @inheritDoc
     */
    public function insert($array, $uniqueArray = [])
    {
        // TODO: Implement insert() method.
    }

    /**
     * @inheritDoc
     */
    public function update($table, $array, $whereArray, $uniqueArray = [])
    {
        // TODO: Implement update() method.
    }

    /**
     * @inheritDoc
     */
    public function delete($table, $whereArray)
    {
        // TODO: Implement delete() method.
    }
}