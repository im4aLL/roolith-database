<?php
namespace Roolith\Store;

use Roolith\Store\Drivers\PdoDriver;
use Roolith\Store\Exceptions\Exception;
use Roolith\Store\Interfaces\DatabaseInterface;
use Roolith\Store\Interfaces\DriverInterface;
use Roolith\Store\Interfaces\PaginatorInterface;
use Roolith\Store\Responses\DeleteResponse;
use Roolith\Store\Responses\InsertResponse;
use Roolith\Store\Responses\UpdateResponse;

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
    public function connect($config): bool
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
    public function disconnect(): bool
    {
        if (!$this->driver) {
            return false;
        }

        return $this->driver->disconnect();
    }

    /**
     * @inheritDoc
     */
    public function reset(): DatabaseInterface
    {
        $this->result = null;
        $this->total = 0;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get(): iterable
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
    public function count(): int
    {
        $this->get();

        return $this->total;
    }

    /**
     * @inheritDoc
     */
    public function where($name, $value, string $expression = '='): DatabaseInterface
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
    public function orWhere($name, $value, string $expression = '='): DatabaseInterface
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
    public function pluck($nameArray): iterable
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
    public function paginate($array): PaginatorInterface
    {
        $paginate = new Paginate($array);

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
    public function table($name): DatabaseInterface
    {
        $this->tableName = $name;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function query($string, $method = null): DatabaseInterface
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
    public function select($array): DatabaseInterface
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
    public function insert($array, array $uniqueArray = []): InsertResponse
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
    public function update($array, $whereArray, array $uniqueArray = []): UpdateResponse
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
    public function delete($whereArray): DeleteResponse
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
    public function debugMode($mode = true): DatabaseInterface
    {
        if ($this->driver) {
            $this->driver->setDebugMode($mode);
        }

        return $this;
    }
}
