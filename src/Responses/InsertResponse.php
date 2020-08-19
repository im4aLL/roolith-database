<?php
namespace Roolith\Responses;


use Roolith\Interfaces\InsertResponseInterface;

class InsertResponse implements InsertResponseInterface
{
    protected $affectedRow;
    protected $insertedId;
    protected $isDuplicate;

    public function __construct($result = [])
    {
        $this->affectedRow = $result['affectedRow'];
        $this->insertedId = $result['insertedId'];
        $this->isDuplicate = $result['isDuplicate'];
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