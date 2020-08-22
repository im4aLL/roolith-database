<?php
namespace Roolith;

use Roolith\Drivers\PdoDriver;
use Roolith\Exceptions\Exception;
use Roolith\Interfaces\DatabaseInterface;
use Roolith\Interfaces\DriverInterface;
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
            $this->connect($config);
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

        try {
            return $this->driver->connect($config);
        } catch (Exception $e) {
            return false;
        }
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
        $this->driver->resetConditionalQueryString();

        return $this->result;
    }

    /**
     * @inheritDoc
     */
    public function first()
    {
        $this->get();

        if ($this->total > 0) {
            return $this->result[0];
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        $this->get();

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
        $conditionQueryString = $this->driver->buildConditionQueryString([
            'name' => 'id',
            'value' => $id,
        ]);

        return $this->select([
            'condition' => $this->driver->getQuerySuffix('', $conditionQueryString)['string'],
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
    public function paginate($param)
    {
        $paginate = new Paginate($param);

        if (is_callable($this->queryFn)) {
            call_user_func($this->queryFn, $this->whereCondition, $paginate->limit(), $paginate->offset());
            $this->queryFn = null;
        } else {
            $this->select([])->get();
        }

        $this->whereCondition = '';
        $this->driver->resetConditionalQueryString();

        $paginate->setItems($this->result);

        return $paginate;
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

        $this->queryFn = function ($whereCondition = '', $limit = 0, $offset = 0) use ($string, $method) {
            try {
                $string = $this->driver->getQuerySuffix($string, $whereCondition, $limit, $offset)['string'];

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

        $this->queryFn = function ($whereCondition = '', $limit = 0, $offset = 0) use ($array) {
            try {
                $querySuffix = $this->driver->getQuerySuffix('', $whereCondition, $limit, $offset);

                if (strlen($querySuffix['limit']) > 0) {
                    $array['limit'] = $querySuffix['limit'];
                }

                if (strlen($whereCondition) > 0) {
                    $array['condition'] = $this->driver->getQuerySuffix('', $whereCondition)['string'];
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
    public function debugMode($mode = true)
    {
        if ($this->driver) {
            $this->driver->setDebugMode($mode);
        }

        return $this;
    }
}
