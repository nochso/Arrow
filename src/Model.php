<?php

namespace Fastpress\Arrow;

use Fastpress\Arrow\Builder\QueryBuilder;

class Model
{
    protected $orm;
    protected $columns = array();

    public function __construct()
    {
        $this->orm = ORM::$instance;
    }

    /**
     * @return bool
     */
    public function save()
    {
        $queryBuilder = new QueryBuilder($this->getTableName());
        $queryBuilder->setQueryType(QueryBuilder::QUERY_TYPE_INSERT);
        $queryBuilder->setModelData($this->columns);
        $statement = $queryBuilder->getStatement();
        if ($statement === false) {
            return false;
        }
        $this->columns[$this->getPrimaryKeyName()] = $this->orm->pdo->lastInsertId();
        return true;
    }

    /**
     * Returns all values indexed by column names.
     *
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Overwrite all values with an array indexed by column names.
     *
     * @param array $columns
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;
    }

    public function getTableName()
    {
        $fqn = get_class($this);
        $pos = strrpos($fqn, '\\');

        if ($pos === false) {
            $name = $fqn;
        } else {
            $name = substr($fqn, $pos + 1);
        }
        return ltrim(strtolower(preg_replace('/[A-Z]+/', '_$0', $name)), '_');
    }

    public function getPrimaryKeyName()
    {
        return 'id';
    }

    public function delete()
    {
        $primaryKeyName = $this->getPrimaryKeyName();
        $primaryKeyValue = $this->columns[$primaryKeyName];
        $sql = sprintf('DELETE FROM %s WHERE %s = ?',
            $this->quoteIdentifier($this->getTableName()),
            $this->quoteIdentifier($primaryKeyName)
        );
        return false !== $this->orm->execute($sql, array($primaryKeyValue));
    }

    /**
     * @return int Rows affected.
     */
    public function update()
    {
        $params = array();
        $sets = array();
        foreach ($this->columns as $name => $value) {
            $params[] = $value;
            $sets[] = sprintf('%s = ?', $this->quoteIdentifier($name));
        }
        $params[] = $this->columns[$this->getPrimaryKeyName()];
        $sql = sprintf('UPDATE %s SET %s WHERE %s = ?',
            $this->quoteIdentifier($this->getTableName()),
            implode(',', $sets),
            $this->quoteIdentifier($this->getPrimaryKeyName())
        );
        $statement = $this->orm->execute($sql, $params);
        return $statement->rowCount();
    }

    /**
     * is utilized for reading data from inaccessible members.
     *
     * @param $name string
     *
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->columns[$name])) {
            return $this->columns[$name];
        }
        return null;
    }

    /**
     * run when writing data to inaccessible members.
     *
     * @param $name string
     * @param $value mixed
     */
    public function __set($name, $value)
    {
        $this->columns[$name] = $value;
    }

    private function quoteIdentifier($identifier)
    {
        return $this->orm->quoteIdentifier($identifier);
    }
}
