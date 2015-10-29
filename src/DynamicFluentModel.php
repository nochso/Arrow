<?php

namespace Fastpress\Arrow;

/**
 * DynamicFluentModel lets you use the fluent interface without inheriting
 * from the Model class.
 *
 * The reasoning behind this is: Model's save/update/delete should use the
 * fluent methods even if the actual Model does not have the FluentBuilderTrait.
 */
final class DynamicFluentModel extends FluentModel
{
    /**
     * @var string
     */
    protected $tableName;
    /**
     * @var string
     */
    protected $primaryKeyName;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Prepares this model for any other existing Model.
     *
     * The original does not have to use the FluentBuilderTrait.
     *
     * @param Model $model
     */
    public function withModel(Model $model)
    {
        $this->withTable($model->getTableName(), $model->getPrimaryKeyName(), $model->getColumns());
    }

    /**
     * Prepares this model for any table.
     *
     * @param string      $tableName
     * @param null|string $primaryKeyName
     * @param null|array  $columns
     */
    public function withTable($tableName, $primaryKeyName = null, $columns = array())
    {
        $this->setTableName($tableName);
        $this->setPrimaryKeyName($primaryKeyName);
        $this->setColumns($columns);
    }

    /**
     * @return mixed
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;
    }

    /**
     * @return mixed
     */
    public function getPrimaryKeyName()
    {
        if ($this->primaryKeyName === null) {
            return parent::getPrimaryKeyName();
        }
        return $this->primaryKeyName;
    }

    /**
     * @param null|string $primaryKeyName
     */
    public function setPrimaryKeyName($primaryKeyName = null)
    {
        $this->primaryKeyName = $primaryKeyName;
    }
}
