<?php

namespace Fastpress\Arrow\Builder;

trait FluentBuilderTrait
{
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

#region Public fluent select methods

    /**
     * Filter where column equals value.
     *
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function eq($column, $value)
    {
        return $this->addSimpleWhere($column, '=', $value);
    }

    /**
     * Filter where column equals value.
     *
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function where($column, $value)
    {
        return $this->eq($column, $value);
    }

    /**
     * Filter where column does not equal value.
     *
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function neq($column, $value)
    {
        return $this->addSimpleWhere($column, '!=', $value);
    }

    /**
     * Filter where column is less than value.
     *
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function lt($column, $value)
    {
        return $this->addSimpleWhere($column, '<', $value);
    }

    /**
     * Filter where column is less than or equal value.
     *
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function lte($column, $value)
    {
        return $this->addSimpleWhere($column, '<=', $value);
    }

    /**
     * Filter where column is greater than value.
     *
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function gt($column, $value)
    {
        return $this->addSimpleWhere($column, '>', $value);
    }

    /**
     * Filter where column is greater than or equal value.
     *
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function gte($column, $value)
    {
        return $this->addSimpleWhere($column, '>=', $value);
    }

    /**
     * Filter where column matches list of values.
     *
     * @param string $column
     * @param array  $values
     *
     * @return static
     */
    public function in($column, $values)
    {
        return $this->addSimpleWhere($column, 'IN', $values);
    }

    /**
     * Filter where column does not match any of the value.
     *
     * @param string $column
     * @param array  $values
     *
     * @return static
     */
    public function notIn($column, $values)
    {
        return $this->addSimpleWhere($column, 'NOT IN', $values);
    }

    /**
     * Filter where column matches value using the LIKE operator.
     *
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function like($column, $value)
    {
        return $this->addSimpleWhere($column, 'LIKE', $value);
    }

    /**
     * Filter where column does not match value using the LIKE operator.
     *
     * @param string $column
     * @param string $value
     *
     * @return static
     */
    public function notLike($column, $value)
    {
        return $this->addSimpleWhere($column, 'NOT LIKE', $value);
    }

    /**
     * Filter where column is SQL NULL.
     *
     * @param string $column
     *
     * @return static
     */
    public function isNull($column)
    {
        return $this->addSimpleWhere($column, 'IS NULL', '');
    }

    /**
     * Filter where column is not SQL NULL.
     *
     * @param string $column
     *
     * @return static
     */
    public function notNull($column)
    {
        return $this->addSimpleWhere($column, 'IS NOT NULL', '');
    }

    /**
     * Limit the amount of maximum rows returned.
     *
     * @param int $limit
     * @param int $offset
     *
     * @return static
     */
    public function limit($limit, $offset = null)
    {
        $this->queryBuilder->setLimit($limit, $offset);
        return $this;
    }

    /**
     * Offset the results.
     *
     * @param int $offset
     *
     * @return static
     */
    public function offset($offset)
    {
        $this->queryBuilder->setOffset($offset);
        return $this;
    }

#endregion

#region Public fluent termination methods

    /**
     * Places the resulting row into the current model object (Active Record style).
     */
    public function fetch()
    {
    }

    /**
     * Returns a list of results.
     * 
     * @return static[]
     */
    public function all()
    {
        $statement = $this->queryBuilder->getStatement();
        $result = [];
        while ($columns = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $model = new static();
            $model->setColumns($columns);
            $result[] = $model;
        }
        $this->queryBuilder = null;
        return $result;
    }

    /**
     * Returns a single result.
     * 
     * @return static
     */
    public function one()
    {
    }

#endregion

    /**
     * @param string $column
     * @param string $operator
     * @param mixed  $value
     *
     * @return $this
     */
    protected function addSimpleWhere($column, $operator, $value)
    {
        if ($this->queryBuilder === null) {
            $this->queryBuilder = new QueryBuilder($this->getTableName());
        }
        $this->queryBuilder->addWhere(new SimpleWhere($column, $operator, $value));
        return $this;
    }
}
