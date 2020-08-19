<?php
namespace Roolith\Constants;


use PDO;

class DbConstant
{
    const DEFAULT_TYPE = 'MySQL';
    const DEFAULT_PORT = [
        self::DEFAULT_TYPE => 3306
    ];
    const DEFAULT_PDO_FETCH_METHOD = PDO::FETCH_OBJ;
}
