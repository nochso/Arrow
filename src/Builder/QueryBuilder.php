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
    protected $tableName;
    /**
     * @var SimpleWhere[]
     */
    protected $where = [];
    protected $limit;
    protected $offset;
    protected $order = [];
    protected $queryType;
    protected $parameters = [];

    const QUERY_TYPE_SELECT = 0;
    const QUERY_TYPE_DELETE = 1;
    const QUERY_TYPE_UPDATE = 2;
    const QUERY_TYPE_INSERT = 4;

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

    public function setLimit($limit, $offset = null)
    {
        $this->limit = $limit;
        if ($offset !== null) {
            $this->setOffset($offset);
        }
    }

    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    public function getSql()
    {
        return $this->getTypeSQL()
            . $this->getWhereSql()
            . $this->getLimitSql()
            . $this->getOffsetSql();
    }

    /**
     * @return false|\PDOStatement
     */
    public function getStatement()
    {
        return $this->orm->execute($this->getSql(), $this->parameters);
    }

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

    protected function getLimitSql()
    {
        if ($this->limit === null) {
            return '';
        }
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
