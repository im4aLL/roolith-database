<?php
namespace Roolith\Responses;


use Roolith\Interfaces\UpdateResponseInterface;

class UpdateResponse implements UpdateResponseInterface
{
    protected $affectedRow;
    protected $isDuplicate;

    public function __construct($result = [])
    {
        $this->affectedRow = $result['affectedRow'];
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
    public function isDuplicate()
    {
        return $this->isDuplicate;
    }

    /**
     * @inheritDoc
     */
    public function success()
    {
        return !$this->isDuplicate() && $this->affectedRow() > 0;
    }
}