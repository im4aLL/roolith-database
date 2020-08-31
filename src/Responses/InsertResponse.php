<?php
namespace Roolith\Store\Responses;


use Roolith\Store\Interfaces\InsertResponseInterface;

class InsertResponse implements InsertResponseInterface
{
    protected $affectedRow;
    protected $insertedId;
    protected $isDuplicate;

    public function __construct($result = [])
    {
        $this->affectedRow = isset($result['affectedRow']) ?  $result['affectedRow'] : 0;
        $this->insertedId = isset($result['insertedId']) ? $result['insertedId'] : 0;
        $this->isDuplicate = isset($result['isDuplicate']) ? $result['isDuplicate'] : 0;
    }

    /**
     * @inheritDoc
     */
    public function affectedRow()
    {
        return $this->affectedRow;
    }

    /**
     * @inheritDoc
     */
    public function insertedId()
    {
        return $this->insertedId;
    }

    /**
     * @inheritDoc
     */
    public function isDuplicate()
    {
        return $this->isDuplicate;
    }

    /**
     * @inheritDoc
     */
    public function success()
    {
        return !$this->isDuplicate() && $this->insertedId() > 0;
    }
}