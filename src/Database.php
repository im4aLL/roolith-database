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
    protected $tableName;
    protected $queryFn;
    protected $whereCondition;

    public function __construct($config = [], DriverInterface $driver = null)
    {
        $this->whereCondition = '';

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

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get()
    {
        if (is_callable($this->queryFn)) {
            call_user_func($this->queryFn, $this->whereCondition);
            $this->queryFn = null;
        } else {
            $this->select([])->get();
        }

        $this->whereCondition = '';

        return $this->result;
    }

    /**
     * @inheritDoc
     */
    public function first()
    {
        $this->get();

        if ($this->count() > 0) {
            return $this->result[0];
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
    public function where($name, $value, $expression = '=')
    {
        $this->whereCondition = $this->driver->buildConditionQueryString([
            'name' => $name,
            'value' => $value,
            'operator' => 'AND',
            'expression' => $expression,
        ]);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function orWhere($name, $value, $expression = '=')
    {
        $this->whereCondition = $this->driver->buildConditionQueryString([
            'name' => $name,
            'value' => $value,
            'operator' => 'OR',
            'expression' => $expression,
        ]);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function find($id)
    {
        return $this->select([
            'condition' => 'WHERE `id` = '.$id,
        ])->first();
    }

    /**
     * @inheritDoc
     */
    public function pluck($nameArray)
    {
        $opt = [
            'field' => $nameArray
        ];

        if ($this->whereCondition) {
            $opt['condition'] = $this->whereCondition;
        }

        return $this->select($opt)->get();
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

        $this->queryFn = function ($whereCondition = '') use ($string, $method) {
            try {
                if (strlen($whereCondition) > 0) {
                    $string .= ' WHERE ' . $whereCondition;
                }

                $resultArray = $method ? $this->driver->query($string, $method): $this->driver->query($string);
                $this->result = $resultArray['data'];
                $this->total = $resultArray['total'];
            } catch (Exceptions\Exception $e) {
                echo $e->getMessage();
            }
        };

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function select($array)
    {
        $this->reset();

        $this->queryFn = function ($whereCondition = '') use ($array) {
            try {
                if (strlen($whereCondition) > 0) {
                    $array['condition'] = 'WHERE ' . $whereCondition;
                }

                $resultArray = $this->driver->select($this->tableName, $array);
                $this->result = $resultArray['data'];
                $this->total = $resultArray['total'];
            } catch (Exceptions\Exception $e) {
                echo $e->getMessage();
            }
        };

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

            return new InsertResponse($resultArray['data']);
        } catch (Exceptions\Exception $e) {
            echo $e->getMessage();
        }

        return new InsertResponse();
    }

    /**
     * @inheritDoc
     */
    public function update($array, $whereArray, $uniqueArray = [])
    {
        $this->reset();

        try {
            $resultArray = $this->driver->update($this->tableName, $array, $whereArray, $uniqueArray);

            return new UpdateResponse($resultArray['data']);
        } catch (Exceptions\Exception $e) {
            echo $e->getMessage();
        }

        return new UpdateResponse();
    }

    /**
     * @inheritDoc
     */
    public function delete($whereArray)
    {
        $this->reset();

        try {
            $resultArray = $this->driver->delete($this->tableName, $whereArray);

            return new DeleteResponse($resultArray['data']);
        } catch (Exceptions\Exception $e) {
            echo $e->getMessage();
        }

        return new DeleteResponse();
    }

    /**
     * @inheritDoc
     */
    public function debugMode()
    {
        if ($this->driver) {
            $this->driver->setDebugMode(true);
        }

        return $this;
    }
}
