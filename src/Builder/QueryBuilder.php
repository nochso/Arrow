<?php

namespace Fastpress\Arrow\Builder;

use Fastpress\Arrow\ORM;

/**
 * QueryBuilder for SELECT, INSERT, UPDATE and DELETE statements.
 */
class QueryBuilder
{
    /**
     * @var \Fastpress\Arrow\ORM
     */
    protected $orm;
    /**
     * @var string
     */
    protected $tableName;
    /**
     * @var ConditionInterface[] A list of WHERE condition objects.
     */
    protected $where = [];
    /**
     * @var int Limit the amount of results.
     */
    protected $limit;
    /**
     * @var int Offset the result list.
     */
    protected $offset;
    /**
     * Not implemented yet. See ORM2.
     *
     * @var array List of ORDER BY conditions.
     */
    protected $order = [];
    /**
     * @var int Any of the QueryBuilder::QUERY_TYPE_* constants.
     */
    protected $queryType;
    /**
     * @var array
     */
    protected $modelData;
    /**
     * @var array 0-indexed array of `?` PDO placeholders.
     */
    protected $parameters = [];

    const QUERY_TYPE_SELECT = 0;
    const QUERY_TYPE_DELETE = 1;
    const QUERY_TYPE_UPDATE = 2;
    const QUERY_TYPE_INSERT = 4;
    /**
     * Whether to use `SELECT TOP 5` or `LIMIT 5`.
     */
    const LIMIT_STYLE_TOP = 0;
    const LIMIT_STYLE_LIMIT = 1;

    /**
     * @param string $tableName
     */
    public function __construct($tableName)
    {
        $this->orm = ORM::$instance;
        $this->tableName = $tableName;
        $this->queryType = self::QUERY_TYPE_SELECT;
    }

    /**
     * @param ConditionInterface $where
     */
    public function addWhere($where)
    {
        $this->where[] = $where;
    }

    /**
     * Limit the amount of maximum rows returned.
     *
     * The second parameter allows you to optionally set the offset.
     *
     * @param int      $limit
     * @param null|int $offset
     */
    public function setLimit($limit, $offset = null)
    {
        $this->limit = $limit;
        if ($offset !== null) {
            $this->setOffset($offset);
        }
    }

    /**
     * Offset the results.
     *
     * @param int $offset
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * @param $column
     * @param string $direction Either 'ASC' or 'DESC'
     */
    public function addOrder($column, $direction = 'ASC')
    {
        if ($direction !== 'ASC' && $direction !== 'DESC') {
            throw new \InvalidArgumentException('Parameter $direction must be either "ASC" or "DESC".');
        }
        $this->order[] = sprintf('%s %s', $this->orm->quoteIdentifier($column), $direction);
    }

    /**
     * Combines any conditions and settings into a single query.
     *
     * As placeholders are used, their parameters/values are added to $this->parameters
     *
     * @return string SQL query using ? placeholders.
     */
    public function getSql()
    {
        return $this->getTypeSQL()
            . $this->getWhereSql()
            . $this->getOrderSql()
            . $this->getLimitSql()
            . $this->getOffsetSql();
    }

    /**
     * Creates, executes and returns a statement based on this instance.
     *
     * @return false|\PDOStatement
     */
    public function getStatement()
    {
        return $this->orm->execute($this->getSql(), $this->parameters);
    }

    /**
     * @return int Any of the QueryBuilder::QUERY_TYPE_* constants.
     */
    public function getQueryType()
    {
        return $this->queryType;
    }

    /**
     * @param int $queryType Any of the QueryBuilder::QUERY_TYPE_* constants.
     */
    public function setQueryType($queryType)
    {
        $this->queryType = $queryType;
    }

    /**
     * @param array $modelData
     */
    public function setModelData($modelData)
    {
        $this->modelData = $modelData;
    }

    /**
     * Returns the beginning of any SQL statement, depending on $this->queryType.
     *
     * @return string
     */
    protected function getTypeSQL()
    {
        $sql = '';
        $quotedTableName = $this->orm->quoteIdentifier($this->tableName);
        switch ($this->queryType) {
            case self::QUERY_TYPE_SELECT:
                $top = '';
                if ($this->limit !== null && $this->detectLimitStyle() === self::LIMIT_STYLE_TOP) {
                    $top = ' TOP ' . $this->limit;
                }
                $sql = sprintf('SELECT%s * FROM %s',
                    $top,
                    $quotedTableName
                );
                break;

            case self::QUERY_TYPE_DELETE:
                $sql = sprintf('DELETE FROM %s', $quotedTableName);
                break;

            case self::QUERY_TYPE_UPDATE:
                $sets = array();
                foreach ($this->modelData as $columnName => $columnValue) {
                    $sets[] = sprintf('%s = ?', $this->orm->quoteIdentifier($columnName));
                    $this->parameters[] = $columnValue;
                }
                $sql = sprintf('UPDATE %s SET %s',
                    $quotedTableName,
                    implode(',', $sets)
                );
                break;

            case self::QUERY_TYPE_INSERT:
                $quotedColumnNames = $this->orm->quoteIdentifier(array_keys($this->modelData));
                $placeholders = implode(',', array_fill(0, count($this->modelData), '?'));
                $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)',
                    $quotedTableName,
                    $quotedColumnNames,
                    $placeholders
                );
                $this->parameters = array_merge($this->parameters, array_values($this->modelData));
                break;
        }
        return $sql;
    }

    /**
     * @return string
     */
    protected function getLimitSql()
    {
        if ($this->limit === null) {
            return '';
        }

        // getTypeSQL() already took care of this
        if ($this->detectLimitStyle() !== self::LIMIT_STYLE_LIMIT) {
            return '';
        }

        $syntax = ' LIMIT %d';
        $driver = $this->orm->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver === 'firebird') {
            $syntax = ' ROWS %d';
        }
        return sprintf($syntax, $this->limit);
    }

    protected function getOffsetSql()
    {
        if ($this->offset === null) {
            return '';
        }
        $syntax = ' OFFSET %d';
        $driver = $this->orm->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver == 'sqlsrv' || $driver == 'dblib' || $driver = 'mssql') {
            throw new \Exception('Using offsets in MS SQL is not supported.');
        }
        if ($driver === 'firebird') {
            $syntax = ' TO %d';
        }
        return sprintf($syntax, $this->offset);
    }

    /**
     * Whether to use `SELECT TOP 5` or `LIMIT 5`.
     *
     * @return int
     */
    protected function detectLimitStyle()
    {
        $driver = $this->orm->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        switch ($driver) {
            case 'sqlsrv':
            case 'dblib':
            case 'mssql':
                return self::LIMIT_STYLE_TOP;
            default:
                return self::LIMIT_STYLE_LIMIT;
        }
    }

    /**
     * Combines any conditions that implement ConditionInterface into a single WHERE SQL string.
     *
     * By default all conditions are combined with AND.
     *
     * @return string
     */
    protected function getWhereSql()
    {
        if (empty($this->where)) {
            return '';
        }
        $parts = [];
        foreach ($this->where as $where) {
            $parts[] = $where->toString();
            $this->parameters = array_merge($this->parameters, $where->getParameters());
        }
        return ' WHERE ' . implode(' AND ', $parts);
    }

    protected function getOrderSql()
    {
        if (empty($this->order)) {
            return '';
        }
        return sprintf(' ORDER BY %s', implode(',', $this->order));
    }
}
