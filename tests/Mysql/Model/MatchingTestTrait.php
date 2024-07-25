<?php
declare(strict_types=1);

namespace Tests\Mysql\Model;

use Fyre\ORM\Exceptions\OrmException;
use Fyre\ORM\ModelRegistry;
use Tests\Mock\Entity\Address;
use Tests\Mock\Entity\Tag;

trait MatchingTestTrait
{
    public function testMatchingConditionsSql(): void
    {
        $this->assertSame(
            'SELECT Users.id AS Users__id, Tags.id AS Tags__id FROM users AS Users INNER JOIN posts AS Posts ON Posts.user_id = Users.id INNER JOIN posts_tags AS PostsTags ON PostsTags.post_id = Posts.id INNER JOIN tags AS Tags ON Tags.id = PostsTags.tag_id AND Tags.tag = \'test\'',
            ModelRegistry::use('Users')
                ->find()
                ->matching('Posts.Tags', [
                    'Tags.tag' => 'test',
                ])
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testMatchingData(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'posts' => [
                [
                    'title' => 'Test 1',
                    'content' => 'This is the content.',
                    'tags' => [
                        [
                            'tag' => 'test1',
                        ],
                        [
                            'tag' => 'test2',
                        ],
                    ],
                ],
                [
                    'title' => 'Test 2',
                    'content' => 'This is the content.',
                    'tags' => [
                        [
                            'tag' => 'test3',
                        ],
                        [
                            'tag' => 'test4',
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $user = $Users
            ->find()
            ->matching('Posts.Tags', [
                'Tags.tag' => 'test4',
            ])
            ->first();

        $this->assertInstanceOf(
            Tag::class,
            $user->_matchingData['Tags']
        );

        $this->assertSame(
            'test4',
            $user->_matchingData['Tags']->tag
        );
    }

    public function testMatchingDataMultiple(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'posts' => [
                [
                    'title' => 'Test 1',
                    'content' => 'This is the content.',
                    'tags' => [
                        [
                            'tag' => 'test1',
                        ],
                        [
                            'tag' => 'test2',
                        ],
                    ],
                ],
                [
                    'title' => 'Test 2',
                    'content' => 'This is the content.',
                    'tags' => [
                        [
                            'tag' => 'test3',
                        ],
                        [
                            'tag' => 'test4',
                        ],
                    ],
                ],
            ],
            'address' => [
                'suburb' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $user = $Users
            ->find()
            ->matching('Addresses')
            ->matching('Posts.Tags', [
                'Tags.tag' => 'test4',
            ])
            ->first();

        $this->assertInstanceOf(
            Address::class,
            $user->_matchingData['Addresses']
        );

        $this->assertSame(
            'Test',
            $user->_matchingData['Addresses']->suburb
        );

        $this->assertInstanceOf(
            Tag::class,
            $user->_matchingData['Tags']
        );

        $this->assertSame(
            'test4',
            $user->_matchingData['Tags']->tag
        );
    }

    public function testMatchingInvalid(): void
    {
        $this->expectException(OrmException::class);

        ModelRegistry::use('Users')
            ->find()
            ->matching('Invalid');
    }

    public function testMatchingMerge(): void
    {
        $this->assertSame(
            'SELECT Users.id AS Users__id, Addresses.id AS Addresses__id, Tags.id AS Tags__id FROM users AS Users INNER JOIN addresses AS Addresses ON Addresses.user_id = Users.id INNER JOIN posts AS Posts ON Posts.user_id = Users.id INNER JOIN posts_tags AS PostsTags ON PostsTags.post_id = Posts.id INNER JOIN tags AS Tags ON Tags.id = PostsTags.tag_id',
            ModelRegistry::use('Users')
                ->find()
                ->matching('Addresses')
                ->matching('Posts.Tags')
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testMatchingSql(): void
    {
        $this->assertSame(
            'SELECT Users.id AS Users__id, Tags.id AS Tags__id FROM users AS Users INNER JOIN posts AS Posts ON Posts.user_id = Users.id INNER JOIN posts_tags AS PostsTags ON PostsTags.post_id = Posts.id INNER JOIN tags AS Tags ON Tags.id = PostsTags.tag_id',
            ModelRegistry::use('Users')
                ->find()
                ->matching('Posts.Tags')
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testNotMatchingConditionsSql(): void
    {
        $this->assertSame(
            'SELECT Users.id AS Users__id FROM users AS Users LEFT JOIN posts AS Posts ON Posts.user_id = Users.id LEFT JOIN posts_tags AS PostsTags ON PostsTags.post_id = Posts.id LEFT JOIN tags AS Tags ON Tags.id = PostsTags.tag_id AND Tags.tag = \'test\' WHERE Tags.id IS NULL',
            ModelRegistry::use('Users')
                ->find()
                ->notMatching('Posts.Tags', [
                    'Tags.tag' => 'test',
                ])
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testNotMatchingInvalid(): void
    {
        $this->expectException(OrmException::class);

        ModelRegistry::use('Users')
            ->find()
            ->notMatching('Invalid');
    }

    public function testNotMatchingMerge(): void
    {
        $this->assertSame(
            'SELECT Users.id AS Users__id FROM users AS Users LEFT JOIN addresses AS Addresses ON Addresses.user_id = Users.id LEFT JOIN posts AS Posts ON Posts.user_id = Users.id LEFT JOIN posts_tags AS PostsTags ON PostsTags.post_id = Posts.id LEFT JOIN tags AS Tags ON Tags.id = PostsTags.tag_id WHERE Addresses.id IS NULL AND Tags.id IS NULL',
            ModelRegistry::use('Users')
                ->find()
                ->notMatching('Addresses')
                ->notMatching('Posts.Tags')
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testNotMatchingSql(): void
    {
        $this->assertSame(
            'SELECT Users.id AS Users__id FROM users AS Users LEFT JOIN posts AS Posts ON Posts.user_id = Users.id LEFT JOIN posts_tags AS PostsTags ON PostsTags.post_id = Posts.id LEFT JOIN tags AS Tags ON Tags.id = PostsTags.tag_id WHERE Tags.id IS NULL',
            ModelRegistry::use('Users')
                ->find()
                ->notMatching('Posts.Tags')
                ->enableAutoFields(false)
                ->sql()
        );
    }
}
