<?php
declare(strict_types=1);

namespace Tests\Postgres\Model;

use Fyre\ORM\Exceptions\OrmException;
use Fyre\ORM\ModelRegistry;

use function array_map;

trait ContainTestTrait
{
    public function testContainAutoFields(): void
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

        $user = $Users->get(1, [
            'contain' => [
                'Addresses',
                'Posts' => [
                    'autoFields' => false,
                    'Tags' => [
                        'autoFields' => false,
                    ],
                ],
            ],
            'autoFields' => false,
        ]);

        $this->assertSame(
            [
                'id' => 1,
                'address' => [
                    'id' => 1,
                ],
                'posts' => [
                    [
                        'id' => 1,
                        'user_id' => 1,
                        'tags' => [
                            [
                                'id' => 1,
                                '_joinData' => [
                                    'id' => 1,
                                    'post_id' => 1,
                                ],
                            ],
                            [
                                'id' => 2,
                                '_joinData' => [
                                    'id' => 2,
                                    'post_id' => 1,
                                ],
                            ],
                        ],
                    ],
                    [
                        'id' => 2,
                        'user_id' => 1,
                        'tags' => [
                            [
                                'id' => 3,
                                '_joinData' => [
                                    'id' => 3,
                                    'post_id' => 2,
                                ],
                            ],
                            [
                                'id' => 4,
                                '_joinData' => [
                                    'id' => 4,
                                    'post_id' => 2,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $user->toArray()
        );
    }

    public function testContainFind(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'Test',
            ],
            'posts' => [
                [
                    'title' => 'Test 1',
                    'content' => 'This is the content.',
                    'comments' => [
                        [
                            'content' => 'This is a comment',
                            'user' => [
                                'name' => 'Test 2',
                            ],
                        ],
                    ],
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
                    'comments' => [
                        [
                            'content' => 'This is a comment',
                            'user' => [
                                'name' => 'Test 3',
                            ],
                        ],
                    ],
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

        $user = $Users->get(1, [
            'contain' => [
                'Addresses',
                'Posts' => [
                    'Comments' => [
                        'Users',
                    ],
                    'Tags',
                ],
            ],
        ]);

        $this->assertSame(
            1,
            $user->id
        );

        $this->assertSame(
            [1, 2],
            array_map(
                fn($post) => $post->id,
                $user->posts
            )
        );

        $this->assertSame(
            1,
            $user->address->id
        );

        $this->assertSame(
            [
                [1, 2],
                [3, 4],
            ],
            array_map(
                fn($user) => array_map(
                    fn($tag) => $tag->id,
                    $user->tags
                ),
                $user->posts
            )
        );

        $this->assertSame(
            [
                [1],
                [2],
            ],
            array_map(
                fn($user) => array_map(
                    fn($comment) => $comment->id,
                    $user->comments
                ),
                $user->posts
            )
        );

        $this->assertSame(
            [
                [2],
                [3],
            ],
            array_map(
                fn($user) => array_map(
                    fn($comment) => $comment->user->id,
                    $user->comments
                ),
                $user->posts
            )
        );
    }

    public function testContainFindOptions(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'posts' => [
                [
                    'title' => 'Test 1',
                    'content' => 'This is the content.',
                ],
                [
                    'title' => 'Test 2',
                    'content' => 'This is the content.',
                ],
            ],
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $user = $Users->get(1, [
            'contain' => [
                'Posts' => [
                    'orderBy' => [
                        'title' => 'DESC',
                    ],
                ],
            ],
        ]);

        $this->assertSame(
            [2, 1],
            array_map(
                fn($post) => $post->id,
                $user->posts
            )
        );
    }

    public function testContainFindSql(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS "Posts__id", Users.id AS "Users__id", Addresses.id AS "Addresses__id" FROM posts AS Posts LEFT JOIN users AS Users ON Users.id = Posts.user_id LEFT JOIN addresses AS Addresses ON Addresses.user_id = Users.id',
            ModelRegistry::use('Posts')
                ->find([
                    'contain' => [
                        'Users' => [
                            'Addresses',
                        ],
                    ],
                ])
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testContainInsert(): void
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

        $this->assertSame(
            1,
            $user->id
        );

        $this->assertSame(
            [1, 2],
            array_map(
                fn($post) => $post->id,
                $user->posts
            )
        );

        $this->assertSame(
            [1, 1],
            array_map(
                fn($post) => $post->user_id,
                $user->posts
            )
        );

        $this->assertSame(
            1,
            $user->address->id
        );

        $this->assertSame(
            [
                [1, 2],
                [3, 4],
            ],
            array_map(
                fn($user) => array_map(
                    fn($tag) => $tag->id,
                    $user->tags
                ),
                $user->posts
            )
        );
    }

    public function testContainInvalid(): void
    {
        $this->expectException(OrmException::class);

        ModelRegistry::use('Users')->find([
            'contain' => [
                'Invalid',
            ],
        ]);
    }

    public function testContainMerge(): void
    {
        $Posts = ModelRegistry::use('Posts');
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $post = $Posts->newEntity([
            'user_id' => $user->id,
            'title' => 'Test',
            'content' => 'This is the content.',
            'comments' => [
                [
                    'user_id' => $user->id,
                    'content' => 'This is a comment',
                ],
            ],
            'tags' => [
                [
                    'tag' => 'test1',
                ],
            ],
        ]);

        $this->assertTrue(
            $Posts->save($post)
        );

        $user = $Users->find([
            'conditions' => [
                'Users.id' => 1,
            ],
        ])
            ->contain([
                'Posts' => [
                    'Comments',
                ],
            ])
            ->contain([
                'Posts' => [
                    'Tags',
                ],
            ])
            ->first();

        $this->assertSame(
            1,
            $user->id
        );

        $this->assertSame(
            1,
            $user->posts[0]->id
        );

        $this->assertSame(
            1,
            $user->posts[0]->comments[0]->id
        );

        $this->assertSame(
            1,
            $user->posts[0]->tags[0]->id
        );
    }

    public function testContainOverwrite(): void
    {
        $Posts = ModelRegistry::use('Posts');
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $post = $Posts->newEntity([
            'user_id' => $user->id,
            'title' => 'Test',
            'content' => 'This is the content.',
            'comments' => [
                [
                    'user_id' => $user->id,
                    'content' => 'This is a comment',
                ],
            ],
            'tags' => [
                [
                    'tag' => 'test1',
                ],
            ],
        ]);

        $this->assertTrue(
            $Posts->save($post)
        );

        $user = $Users->find([
            'conditions' => [
                'Users.id' => 1,
            ],
        ])
            ->contain([
                'Posts' => [
                    'Comments',
                ],
            ])
            ->contain([
                'Posts' => [
                    'Tags',
                ],
            ], true)
            ->first();

        $this->assertSame(
            1,
            $user->id
        );

        $this->assertSame(
            1,
            $user->posts[0]->id
        );

        $this->assertNull(
            $user->posts[0]->comments
        );

        $this->assertSame(
            1,
            $user->posts[0]->tags[0]->id
        );
    }
}
