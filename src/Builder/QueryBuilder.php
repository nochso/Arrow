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
     * @var SimpleWhere[] A list of WHERE condition objects.
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

    public function __construct($tableName)
    {
        $this->orm = ORM::$instance;
        $this->tableName = $tableName;
        $this->queryType = self::QUERY_TYPE_SELECT;
    }

    /**
     * @param SimpleWhere $where
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
     * Returns the beginning of any SQL statement, depending on $this->queryType
     *
     * @return string
     */
    protected function getTypeSQL()
    {
        $sql = '';
        switch ($this->queryType) {
            case self::QUERY_TYPE_SELECT:
                $top = '';
                if ($this->limit !== null && $this->detectLimitStyle() === self::LIMIT_STYLE_TOP) {
                    $top = ' TOP ' . $this->limit;
                }
                $sql = sprintf('SELECT%s * FROM %s',
                    $top,
                    $this->orm->quoteIdentifier($this->tableName)
                );
                break;

            // TODO Rewrite for this project
//            case self::QUERY_TYPE_DELETE:
//                $sql = 'DELETE FROM `' . $this->tableName . '`';
//                break;
//
//            case self::QUERY_TYPE_UPDATE:
//                $sql = 'UPDATE `' . $this->tableName . '` SET ';
//                if ($this->modelData instanceof \nochso\ORM\ResultSet) {
//                    $sql .= $this->getMultiUpdateSetsSQL();
//                } else {
//                    $sql .= $this->getUpdateSetsSQL();
//                }
//                break;
//
//            case self::QUERY_TYPE_INSERT:
//                $columnNames = '`' . implode('`, `', array_keys($this->modelData)) . '`';
//                $parameters = [];
//                foreach ($this->modelData as $key => $value) {
//                    $parameters[] = $this->addParameter($value);
//                }
//                $parameters = implode(', ', $parameters);
//                $sql = 'INSERT INTO `' . $this->tableName . '` (' . $columnNames . ') VALUES (' . $parameters . ')';
//                break;
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
     * Combines any where condition objects (SimpleWhere) into a single WHERE condition.
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
}
