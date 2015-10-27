<?php

namespace Fastpress\Arrow\Test\Model;

use Fastpress\Arrow\FluentModel;

/**
 * @property mixed $id
 * @property mixed $name
 */
class FluentUser extends FluentModel
{
    public function getTableName()
    {
        return 'user';
    }
}
