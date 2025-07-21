<?php
namespace Roolith\Store\Interfaces;


interface UpdateResponseInterface extends QueryResponseInterface
{
    /**
     * @return int
     */
    public function affectedRow(): int;

    /**
     * @return bool
     */
    public function isDuplicate(): bool;
}