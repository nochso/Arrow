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

    /**
     * Returns the table name based on the class name.
     *
     * By default the last part of fully qualified class name is converted from
     * camelCase to snake_case.
     *
     * e.g. `\vendor\name\UserRole` becomes `user_role`
     *
     * You can override this method when extending Model to either:
     * - Specify a the table name of a specific model
     * - Generate a table name dynamically, similar to this implementation
     *
     * @return string
     */
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

    /**
     * Returns the name of the primary key column.
     *
     * By default this is `id`.
     *
     * You can override this for each inherited Model just like `getTableName()`
     *
     * @return string
     */
    public function getPrimaryKeyName()
    {
        return 'id';
    }

    /**
     * Saves (INSERTs) the current model instance to database.
     *
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
     * Updates the existing database row with current model data.
     *
     * @return bool True on success, false otherwise.
     */
    public function update()
    {
        $model = new DynamicFluentModel();
        $model->withModel($this);
        $primaryKeyValue = $this->columns[$this->getPrimaryKeyName()];
        $rowCount = $model->where($this->getPrimaryKeyName(), $primaryKeyValue)->updateAll();
        return $rowCount > 0;
    }

    /**
     * Deletes the current model.
     *
     * @return bool True on success, false otherwise.
     */
    public function delete()
    {
        $model = new DynamicFluentModel();
        $model->withModel($this);
        $primaryKeyValue = $this->columns[$this->getPrimaryKeyName()];
        $rowCount = $model->where($this->getPrimaryKeyName(), $primaryKeyValue)->deleteAll();
        return $rowCount > 0;
    }
}
