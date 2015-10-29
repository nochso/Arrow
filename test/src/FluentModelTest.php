<?php

namespace Fastpress\Arrow\Test;

use Fastpress\Arrow\ORM;
use Fastpress\Arrow\Test\Model\FluentUser;
use Fastpress\Arrow\Test\Model\User;

class FluentModelTest extends \PHPUnit_Framework_TestCase
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
    name TEXT
)
TAG;
        $this->orm->execute($sql);
    }

    public function testAll()
    {
        $user = new User();
        $user->name = 'John';
        $user->save();

        $user->id = null;
        $user->name = 'Jane';
        $user->save();

        $user->id = null;
        $user->name = 'Mark';
        $user->save();

        $user = new FluentUser();
        $users = $user->like('name', 'J%')->all();
        foreach ($users as $user) {
            $this->assertStringStartsWith('J', $user->name);
        }

        $users = $user->in('name', ['John', 'Jane'])->all();
        foreach ($users as $user) {
            $this->assertStringStartsWith('J', $user->name);
        }
    }

    public function testOne()
    {
        $john = new User();
        $john->name = 'John';
        $john->save();

        $query = new FluentUser();
        $user = $query->eq('name', 'John')->one();
        $this->assertEquals('John', $user->name);

        $missingUser = $user->eq('name', 'Waldo')->one();
        $this->assertNull($missingUser);
    }

    public function testFetch()
    {
        $john = new User();
        $john->name = 'John';
        $john->save();

        $user = new FluentUser();
        $user->eq('name', 'John')->fetch();
        $this->assertEquals('John', $user->name);
    }

    public function testFetchPrimaryKey()
    {
        $john = new User();
        $john->id = 2;
        $john->name = 'John';
        $john->save();

        $user = new FluentUser();
        $user->fetch(2);
        $this->assertEquals('John', $user->name);
    }

    public function testDelete()
    {
        $user = new User();
        $user->name = 'John';
        $user->save();

        $user->id = null;
        $user->name = 'Jane';
        $user->save();

        $user->id = null;
        $user->name = 'Mark';
        $user->save();

        $delete = new FluentUser();
        $deletedCount = $delete->where('name', 'John')->deleteAll();
        $this->assertEquals(1, $deletedCount);

        $count = $this->orm->execute('SELECT COUNT(*) FROM user')->fetchColumn();
        $this->assertEquals(2, $count);

        $delete->deleteAll();
        $count = $this->orm->execute('SELECT COUNT(*) FROM user')->fetchColumn();
        $this->assertEquals(0, $count);
    }

    public function testUpdate()
    {
        $user = new User();
        $user->name = 'John';
        $user->save();

        $user->id = null;
        $user->name = 'Jane';
        $user->save();

        $user->id = null;
        $user->name = 'Mark';
        $user->save();

        $fluentUser = new FluentUser();
        $fluentUser->name = 'oops';
        $fluentUser->updateAll();

        $names = $this->orm->execute('SELECT name FROM user')->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($names as $name) {
            $this->assertEquals('oops', $name);
        }
    }
}
