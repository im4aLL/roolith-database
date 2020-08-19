<?php
namespace Roolith\Responses;


use Roolith\Interfaces\DeleteResponseInterface;

class DeleteResponse implements DeleteResponseInterface
{
    protected $affectedRow;


    public function __construct($result = [])
    {
        $this->affectedRow = $result['affectedRow'];
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
    public function success()
    {
        return $this->affectedRow() > 0;
    }
}