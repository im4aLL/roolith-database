<?php
namespace Roolith;

use Roolith\Interfaces\DatabaseInterface;
use Roolith\Interfaces\DriverInterface;
use Roolith\Interfaces\PaginatorInterface;

class Database implements DatabaseInterface
{
    protected $driver;
    protected $result;
    protected $total = 0;
    protected $queryDebug;

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    /**
     * @inheritDoc
     */
    public function connect($config)
    {
        return $this->driver->connect($config);
    }

    /**
     * @inheritDoc
     */
    public function disconnect()
    {
        return $this->driver->disconnect();
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
        // TODO: Implement first() method.
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
    public function query($string, $method = null)
    {
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
    public function table($name)
    {
        // TODO: Implement table() method.
    }

    /**
     * @inheritDoc
     */
    public function select($array)
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
    public function update($array, $whereArray, $uniqueArray = [])
    {
        // TODO: Implement update() method.
    }

    /**
     * @inheritDoc
     */
    public function delete($whereArray)
    {
        // TODO: Implement delete() method.
    }
}