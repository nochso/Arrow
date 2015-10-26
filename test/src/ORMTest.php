<?php

namespace Fastpress\Yaar\Test;

use Fastpress\Yaar\ORM;
use Fastpress\Yaar\Test\Model\User;

class ORMTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ORM
     */
    private $orm;

    protected function setUp()
    {
        $this->orm = new ORM();
        $this->orm->connect('sqlite::memory:');
        $sql = <<<'TAG'
CREATE TABLE user (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT,
    role_id INTEGER
)
TAG;
        $this->orm->execute($sql);
    }

    public function testORM()
    {
        // New empty Model that knows about ORM class
        $user = User::via($this->orm);
        $user->id = '1';
        $user->name = 'John';
        $user->insert();

        $sameUser = User::via($this->orm);
        $sameUser->get(1);
        echo "\n";
        print_r($sameUser);

        $user->name = 'Johnny';
        $user->update();
        $user->delete();
    }
}
