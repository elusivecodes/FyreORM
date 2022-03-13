<?php
declare(strict_types=1);

namespace Tests\Model;

use
    Fyre\ORM\Exceptions\ORMException,
    Fyre\ORM\ModelRegistry;

trait JoinTest
{

    public function testContainLeftJoinSql(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS Posts__id FROM posts AS Posts LEFT JOIN users AS Users ON Users.id = Posts.user_id LEFT JOIN addresses AS Addresses ON Addresses.user_id = Users.id',
            ModelRegistry::use('Posts')
                ->find()
                ->leftJoinWith('Users.Addresses')
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testContainInnerJoinSql(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS Posts__id FROM posts AS Posts INNER JOIN users AS Users ON Users.id = Posts.user_id INNER JOIN addresses AS Addresses ON Addresses.user_id = Users.id',
            ModelRegistry::use('Posts')
                ->find()
                ->innerJoinWith('Users.Addresses')
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testContainLeftJoinConditionsSql(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS Posts__id FROM posts AS Posts LEFT JOIN users AS Users ON Users.id = Posts.user_id LEFT JOIN addresses AS Addresses ON Addresses.user_id = Users.id AND Addresses.suburb = \'Test\'',
            ModelRegistry::use('Posts')
                ->find()
                ->leftJoinWith('Users.Addresses', [
                    'Addresses.suburb' => 'Test'
                ])
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testContainInnerJoinConditionsSql(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS Posts__id FROM posts AS Posts INNER JOIN users AS Users ON Users.id = Posts.user_id INNER JOIN addresses AS Addresses ON Addresses.user_id = Users.id AND Addresses.suburb = \'Test\'',
            ModelRegistry::use('Posts')
                ->find()
                ->innerJoinWith('Users.Addresses', [
                    'Addresses.suburb' => 'Test'
                ])
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testContainLeftJoinOverwrite(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS Posts__id FROM posts AS Posts LEFT JOIN users AS Users ON Users.id = Posts.user_id',
            ModelRegistry::use('Posts')
                ->find()
                ->innerJoinWith('Users')
                ->leftJoinWith('Users')
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testContainInnerJoinOverwrite(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS Posts__id FROM posts AS Posts INNER JOIN users AS Users ON Users.id = Posts.user_id',
            ModelRegistry::use('Posts')
                ->find()
                ->leftJoinWith('Users')
                ->innerJoinWith('Users')
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testContainLeftJoinMerge(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS Posts__id FROM posts AS Posts INNER JOIN users AS Users ON Users.id = Posts.user_id LEFT JOIN addresses AS Addresses ON Addresses.user_id = Users.id',
            ModelRegistry::use('Posts')
                ->find()
                ->innerJoinWith('Users')
                ->leftJoinWith('Users.Addresses')
                ->enableAutoFields(false)
                ->sql()
        );
    }


    public function testContainInnerJoinMerge(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS Posts__id FROM posts AS Posts LEFT JOIN users AS Users ON Users.id = Posts.user_id INNER JOIN addresses AS Addresses ON Addresses.user_id = Users.id',
            ModelRegistry::use('Posts')
                ->find()
                ->leftJoinWith('Users')
                ->innerJoinWith('Users.Addresses')
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testContainLeftJoinInvalid(): void
    {
        $this->expectException(ORMException::class);

        ModelRegistry::use('Posts')
            ->find()
            ->leftJoinWith('Invalid');
    }

    public function testContainInnerJoinInvalid(): void
    {
        $this->expectException(ORMException::class);

        ModelRegistry::use('Posts')
            ->find()
            ->innerJoinWith('Invalid');
    }

}
