<?php

namespace Fastpress\Arrow\Test;

use Fastpress\Arrow\DynamicFluentModel;
use Fastpress\Arrow\ORM;

class DynamicFluentModelTest extends \PHPUnit_Framework_TestCase
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
    identifier INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT
)
TAG;
        $this->orm->execute($sql);
    }

    public function testDynamic()
    {
        $userModel = DynamicFluentModel::create('user', 'identifier');
        $userModel->name = 'John';
        $userModel->save();
        $this->assertNotNull($userModel->identifier);

        $userModel->name = 'Jane';
        $userModel->update();

        $jane = $userModel->where('identifier', $userModel->identifier)->one();
        $this->assertEquals('Jane', $jane->name);
    }

    public function testColumns()
    {
        $user = DynamicFluentModel::create('user', 'identifier', ['name' => 'Sweet Dee']);
        $user->save();
        $this->assertEquals('1', $user->identifier);
    }
}
