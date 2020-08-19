<?php
namespace Roolith\Interfaces;


interface DeleteResponseInterface extends QueryResponseInterface
{
    /**
     * @return int
     */
    public function affectedRow();
}