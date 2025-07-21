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
        $this->affectedRow = $result['affectedRow'] ?? 0;
        $this->insertedId = $result['insertedId'] ?? 0;
        $this->isDuplicate = $result['isDuplicate'] ?? 0;
    }

    /**
     * @inheritDoc
     */
    public function affectedRow(): int
    {
        return $this->affectedRow;
    }

    /**
     * @inheritDoc
     */
    public function insertedId(): int
    {
        return $this->insertedId;
    }

    /**
     * @inheritDoc
     */
    public function isDuplicate(): bool
    {
        return $this->isDuplicate;
    }

    /**
     * @inheritDoc
     */
    public function success(): bool
    {
        return !$this->isDuplicate() && $this->insertedId() > 0;
    }
}