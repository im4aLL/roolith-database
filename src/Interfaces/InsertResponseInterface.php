<?php
namespace Roolith\Store\Interfaces;


interface InsertResponseInterface extends QueryResponseInterface
{
    /**
     * @return int
     */
    public function affectedRow();

    /**
     * @return int
     */
    public function insertedId();

    /**
     * @return bool
     */
    public function isDuplicate();
}