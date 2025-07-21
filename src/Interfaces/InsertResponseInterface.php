<?php
namespace Roolith\Store\Interfaces;


interface InsertResponseInterface extends QueryResponseInterface
{
    /**
     * @return int
     */
    public function affectedRow(): int;

    /**
     * @return int
     */
    public function insertedId(): int;

    /**
     * @return bool
     */
    public function isDuplicate(): bool;
}