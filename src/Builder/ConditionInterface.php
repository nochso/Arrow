<?php

namespace Fastpress\Arrow\Builder;

interface ConditionInterface
{
    /**
     * Returns the parameters/placeholders required by this condition.
     *
     * Must always return an array, even if it's empty.
     *
     * @return array
     */
    public function getParameters();

    /**
     * @return string
     */
    public function toString();
}
