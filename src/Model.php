<?php

namespace Fastpress\Arrow;

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
        $orm = $this->orm;
        $columns = $this->getColumns();
        $quotedColumnNames = $this->quoteIdentifier(array_keys($columns));
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        $sql = sprintf('INSERT INTO %s (%s) VALUES (%s)',
            $this->quoteIdentifier($this->getTableName()),
            $quotedColumnNames,
            $placeholders
        );
        $values = array_values($columns);
        $success = $orm->execute($sql, $values);
        if ($success === false) {
            return false;
        }

        $this->columns[$this->getPrimaryKeyName()] = $orm->pdo->lastInsertId();
        return true;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
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
     * Selects a row by primary key, replacing any previous data.
     *
     * @param $primaryKey
     *
     * @return bool
     */
    public function get($primaryKey)
    {
        $sql = sprintf('SELECT * FROM %s WHERE %s = ?',
            $this->quoteIdentifier($this->getTableName()),
            $this->quoteIdentifier($this->getPrimaryKeyName())
        );
        $params = array($primaryKey);
        $statement = $this->orm->execute($sql, $params);
        $columns = $statement->fetch(\PDO::FETCH_ASSOC);
        if ($columns === false) {
            $this->columns = array();
            return false;
        }
        $this->columns = $columns;
        return true;
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
