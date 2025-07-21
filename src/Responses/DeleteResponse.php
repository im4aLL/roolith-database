<?php
namespace Roolith\Store\Responses;


use Roolith\Store\Interfaces\DeleteResponseInterface;

class DeleteResponse implements DeleteResponseInterface
{
    protected $affectedRow;


    public function __construct($result = [])
    {
        $this->affectedRow = $result['affectedRow'] ?? 0;
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
    public function success(): bool
    {
        return $this->affectedRow() > 0;
    }
}