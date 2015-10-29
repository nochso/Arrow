<?php

namespace Fastpress\Arrow\Builder;

use Fastpress\Arrow\ORM;

/**
 * SimpleWhere can represent most SQL conditions and returns SQL strings with
 * placeholders and their parameters in the proper order.
 *
 * This can easily be refactored if it becomes too complex: different condition
 * classes each implementing the two methods `toString` and `getParameters`.
 * Then they can all be used by QueryBuilder.
 */
class SimpleWhere implements ConditionInterface
{
    /**
     * @var string
     */
    protected $columnName;
    /**
     * @var string
     */
    protected $operator;
    /**
     * @var mixed|array|null
     */
    protected $value;

    /**
     * @param string $columnName
     * @param string $operator
     * @param mixed  $value
     */
    public function __construct($columnName, $operator, $value)
    {
        $this->columnName = $columnName;
        $this->operator = $operator;
        $this->value = $value;
    }

    /**
     * Returns the parameters/placeholders required by this condition.
     *
     * Must always return an array, even if it's empty.
     *
     * @return array
     */
    public function getParameters()
    {
        switch ($this->operator) {
            case 'IN':
            case 'NOT IN':
                if (!is_array($this->value)) {
                    throw new \InvalidArgumentException('Expecting array when using SQL operator ' . $this->operator);
                }
                return $this->value;

            case 'IS NULL':
            case 'IS NOT NULL':
                return [];

            default:
                return [$this->value];
        }
    }

    /**
     * @return string
     */
    public function toString()
    {
        $orm = ORM::$instance;
        $quotedColumn = $orm->quoteIdentifier($this->columnName);
        switch ($this->operator) {

            // Conditions with multiple values
            case 'IN':
            case 'NOT IN':
                $placeholders = implode(',', array_fill(0, count($this->value), '?'));
                return sprintf('%s %s (%s)', $quotedColumn, $this->operator, $placeholders);

            // Conditions without values
            case 'IS NULL':
            case 'IS NOT NULL':
                return sprintf('%s %s', $quotedColumn, $this->operator);

            // Conditions with a single value
            case 'LIKE':
                return sprintf("%s %s ? ESCAPE '='", $quotedColumn, $this->operator);

            default:
                return sprintf('%s %s ?', $quotedColumn, $this->operator);
        }
    }
}
