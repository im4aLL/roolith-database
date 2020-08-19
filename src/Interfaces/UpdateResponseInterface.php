<?php
namespace Roolith\Interfaces;


interface UpdateResponseInterface extends QueryResponseInterface
{
    /**
     * @return int
     */
    public function affectedRow();

    /**
     * @return bool
     */
    public function isDuplicate();
}