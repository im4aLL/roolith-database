<?php
namespace Roolith\Store\Responses;


use Roolith\Store\Interfaces\UpdateResponseInterface;

class UpdateResponse implements UpdateResponseInterface
{
    protected $affectedRow;
    protected $isDuplicate;

    public function __construct($result = [])
    {
        $this->affectedRow = isset($result['affectedRow']) ? $result['affectedRow'] : 0;
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