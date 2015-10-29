<?php

namespace Fastpress\Arrow\Builder;

/**
 * Adds fluent SQL query builder to classes inheriting from Model.
 *
 * Conditions like `where` and `like` return the same object you call them on.
 * That way you can chain queries like this:
 *
 * ```
 * $model->where('id', '1')->limit(5)->all();
 * ```
 *
 * The first two method calls collect conditions in a QueryBuilder. Calling
 * `all()` ends the chain by combining any previous calls into a single SQL
 * query and returning the result.
 *
 * If users want to keep it simple, they can still inherit Model without using this trait.
 */
trait FluentBuilderTrait
{
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

#region Public fluent methods

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
     * The second parameter allows you to optionally set the offset.
     *
     * @param int $limit
     * @param int $offset
     *
     * @see FluentBuilderTrait::offset
     *
     * @return static
     */
    public function limit($limit, $offset = null)
    {
        $this->init();
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
        $this->init();
        $this->queryBuilder->setOffset($offset);
        return $this;
    }

    /**
     * Sort the results by ascending order.
     *
     * @param string $column
     *
     * @return static
     */
    public function orderAsc($column)
    {
        $this->init();
        $this->queryBuilder->addOrder($column, 'ASC');
        return $this;
    }

    /**
     * Sort the results by descending order.
     *
     * @param string $column
     *
     * @return static
     */
    public function orderDesc($column)
    {
        $this->init();
        $this->queryBuilder->addOrder($column, 'DESC');
        return $this;
    }

#endregion

#region Public fluent termination methods

    /**
     * Places the resulting row into the current model object (Active Record style).
     *
     * @param null $primaryKey
     *
     * @return bool
     */
    public function fetch($primaryKey = null)
    {
        if ($primaryKey !== null) {
            // If a primary key was given, discard the previous QueryBuilder
            $this->reset();
            $this->eq($this->getPrimaryKeyName(), $primaryKey);
        } else {
            $this->init();
        }
        $this->limit(1);
        $statement = $this->queryBuilder->getStatement();
        $columns = $statement->fetch(\PDO::FETCH_ASSOC);
        $this->reset();
        if ($columns === false) {
            $this->setColumns([]);
            return false;
        }
        $this->setColumns($columns);
        return true;
    }

    /**
     * Returns a list of results.
     *
     * @return static[]
     */
    public function all()
    {
        $this->init();
        $statement = $this->queryBuilder->getStatement();
        $result = [];
        while ($columns = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $model = new static();
            $model->setColumns($columns);
            $result[] = $model;
        }
        $this->reset();
        return $result;
    }

    /**
     * Returns a single result.
     *
     * @return static
     */
    public function one()
    {
        $this->init();
        $this->limit(1);
        $statement = $this->queryBuilder->getStatement();
        $columns = $statement->fetch(\PDO::FETCH_ASSOC);
        if ($columns === false) {
            return null;
        }
        $model = new static();
        $model->setColumns($columns);
        $this->reset();
        return $model;
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
        $this->init();
        $this->queryBuilder->addWhere(new SimpleWhere($column, $operator, $value));
        return $this;
    }

    /**
     * Ensures there's a QueryBuilder to work with.
     */
    protected function init()
    {
        if ($this->queryBuilder === null) {
            $this->queryBuilder = new QueryBuilder($this->getTableName());
        }
    }

    /**
     * Resets the QueryBuilder by destroying it.
     */
    protected function reset()
    {
        $this->queryBuilder = null;
    }
}
