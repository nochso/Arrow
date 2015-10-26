<?php

namespace Fastpress\Yaar\Test\Model;

use Fastpress\Yaar\Model;

/**
 * @property mixed $id
 * @property mixed $name
 * @property mixed $role_id
 */
class User extends Model
{
    public function findByName($name)
    {
        // 404 Reusable query builder stuff not found
        return new User();
    }
}
