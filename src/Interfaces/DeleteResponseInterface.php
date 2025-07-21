<?php
namespace Roolith\Store\Interfaces;


interface DeleteResponseInterface extends QueryResponseInterface
{
    /**
     * @return int
     */
    public function affectedRow(): int;
}