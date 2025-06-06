<?php
declare(strict_types=1);

namespace Tests\Postgres\Model;

use Fyre\ORM\Exceptions\OrmException;

trait JoinTestTrait
{
    public function testContainInnerJoinConditionsSql(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS "Posts__id" FROM posts AS Posts INNER JOIN users AS Users ON Users.id = Posts.user_id INNER JOIN addresses AS Addresses ON Addresses.user_id = Users.id AND Addresses.suburb = \'Test\'',
            $this->modelRegistry->use('Posts')
                ->find()
                ->innerJoinWith('Users.Addresses', [
                    'Addresses.suburb' => 'Test',
                ])
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testContainInnerJoinInvalid(): void
    {
        $this->expectException(OrmException::class);

        $this->modelRegistry->use('Posts')
            ->find()
            ->innerJoinWith('Invalid');
    }

    public function testContainInnerJoinMerge(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS "Posts__id" FROM posts AS Posts LEFT JOIN users AS Users ON Users.id = Posts.user_id INNER JOIN addresses AS Addresses ON Addresses.user_id = Users.id',
            $this->modelRegistry->use('Posts')
                ->find()
                ->leftJoinWith('Users')
                ->innerJoinWith('Users.Addresses')
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testContainInnerJoinOverwrite(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS "Posts__id" FROM posts AS Posts INNER JOIN users AS Users ON Users.id = Posts.user_id',
            $this->modelRegistry->use('Posts')
                ->find()
                ->leftJoinWith('Users')
                ->innerJoinWith('Users')
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testContainInnerJoinSql(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS "Posts__id" FROM posts AS Posts INNER JOIN users AS Users ON Users.id = Posts.user_id INNER JOIN addresses AS Addresses ON Addresses.user_id = Users.id',
            $this->modelRegistry->use('Posts')
                ->find()
                ->innerJoinWith('Users.Addresses')
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testContainLeftJoinConditionsSql(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS "Posts__id" FROM posts AS Posts LEFT JOIN users AS Users ON Users.id = Posts.user_id LEFT JOIN addresses AS Addresses ON Addresses.user_id = Users.id AND Addresses.suburb = \'Test\'',
            $this->modelRegistry->use('Posts')
                ->find()
                ->leftJoinWith('Users.Addresses', [
                    'Addresses.suburb' => 'Test',
                ])
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testContainLeftJoinInvalid(): void
    {
        $this->expectException(OrmException::class);

        $this->modelRegistry->use('Posts')
            ->find()
            ->leftJoinWith('Invalid');
    }

    public function testContainLeftJoinMerge(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS "Posts__id" FROM posts AS Posts INNER JOIN users AS Users ON Users.id = Posts.user_id LEFT JOIN addresses AS Addresses ON Addresses.user_id = Users.id',
            $this->modelRegistry->use('Posts')
                ->find()
                ->innerJoinWith('Users')
                ->leftJoinWith('Users.Addresses')
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testContainLeftJoinOverwrite(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS "Posts__id" FROM posts AS Posts LEFT JOIN users AS Users ON Users.id = Posts.user_id',
            $this->modelRegistry->use('Posts')
                ->find()
                ->innerJoinWith('Users')
                ->leftJoinWith('Users')
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testContainLeftJoinSql(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS "Posts__id" FROM posts AS Posts LEFT JOIN users AS Users ON Users.id = Posts.user_id LEFT JOIN addresses AS Addresses ON Addresses.user_id = Users.id',
            $this->modelRegistry->use('Posts')
                ->find()
                ->leftJoinWith('Users.Addresses')
                ->disableAutoFields()
                ->sql()
        );
    }
}
