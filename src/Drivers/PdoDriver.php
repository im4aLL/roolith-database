<?php
namespace Roolith\Store\Drivers;

use PDO;
use PDOException;
use Roolith\Store\Constants\DbConstant;
use Roolith\Store\Exceptions\Exception;
use Roolith\Store\Exceptions\InvalidArgumentException;
use Roolith\Store\Interfaces\DriverInterface;

class PdoDriver implements DriverInterface
{
    protected $pdo;
    protected $debugMode;
    protected $whereCondition;
    protected $whereBindings = [];
    protected $whereCounter = 0;

    public function __construct()
    {
        $this->whereCondition = "";
        $this->whereBindings = [];
        $this->whereCounter = 0;
        $this->debugMode = false;
    }

    /**
     * @inheritDoc
     */
    public function connect($config): bool
    {
        if (is_array($config) && count($config) === 0) {
            throw new Exception("Invalid configuration!");
        }

        try {
            $this->pdo = $this->getPdo($config);
        } catch (PDOException $PDOException) {
            throw new Exception(
                $PDOException->getMessage() .
                    " " .
                    $PDOException->getTraceAsString(),
            );
        }

        return true;
    }

    protected function getPdo($config): PDO
    {
        if (is_string($config)) {
            return $this->getPdoByDsn($config);
        }

        $type = isset($config["type"])
            ? strtolower($config["type"])
            : strtolower(DbConstant::DEFAULT_TYPE);
        $user = $config["user"];
        $pass = $config["pass"];
        $host = $config["host"];
        $port =
            $config["port"] ??
            DbConstant::DEFAULT_PORT[DbConstant::DEFAULT_TYPE];
        $dbname = $config["name"];

        $dsn = $type . ":host=$host;port=$port;dbname=$dbname";

        return $this->getPdoByDsn($dsn, $type, $user, $pass);
    }

    protected function getPdoByDsn(
        $dsn,
        $type = null,
        $user = null,
        $pass = null,
    ): PDO {
        $opt = [];

        if ($type === "mysql") {
            $opt = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'UTF8'",
            ];
        }

        if ($type !== null && $user !== null && $pass !== null) {
            return new PDO($dsn, $user, $pass, $opt);
        }

