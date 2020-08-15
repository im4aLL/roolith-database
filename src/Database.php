<?php
namespace Roolith;

use Roolith\Interfaces\DatabaseInterface;
use Roolith\Interfaces\DriverInterface;
use Roolith\Interfaces\Paginator;

class Database implements DatabaseInterface
{
    protected $driver;

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
        // TODO: Implement get() method.
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
        // TODO: Implement count() method.
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
        // TODO: Implement query() method.
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