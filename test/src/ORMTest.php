<?php

namespace Fastpress\Arrow\Test;

use Fastpress\Arrow\ORM;
use Fastpress\Arrow\Test\Model\User;

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
}