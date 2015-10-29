<?php

namespace Fastpress\Arrow\Test;

use Fastpress\Arrow\ORM;
use Fastpress\Arrow\Test\Model\FluentUser;
use Fastpress\Arrow\Test\Model\User;

class ModelTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ORM
     */
    private $orm;

    protected function setUp()
    {
        $this->orm = new ORM();
        $this->orm->connect('sqlite::memory:');

        $queries[] = <<<'TAG'
CREATE TABLE user (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT
)
TAG;
        $queries[] = <<<'TAG'
CREATE TABLE post (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    content TEXT NOT NULL,
    date_created DATETIME NOT NULL
)
TAG;

        foreach ($queries as $sql) {
            $this->orm->execute($sql);
        }
    }

    public function testSave()
    {
        $user = new User();
        $user->name = 'John';
        $sql = 'SELECT COUNT(*) FROM user';

        $this->assertEquals('0', $this->orm->execute($sql)->fetchColumn(), 'Table should be empty before insert');
        $this->assertNull($user->id, 'Primary key should be null for insert');
        $user->save();
        $this->assertEquals('1', $this->orm->execute($sql)->fetchColumn(), 'Table should not be empty after insert');
        $this->assertNotNull($user->id);
    }

    /**
     * @expectedException \PDOException
     * @expectedExceptionMessage table user has no column named missing_column
     */
    public function testSaveMissingColumn()
    {
        $user = new User();
        $user->missing_column = 'x';
        $user->save();
    }

    /**
     * @expectedException \PDOException
     * @expectedExceptionMessageRegExp /Integrity constraint violation.*UNIQUE constraint/
     */
    public function testSaveDuplicate()
    {
        $user = new User();
        $user->name = 'x';
        $user->save();
        $user->save();
    }

    public function testUpdate()
    {
        $user = new User();
        $user->name = 'John';
        $user->save();

        $user->name = 'Jane';
        $user->update();

        $sql = 'SELECT COUNT(*) FROM user WHERE name = ?';
        $johnCount = $this->orm->execute($sql, ['John'])->fetchColumn();
        $this->assertEquals('0', $johnCount);
        $janeCount = $this->orm->execute($sql, ['Jane'])->fetchColumn();
        $this->assertEquals('1', $janeCount);
    }

    public function testDelete()
    {
        $user = new User();
        $user->name = 'John';
        $user->save();

        $sql = 'SELECT COUNT(*) FROM user';
        $this->assertEquals('1', $this->orm->execute($sql)->fetchColumn());

        $user->delete();
        $this->assertEquals('0', $this->orm->execute($sql)->fetchColumn());
    }

    public function testFluentAll()
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

    public function testFluentOne()
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

    public function testFluentFetch()
    {
        $john = new User();
        $john->name = 'John';
        $john->save();

        $user = new FluentUser();
        $user->eq('name', 'John')->fetch();
        $this->assertEquals('John', $user->name);
    }

    public function testFluentFetchPrimaryKey()
    {
        $john = new User();
        $john->id = 2;
        $john->name = 'John';
        $john->save();

        $user = new FluentUser();
        $user->fetch(2);
        $this->assertEquals('John', $user->name);
    }
}
