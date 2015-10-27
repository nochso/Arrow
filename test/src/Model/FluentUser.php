<?php

namespace Fastpress\Arrow\Test\Model;

use Fastpress\Arrow\FluentModel;

/**
 * @property mixed $id
 * @property mixed $name
 */
class FluentUser extends FluentModel
{
    /**
     * Only needed because there's already a `User` in the same namespace.
     *
     * @return string
     */
    public function getTableName()
    {
        return 'user';
    }
}