        return new PDO($dsn);
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): bool
    {
        $this->pdo = null;

        return true;
    }

    /**
     * Output debug query in monospaced font.
     *
     * Uses <pre><code> with monospace styling in browser context,
     * plain text in CLI context.
     *
     * @param string $string SQL query string.
     * @param mixed $bindings Optional bound values to dump.
     * @return void
     */
    protected function logDebug(string $string, $bindings = null): void
    {
        if (!$this->debugMode) {
            return;
        }

        $isCli = PHP_SAPI === 'cli';

        if ($isCli) {
            echo $string . PHP_EOL;

            if ($bindings !== null) {
                print_r($bindings);
                echo PHP_EOL;
            }

            return;
        }

        $style =
            'background:#1e1e1e;' .
            'color:#e6e6e6;' .
            'font-family:monospace;' .
            'font-size:13px;' .
            'line-height:1.5;' .
            'padding:12px 14px;' .
            'border-radius:8px;' .
            'border:1px solid #333;' .
            'overflow-x:auto;' .
            'white-space:pre-wrap;' .
            'word-break:break-word;' .
            'margin:8px 0;';

        echo '<pre style="' .
            $style .
            '"><code>' .
            htmlspecialchars($string, ENT_QUOTES, 'UTF-8') .
            '</code></pre>';

        if ($bindings !== null) {
            echo '<pre style="' .
                $style .
                '"><code>' .
                htmlspecialchars(
                    print_r($bindings, true),
                    ENT_QUOTES,
                    'UTF-8',
                ) .
                '</code></pre>';
        }
    }

    /**
     * Reset
     *
     * @return $this
     */
    public function reset(): PdoDriver
    {
        $this->whereCondition = "";
        $this->whereBindings = [];
        $this->whereCounter = 0;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function query(
        $string,
        $method = DbConstant::DEFAULT_PDO_FETCH_METHOD,
        $bindings = [],
    ): array {
        $this->reset();
        if ($method === null) {
            $method = DbConstant::DEFAULT_PDO_FETCH_METHOD;
        }

        $result = [
            "total" => null,
            "data" => null,
        ];

        try {
            $this->logDebug($string, $bindings ?: null);

            $qry = $this->pdo->prepare($string);
            $qry->execute($bindings ?: null);
            $qry->setFetchMode($method);

            if (str_starts_with(strtolower(trim($string)), "select")) {
                $result["data"] = $qry->fetchAll();
            }

            $result["total"] = $qry->rowCount();
        } catch (PDOException $PDOException) {
            throw new Exception(
                $PDOException->getMessage() .
                    " " .
                    $PDOException->getTraceAsString(),
            );
        }

        return $result;
    }

    /**
     * Execute a query without returning any result.
     *
     * @param string $string The SQL query to execute.
     * @return mixed
     */
    public function execute(string $string, array $bindings = []): mixed
    {
        try {
            $this->logDebug($string, $bindings ?: null);

            $qry = $this->pdo->prepare($string);
            return $qry->execute($bindings ?: null);
        } catch (PDOException $PDOException) {
            throw new Exception(
                $PDOException->getMessage() .
                    " " .
                    $PDOException->getTraceAsString(),
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function buildConditionQueryString($array): string
    {
        $name = $array["name"] ?? null;
        $value = $array["value"] ?? null;
        $expression = strtoupper(trim($array["expression"] ?? "="));
        $operator = strtoupper(trim($array["operator"] ?? "AND"));

        if (!in_array($operator, ["AND", "OR"], true)) {
            throw new InvalidArgumentException("Invalid condition operator.");
        }

        $allowedExpressions = [
            "=",
            "!=",
            "<>",
            "<",
            "<=",
            ">",
            ">=",
            "LIKE",
            "NOT LIKE",
            "IN",
            "NOT IN",
        ];
        if (!in_array($expression, $allowedExpressions, true)) {
            throw new InvalidArgumentException("Invalid condition expression.");
        }

        $column = $this->quoteIdentifier($name);

        if (strlen($this->whereCondition) > 0) {
            $this->whereCondition .= " " . $operator . " ";
        }

        if ($value === null) {
            if (in_array($expression, ["!=", "<>"], true)) {
                $this->whereCondition .= $column . " IS NOT NULL";
            } else {
                $this->whereCondition .= $column . " IS NULL";
            }

            return $this->whereCondition;
        }

        if (is_array($value)) {
            if (!in_array($expression, ["IN", "NOT IN"], true)) {
                $expression = "IN";
            }

            if (count($value) === 0) {
                throw new InvalidArgumentException("IN condition requires values.");
            }

            $placeholders = [];
            foreach (array_values($value) as $item) {
                $placeholder = ":where" . $this->whereCounter++;
                $placeholders[] = $placeholder;
                $this->whereBindings[$placeholder] = $item;
            }

            $this->whereCondition .=
                $column . " " . $expression . " (" . implode(", ", $placeholders) . ")";

            return $this->whereCondition;
        }

        $placeholder = ":where" . $this->whereCounter++;
        $this->whereBindings[$placeholder] = $value;
        $this->whereCondition .= $column . " " . $expression . " " . $placeholder;

        return $this->whereCondition;
    }

    /**
     * Get bindings collected by buildConditionQueryString().
     *
     * @return array
     */
    public function getWhereBindings(): array
    {
        return $this->whereBindings;
    }

    /**
     * Quote a column or table identifier.
     *
     * Supports dot notation (table.column). Rejects anything outside
     * [A-Za-z0-9_.] after stripping backticks.
     *
     * @param mixed $name
     * @return string
     */
    protected function quoteIdentifier($name): string
    {
        if (!is_string($name) || $name === "") {
            throw new InvalidArgumentException("Invalid identifier.");
        }

        $parts = explode(".", str_replace("`", "", $name));
        foreach ($parts as $part) {
            if (!preg_match("/^[A-Za-z0-9_]+$/", $part)) {
                throw new InvalidArgumentException("Invalid identifier.");
            }
        }

        return "`" . implode("`.`", $parts) . "`";
    }

    /**
     * Quote a single field for the SELECT list.
     *
     * Allows *, table.*, identifiers and optional AS alias.
     *
     * @param mixed $field
     * @return string
     */
    protected function quoteField($field): string
    {
        if (!is_string($field) || $field === "") {
            throw new InvalidArgumentException("Invalid field.");
        }

        $alias = "";
        if (preg_match("/\s+AS\s+/i", $field)) {
            $split = preg_split("/\s+AS\s+/i", $field);
            if (count($split) !== 2) {
                throw new InvalidArgumentException("Invalid field.");
            }
            $field = trim($split[0]);
            $alias = trim($split[1]);
        }

        if ($field === "*") {
            $quoted = "*";
        } elseif (substr($field, -2) === ".*") {
            $quoted = $this->quoteIdentifier(substr($field, 0, -2)) . ".*";
        } else {
            $quoted = $this->quoteIdentifier($field);
        }

        if ($alias !== "") {
            $quoted .= " AS " . $this->quoteIdentifier($alias);
        }

        return $quoted;
    }

    /**
     * Sanitize ORDER BY / GROUP BY clause.
     *
     * @param mixed $value
     * @return string
     */
    protected function sanitizeOrderClause($value): string
    {
        if (!is_string($value) || trim($value) === "") {
            throw new InvalidArgumentException("Invalid order clause.");
        }

        $items = [];
        foreach (explode(",", $value) as $chunk) {
            $chunk = trim($chunk);
            if (!preg_match("/^([A-Za-z0-9_.]+)(\s+(ASC|DESC))?$/i", $chunk, $matches)) {
                throw new InvalidArgumentException("Invalid order clause.");
            }
            $quoted = $this->quoteIdentifier($matches[1]);
            if (!empty($matches[3])) {
                $quoted .= " " . strtoupper($matches[3]);
            }
            $items[] = $quoted;
        }

        return implode(", ", $items);
    }

    /**
     * Sanitize LIMIT clause. Allows "10" or "0, 10".
     *
     * @param mixed $limit
     * @return string
     */
    protected function sanitizeLimit($limit): string
    {
        if (is_int($limit)) {
            if ($limit < 0) {
                throw new InvalidArgumentException("Invalid limit.");
            }

            return (string) $limit;
        }

        if (is_string($limit) && preg_match("/^\d+(\s*,\s*\d+)?$/", trim($limit))) {
            return preg_replace("/\s+/", " ", trim($limit));
        }

        throw new InvalidArgumentException("Invalid limit.");
    }

    /**
     * @inheritDoc
     */
    public function resetConditionalQueryString(): bool
    {
        $this->whereCondition = "";
        $this->whereBindings = [];
        $this->whereCounter = 0;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function select($table, $array, $bindings = []): iterable
    {
        $result = [
            "total" => null,
            "data" => null,
        ];

        $fieldString = $this->buildFieldSelectString($array);
        $qryStr = $this->buildQueryString($table, $fieldString, $array);
        $allBindings = array_merge(
            $this->whereBindings,
            $array["bindings"] ?? [],
            $bindings,
        );

        try {
            $this->logDebug($qryStr, $allBindings ?: null);

            $qry = $this->pdo->prepare($qryStr);
            $qry->execute($allBindings ?: null);

            if (isset($array["method"])) {
                $qry->setFetchMode($array["method"]);
            } else {
                $qry->setFetchMode(DbConstant::DEFAULT_PDO_FETCH_METHOD);
            }

            $result["data"] = $qry->fetchAll();
            $result["total"] = $qry->rowCount();
        } catch (PDOException $PDOException) {
            throw new Exception(
                $PDOException->getMessage() .
                    " Query: " .
                    $qryStr .
                    " " .
                    $PDOException->getTraceAsString(),
            );
        }

        return $result;
    }

    /**
     * Build select field string
     *
     * @param $array
     * @return string
     */
    protected function buildFieldSelectString($array): string
    {
        if (!isset($array["field"]) || count($array["field"]) === 0) {
            return "*";
        }

        $fields = [];
        foreach ($array["field"] as $field) {
            $fields[] = $this->quoteField($field);
        }

        return implode(", ", $fields);
    }

    /**
     * Build select field query string
     *
     * @param $table
     * @param $fieldString
     * @param $array
     * @return string
     */
    protected function buildQueryString($table, $fieldString, $array): string
    {
        $qryStr =
            "SELECT " .
            $fieldString .
            " FROM " .
            $this->quoteIdentifier($table) .
            " " .
            ($array["condition"] ?? "");

        if (isset($array["groupBy"])) {
            $qryStr .= " GROUP BY " . $this->sanitizeOrderClause($array["groupBy"]);
        }

        if (isset($array["orderBy"])) {
            $qryStr .= " ORDER BY " . $this->sanitizeOrderClause($array["orderBy"]);
        }

        if (isset($array["limit"])) {
            $qryStr .= " LIMIT " . $this->sanitizeLimit($array["limit"]);
        }

        return $qryStr;
    }

    /**
     * @inheritDoc
     */
    public function insert(string $table, array $array, array $uniqueArray = [])
    {
        $result = [
            "data" => [
                "affectedRow" => 0,
                "insertedId" => 0,
                "isDuplicate" => false,
            ],
        ];

        if (count($array) === 0) {
            throw new InvalidArgumentException("Insert data is empty.");
        }

        $columns = [];
        $placeholders = [];
        $executeArray = [];
        $index = 0;

        foreach ($array as $key => $val) {
            $columns[] = $this->quoteIdentifier($key);
            $placeholder = ":ins" . $index++;
            $placeholders[] = $placeholder;
            $executeArray[$placeholder] = $val;
        }

        $fieldString = implode(",", $placeholders);
        $rawFieldsStr = implode(",", $columns);

        $result["data"]["isDuplicate"] = $this->isAlreadyExists(
            $table,
            $array,
            $uniqueArray,
        );

        if ($result["data"]["isDuplicate"] === false) {
            $qryStr =
                "INSERT INTO " .
                $this->quoteIdentifier($table) .
                " (" .
                $rawFieldsStr .
                ") VALUES(" .
                $fieldString .
                ")";

            try {
                $this->logDebug($qryStr, $executeArray);

                $qry = $this->pdo->prepare($qryStr);
                $qry->execute($executeArray);

                $result["data"]["affectedRow"] = $qry->rowCount();
                $result["data"]["insertedId"] = $this->pdo->lastInsertId();
            } catch (PDOException $PDOException) {
                throw new Exception(
                    $PDOException->getMessage() .
                        " Query: " .
                        $qryStr .
                        " " .
                        $PDOException->getTraceAsString(),
                );
            }
        }

        return $result;
    }

    /**
     * If record already exists
     *
     * @param $table
     * @param array $array
     * @param array $uniqueArray
     * @param array $whereArray
     * @return bool
     */
    protected function isAlreadyExists(
        $table,
        array $array = [],
        array $uniqueArray = [],
        array $whereArray = [],
    ): bool {
        $result = false;

        if (count($uniqueArray) > 0) {
            $bindings = [];
            $condition = [];
            $index = 0;
            foreach ($uniqueArray as $fieldName) {
                if (!array_key_exists($fieldName, $array)) {
                    throw new InvalidArgumentException("Unique field missing from data.");
                }
                $value = $array[$fieldName];
                if (is_array($value) || is_object($value)) {
                    throw new InvalidArgumentException("Unique field value must be scalar or null.");
                }
                $column = $this->quoteIdentifier($fieldName);
                if ($value === null) {
                    $condition[] = $column . " IS NULL";
                    continue;
                }
                $placeholder = ":uniq" . $index++;
                $condition[] = $column . " = " . $placeholder;
                $bindings[$placeholder] = $value;
            }

            $extendedCondition = [];
            $extIndex = 0;
            foreach ($whereArray as $whereKey => $whereVal) {
                if (is_array($whereVal) || is_object($whereVal)) {
                    throw new InvalidArgumentException("Where value must be scalar or null.");
                }
                $column = $this->quoteIdentifier($whereKey);
                if ($whereVal === null) {
                    $extendedCondition[] = $column . " IS NOT NULL";
                    continue;
                }
                $placeholder = ":uniqw" . $extIndex++;
                $extendedCondition[] = $column . " != " . $placeholder;
                $bindings[$placeholder] = $whereVal;
            }

            $cQryStr =
                "SELECT 1 FROM " .
                $this->quoteIdentifier($table) .
                " WHERE " .
                implode(" AND ", $condition);
            if (count($extendedCondition) > 0) {
                $cQryStr .= " AND " . implode(" AND ", $extendedCondition);
            }
            $cQryStr .= " LIMIT 1";

            try {
                $this->logDebug($cQryStr, $bindings ?: null);

                $cQry = $this->pdo->prepare($cQryStr);
                $cQry->execute($bindings);

                if ($cQry->fetchColumn() !== false) {
                    $result = true;
                }
            } catch (PDOException $PDOException) {
                throw new Exception(
                    $PDOException->getMessage() .
                        " Query: " .
                        $cQryStr .
                        " " .
                        $PDOException->getTraceAsString(),
                );
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function update(
        string $table,
        array $array,
        array $whereArray,
        array $uniqueArray = [],
    ) {
        $result = [
            "data" => [
                "affectedRow" => 0,
                "isDuplicate" => false,
            ],
        ];

        $fields = [];
        $executeArray = [];

        $setIndex = 0;
        foreach ($array as $key => $val) {
            $placeholder = ":set" . $setIndex++;
            $fields[] = $this->quoteIdentifier($key) . " = " . $placeholder;
            $executeArray[$placeholder] = $val;
        }

        $fieldsString = implode(", ", $fields);
        $result["data"]["isDuplicate"] = $this->isAlreadyExists(
            $table,
            $array,
            $uniqueArray,
            $whereArray,
        );

        if ($result["data"]["isDuplicate"] === false) {
            [$whereCond, $whereBindings] = $this->prepareWhereArray($whereArray);
            $executeArray = array_merge($executeArray, $whereBindings);

            $qryStr =
                "UPDATE " .
                $this->quoteIdentifier($table) .
                " SET " .
                $fieldsString .
                $whereCond;

            try {
                $this->logDebug($qryStr, $executeArray);

                $qry = $this->pdo->prepare($qryStr);
                $qry->execute($executeArray);

                $result["data"]["affectedRow"] = $qry->rowCount();
            } catch (PDOException $PDOException) {
                throw new Exception(
                    $PDOException->getMessage() .
                        " Query: " .
                        $qryStr .
                        " " .
                        $PDOException->getTraceAsString(),
                );
            }
        }

        return $result;
    }

    /**
     * Prepare where array
     *
     * @param $whereArray string|array
     * @return array [string $whereCond, array $bindings]
     */
    protected function prepareWhereArray($whereArray): array
    {
        if (is_array($whereArray)) {
            if (count($whereArray) === 0) {
                throw new InvalidArgumentException("Where condition is empty.");
            }

            $affectedTo = [];
            $bindings = [];
            $index = 0;

            foreach ($whereArray as $key => $val) {
                $column = $this->quoteIdentifier($key);
                if ($val === null) {
                    $affectedTo[] = $column . " IS NULL";
                    continue;
                }
                if (is_array($val)) {
                    if (count($val) === 0) {
                        throw new InvalidArgumentException("IN condition requires values.");
                    }
                    $placeholders = [];
                    foreach (array_values($val) as $item) {
                        $placeholder = ":w" . $index . "_" . count($placeholders);
                        $placeholders[] = $placeholder;
                        $bindings[$placeholder] = $item;
                    }
                    $affectedTo[] = $column . " IN (" . implode(", ", $placeholders) . ")";
                    $index++;
                    continue;
                }
                $placeholder = ":w" . $index++;
                $affectedTo[] = $column . " = " . $placeholder;
                $bindings[$placeholder] = $val;
            }

            return [" WHERE " . implode(" AND ", $affectedTo), $bindings];
        }

        return [" WHERE " . $whereArray, []];
    }

    /**
     * @inheritDoc
     */
    public function delete(string $table, array $whereArray)
    {
        $result = [
            "data" => [
                "affectedRow" => 0,
            ],
        ];

        if (count($whereArray) > 0) {
            [$whereCond, $whereBindings] = $this->prepareWhereArray($whereArray);

            $qryStr =
                "DELETE FROM " . $this->quoteIdentifier($table) . " " . $whereCond;

            try {
                $this->logDebug($qryStr, $whereBindings ?: null);

                $qry = $this->pdo->prepare($qryStr);
                $qry->execute($whereBindings ?: null);

                $result["data"]["affectedRow"] = $qry->rowCount();
                $result["debug"] = [
                    "string" => $qryStr,
                    "value" => $whereArray,
                    "method" => null,
                ];
            } catch (PDOException $PDOException) {
                throw new Exception(
                    $PDOException->getMessage() .
                        " Query: " .
                        $qryStr .
                        " " .
                        $PDOException->getTraceAsString(),
                );
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function setDebugMode(bool $mode): DriverInterface
    {
        $this->debugMode = $mode;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getQuerySuffix(
        string $string = "",
        string $whereCondition = "",
        int $limit = 0,
        int $offset = 0,
    ): array {
        $resultArray = [
            "condition" => "",
            "limit" => "",
        ];

        if (strlen($whereCondition) > 0) {
            $string .= " WHERE " . $whereCondition;
            $resultArray["condition"] = "WHERE " . $whereCondition;
        }

        $limit = (int) $limit;
        $offset = (int) $offset;

        if ($limit > 0) {
            $string .= " LIMIT $limit";

            if ($offset > 0) {
                $string .= " OFFSET $offset";
            }

            $resultArray["limit"] = "$offset, $limit";
        }

        $resultArray["string"] = $string;

        return $resultArray;
    }
}
