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
        # Intializing
        $user = new User();
        $user->name = 'John';
        $user->save();

        $user->name = 'Jane';
        $user->update();

        # Delete only this user
        $user->delete();

        # READ / SELECT
//        $article = $blogs->findBySlug('why-i-love-php'); # maybe implementable in Blogs class.
//        echo $article->title; // Why I Love PHP
//        # UPDATE (auto)
//        $article->title= 'PHP SUCKS';
//        $article->update();
//        echo $article->title; // PHP SUCKS
//        # INSERT
//        $article->title = 'New Blog';
//        $article->save(); # unset primary key, INSERT using remaining columns, set primary key of fresh row
//        # DELETE
//        $article->delete(); # DELETE * FROM Blogs (dangerous)
//        # Ranged DELETE
//        $article->where('mail', 'foo@test.com')->deleteAll();

//        // New empty Model that knows about ORM class
//        $user = User::via($this->orm);
//        $user->id = '1';
//        $user->name = 'John';
//        $user->insert();
//
//        $sameUser = User::via($this->orm);
//        $sameUser->get(1);
//        echo "\n";
//        print_r($sameUser);
//
//        $user->name = 'Johnny';
//        $user->update();
//        $user->delete();
    }
}
