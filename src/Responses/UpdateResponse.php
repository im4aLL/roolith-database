<?php
namespace Roolith\Store\Responses;


use Roolith\Store\Interfaces\UpdateResponseInterface;

class UpdateResponse implements UpdateResponseInterface
{
    protected $affectedRow;
    protected $isDuplicate;

    public function __construct($result = [])
    {
        $this->affectedRow = $result['affectedRow'] ?? 0;
        $this->isDuplicate = $result['isDuplicate'] ?? false;
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
    public function isDuplicate(): bool
    {
        return $this->isDuplicate;
    }

    /**
     * @inheritDoc
     */
    public function success(): bool
    {
        return !$this->isDuplicate();
    }
}