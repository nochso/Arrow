<?php

namespace Fastpress\Arrow\Test\Model;

use Fastpress\Arrow\Model;

/**
 * @property mixed $id
 * @property mixed $name
 */
class User extends Model
{
    public function findByName($name)
    {
        // 404 Reusable query builder stuff not found
        return new User();
    }
}
