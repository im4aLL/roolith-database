<?php
namespace Roolith;

use Roolith\Drivers\PdoDriver;
use Roolith\Interfaces\DatabaseInterface;
use Roolith\Interfaces\DriverInterface;
use Roolith\Interfaces\PaginatorInterface;
use Roolith\Responses\DeleteResponse;
use Roolith\Responses\InsertResponse;
use Roolith\Responses\UpdateResponse;

class Database implements DatabaseInterface
{
    protected $driver;
    protected $result;
    protected $total = 0;
    protected $queryDebug;
    protected $tableName;

    public function __construct($config = [], DriverInterface $driver = null)
    {
        if ($driver) {
            $this->driver = $driver;
        }

        if (count($config) > 0) {
            try {
                $this->connect($config);
            } catch (Exceptions\Exception $e) {
                echo $e->getMessage();
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function connect($config)
    {
        if (!$this->driver) {
            if (isset($config['type'])) {
                switch ($config['type']) {
                    case 'MySQL':
                    case 'PostgreSQL':
                    case 'SQLite':
                    case 'SQL':
                    default:
                        $this->driver = new PdoDriver();
                        break;
                }
            } else {
                $this->driver = new PdoDriver();
            }
        }

        return $this->driver->connect($config);
    }

    /**
     * @inheritDoc
     */
    public function disconnect()
    {
        if (!$this->driver) {
            return false;
        }

        return $this->driver->disconnect();
    }

    /**
     * @inheritDoc
     */
    public function reset()
    {
        $this->result = null;
        $this->total = 0;
        $this->queryDebug = null;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get()
    {
        return $this->result;
    }

    /**
     * @inheritDoc
     */
    public function first()
    {
        if ($this->count() > 0) {
            return $this->get()[0];
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return $this->total;
    }

    /**
     * @inheritDoc
     */
    public function debug()
    {
        return $this->queryDebug;
    }

    /**
     * @inheritDoc
     */
    public function where($name, $value)
    {
        // TODO: Implement where() method.
    }

    /**
     * @inheritDoc
     */
    public function orWhere($name, $value)
    {
        // TODO: Implement orWhere() method.
    }

    /**
     * @inheritDoc
     */
    public function find($id)
    {
        // TODO: Implement find() method.
    }

    /**
     * @inheritDoc
     */
    public function pluck($nameArray)
    {
        // TODO: Implement pluck() method.
    }

    /**
     * @inheritDoc
     */
    public function paginate($number)
    {
        // TODO: Implement paginate() method.
    }

    /**
     * @inheritDoc
     */
    public function table($name)
    {
        $this->tableName = $name;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function query($string, $method = null)
    {
        $this->reset();

        try {
            $resultArray = $method ? $this->driver->query($string, $method): $this->driver->query($string);
            $this->result = $resultArray['data'];
            $this->total = $resultArray['total'];
            $this->queryDebug = $resultArray['debug'];
        } catch (Exceptions\Exception $e) {
            echo $e->getMessage();
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function select($array)
    {
        $this->reset();

        try {
            $resultArray = $this->driver->select($this->tableName, $array);
            $this->result = $resultArray['data'];
            $this->total = $resultArray['total'];
            $this->queryDebug = $resultArray['debug'];
        } catch (Exceptions\Exception $e) {
            echo $e->getMessage();
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function insert($array, $uniqueArray = [])
    {
        $this->reset();

        try {
            $resultArray = $this->driver->insert($this->tableName, $array, $uniqueArray);
            $this->queryDebug = $resultArray['debug'];

            return new InsertResponse($resultArray['data']);
        } catch (Exceptions\Exception $e) {
            echo $e->getMessage();
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function update($array, $whereArray, $uniqueArray = [])
    {
        $this->reset();

        try {
            $resultArray = $this->driver->update($this->tableName, $array, $whereArray, $uniqueArray);
            $this->queryDebug = $resultArray['debug'];

            return new UpdateResponse($resultArray['data']);
        } catch (Exceptions\Exception $e) {
            echo $e->getMessage();
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function delete($whereArray)
    {
        $this->reset();

        try {
            $resultArray = $this->driver->delete($this->tableName, $whereArray);
            $this->queryDebug = $resultArray['debug'];

            return new DeleteResponse($resultArray['data']);
        } catch (Exceptions\Exception $e) {
            echo $e->getMessage();
        }

        return false;
    }
}
