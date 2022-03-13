<?php
declare(strict_types=1);

namespace Tests\Model;

use
    Fyre\ORM\ModelRegistry,
    Tests\Mock\Entity\Post,
    Tests\Mock\Entity\User;

use function
    array_map;

trait HasManyTest
{

    public function testHasManyInsert(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'posts' => [
                [
                    'title' => 'Test 1',
                    'content' => 'This is the content.'
                ],
                [
                    'title' => 'Test 2',
                    'content' => 'This is the content.'
                ]
            ]
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

        $this->assertFalse(
            $user->isNew()
        );

        $this->assertFalse(
            $user->posts[0]->isNew()
        );

        $this->assertFalse(
            $user->posts[1]->isNew()
        );

        $this->assertFalse(
            $user->isDirty()
        );

        $this->assertFalse(
            $user->posts[0]->isDirty()
        );

        $this->assertFalse(
            $user->posts[1]->isDirty()
        );
    }

    public function testHasManyInsertMany(): void
    {
        $Users = ModelRegistry::use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'posts' => [
                    [
                        'title' => 'Test 1',
                        'content' => 'This is the content.'
                    ],
                    [
                        'title' => 'Test 2',
                        'content' => 'This is the content.'
                    ]
                ]
            ],
            [
                'name' => 'Test 2',
                'posts' => [
                    [
                        'title' => 'Test 3',
                        'content' => 'This is the content.'
                    ],
                    [
                        'title' => 'Test 4',
                        'content' => 'This is the content.'
                    ]
                ]
            ]
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertSame(
            [1, 2],
            array_map(
                fn($user) => $user->id,
                $users
            )
        );

        $this->assertSame(
            [
                [1, 2],
                [3, 4]
            ],
            array_map(
                fn($user) => array_map(
                    fn($post) => $post->id,
                    $user->posts
                ),
                $users
            )
        );

        $this->assertSame(
            [
                [1, 1],
                [2, 2]
            ],
            array_map(
                fn($user) => array_map(
                    fn($post) => $post->user_id,
                    $user->posts
                ),
                $users
            )
        );

        $this->assertFalse(
            $users[0]->isNew()
        );

        $this->assertFalse(
            $users[1]->isNew()
        );

        $this->assertFalse(
            $users[0]->posts[0]->isNew()
        );

        $this->assertFalse(
            $users[0]->posts[1]->isNew()
        );

        $this->assertFalse(
            $users[1]->posts[0]->isNew()
        );

        $this->assertFalse(
            $users[1]->posts[1]->isNew()
        );

        $this->assertFalse(
            $users[0]->isDirty()
        );

        $this->assertFalse(
            $users[1]->isDirty()
        );

        $this->assertFalse(
            $users[0]->posts[0]->isDirty()
        );

        $this->assertFalse(
            $users[0]->posts[1]->isDirty()
        );

        $this->assertFalse(
            $users[1]->posts[0]->isDirty()
        );

        $this->assertFalse(
            $users[1]->posts[1]->isDirty()
        );
    }

    public function testHasManyUpdate(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'posts' => [
                [
                    'title' => 'Test 1',
                    'content' => 'This is the content.'
                ],
                [
                    'title' => 'Test 2',
                    'content' => 'This is the content.'
                ]
            ]
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $Users->patchEntity($user, [
            'name' => 'Test 2',
            'posts' => [
                [
                    'title' => 'Test 3'
                ],
                [
                    'title' => 'Test 4'
                ]
            ]
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $this->assertFalse(
            $user->isDirty()
        );

        $this->assertFalse(
            $user->posts[0]->isDirty()
        );

        $this->assertFalse(
            $user->posts[1]->isDirty()
        );

        $user = $Users->get(1, [
            'contain' => [
                'Posts'
            ]
        ]);

        $this->assertSame(
            'Test 2',
            $user->name
        );

        $this->assertSame(
            ['Test 3', 'Test 4'],
            array_map(
                fn($post) => $post->title,
                $user->posts
            )
        );
    }

    public function testHasManyUpdateMany(): void
    {
        $Users = ModelRegistry::use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'posts' => [
                    [
                        'title' => 'Test 1',
                        'content' => 'This is the content.'
                    ],
                    [
                        'title' => 'Test 2',
                        'content' => 'This is the content.'
                    ]
                ]
            ],
            [
                'name' => 'Test 2',
                'posts' => [
                    [
                        'title' => 'Test 3',
                        'content' => 'This is the content.'
                    ],
                    [
                        'title' => 'Test 4',
                        'content' => 'This is the content.'
                    ]
                ]
            ]
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $Users->patchEntities($users, [
            [
                'name' => 'Test 3',
                'posts' => [
                    [
                        'title' => 'Test 5'
                    ],
                    [
                        'title' => 'Test 6'
                    ]
                ]
            ],
            [
                'name' => 'Test 4',
                'posts' => [
                    [
                        'title' => 'Test 7'
                    ],
                    [
                        'title' => 'Test 8'
                    ]
                ]
            ]
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertFalse(
            $users[0]->isDirty()
        );

        $this->assertFalse(
            $users[1]->isDirty()
        );

        $this->assertFalse(
            $users[0]->posts[0]->isDirty()
        );

        $this->assertFalse(
            $users[0]->posts[1]->isDirty()
        );

        $this->assertFalse(
            $users[1]->posts[0]->isDirty()
        );

        $this->assertFalse(
            $users[1]->posts[1]->isDirty()
        );

        $users = $Users->find([
            'contain' => [
                'Posts'
            ]
        ])->all();

        $this->assertSame(
            ['Test 3', 'Test 4'],
            array_map(
                fn($user) => $user->name,
                $users
            )
        );

        $this->assertSame(
            [
                ['Test 5', 'Test 6'],
                ['Test 7', 'Test 8']
            ],
            array_map(
                fn($user) => array_map(
                    fn($post) => $post->title,
                    $user->posts
                ),
                $users
            )
        );
    }

    public function testHasManyDelete(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'posts' => [
                [
                    'title' => 'Test 1',
                    'content' => 'This is the content.'
                ],
                [
                    'title' => 'Test 2',
                    'content' => 'This is the content.'
                ]
            ]
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $this->assertTrue(
            $Users->delete($user)
        );

        $this->assertSame(
            0,
            $Users->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('Posts')->find()->count()
        );
    }

    public function testHasManyDeleteMany(): void
    {
        $Users = ModelRegistry::use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'posts' => [
                    [
                        'title' => 'Test 1',
                        'content' => 'This is the content.'
                    ],
                    [
                        'title' => 'Test 2',
                        'content' => 'This is the content.'
                    ]
                ]
            ],
            [
                'name' => 'Test 2',
                'posts' => [
                    [
                        'title' => 'Test 3',
                        'content' => 'This is the content.'
                    ],
                    [
                        'title' => 'Test 4',
                        'content' => 'This is the content.'
                    ]
                ]
            ]
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertTrue(
            $Users->deleteMany($users)
        );

        $this->assertSame(
            0,
            $Users->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('Posts')->find()->count()
        );
    }

    public function testHasManyDeleteUnlink(): void
    {
        $Users = ModelRegistry::use('Users');

        $Users->removeRelationship('Posts');
        $Users->hasMany('Posts');

        $user = $Users->newEntity([
            'name' => 'Test',
            'posts' => [
                [
                    'title' => 'Test 1',
                    'content' => 'This is the content.'
                ],
                [
                    'title' => 'Test 2',
                    'content' => 'This is the content.'
                ]
            ]
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $this->assertTrue(
            $Users->delete($user)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->user_id,
                ModelRegistry::use('Posts')
                    ->find()
                    ->all()
            )
        );
    }

    public function testHasManyDeleteManyUnlink(): void
    {
        $Users = ModelRegistry::use('Users');

        $Users->removeRelationship('Posts');
        $Users->hasMany('Posts');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'posts' => [
                    [
                        'title' => 'Test 1',
                        'content' => 'This is the content.'
                    ],
                    [
                        'title' => 'Test 2',
                        'content' => 'This is the content.'
                    ]
                ]
            ],
            [
                'name' => 'Test 2',
                'posts' => [
                    [
                        'title' => 'Test 3',
                        'content' => 'This is the content.'
                    ],
                    [
                        'title' => 'Test 4',
                        'content' => 'This is the content.'
                    ]
                ]
            ]
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertTrue(
            $Users->deleteMany($users)
        );

        $this->assertSame(
            [null, null, null, null],
            array_map(
                fn($post) => $post->user_id,
                ModelRegistry::use('Posts')
                    ->find()
                    ->all()
            )
        );
    }

    public function testHasManyReplace(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'posts' => [
                [
                    'title' => 'Test 1',
                    'content' => 'This is the content.'
                ],
                [
                    'title' => 'Test 2',
                    'content' => 'This is the content.'
                ]
            ]
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $user->posts = [];

        $this->assertTrue(
            $Users->save($user)
        );

        $this->assertSame(
            0,
            ModelRegistry::use('Posts')->find()->count()
        );
    }

    public function testHasManyReplaceMany(): void
    {
        $Users = ModelRegistry::use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'posts' => [
                    [
                        'title' => 'Test 1',
                        'content' => 'This is the content.'
                    ],
                    [
                        'title' => 'Test 2',
                        'content' => 'This is the content.'
                    ]
                ]
            ],
            [
                'name' => 'Test 2',
                'posts' => [
                    [
                        'title' => 'Test 3',
                        'content' => 'This is the content.'
                    ],
                    [
                        'title' => 'Test 4',
                        'content' => 'This is the content.'
                    ]
                ]
            ]
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $Users->patchEntities($users, [
            [
                'posts' => []
            ],
            [
                'posts' => []
            ]
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertSame(
            0,
            ModelRegistry::use('Posts')->find()->count()
        );
    }

    public function testHasManyReplaceUnlink(): void
    {
        $Users = ModelRegistry::use('Users');

        $Users->removeRelationship('Posts');
        $Users->hasMany('Posts');

        $user = $Users->newEntity([
            'name' => 'Test',
            'posts' => [
                [
                    'title' => 'Test 1',
                    'content' => 'This is the content.'
                ],
                [
                    'title' => 'Test 2',
                    'content' => 'This is the content.'
                ]
            ]
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $user->posts = [];

        $this->assertTrue(
            $Users->save($user)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->user_id,
                ModelRegistry::use('Posts')
                    ->find()
                    ->all()
            )
        );
    }

    public function testHasManyReplaceManyUnlink(): void
    {
        $Users = ModelRegistry::use('Users');

        $Users->removeRelationship('Posts');
        $Users->hasMany('Posts');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'posts' => [
                    [
                        'title' => 'Test 1',
                        'content' => 'This is the content.'
                    ],
                    [
                        'title' => 'Test 2',
                        'content' => 'This is the content.'
                    ]
                ]
            ],
            [
                'name' => 'Test 2',
                'posts' => [
                    [
                        'title' => 'Test 3',
                        'content' => 'This is the content.'
                    ],
                    [
                        'title' => 'Test 4',
                        'content' => 'This is the content.'
                    ]
                ]
            ]
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $Users->patchEntities($users, [
            [
                'posts' => []
            ],
            [
                'posts' => []
            ]
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertSame(
            [null, null, null, null],
            array_map(
                fn($post) => $post->user_id,
                ModelRegistry::use('Posts')
                    ->find()
                    ->all()
            )
        );
    }

    public function testHasManyFind(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'posts' => [
                [
                    'title' => 'Test 1',
                    'content' => 'This is the content.'
                ],
                [
                    'title' => 'Test 2',
                    'content' => 'This is the content.'
                ]
            ]
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $user = $Users->get(1, [
            'contain' => [
                'Posts'
            ]
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

        $this->assertInstanceOf(
            User::class,
            $user
        );

        $this->assertInstanceOf(
            Post::class,
            $user->posts[0]
        );

        $this->assertInstanceOf(
            Post::class,
            $user->posts[1]
        );

        $this->assertFalse(
            $user->isNew()
        );

        $this->assertFalse(
            $user->posts[0]->isNew()
        );

        $this->assertFalse(
            $user->posts[1]->isNew()
        );
    }

    public function testHasManyLeftJoinSql(): void
    {
        $this->assertSame(
            'SELECT Users.id AS Users__id FROM users AS Users LEFT JOIN posts AS Posts ON Posts.user_id = Users.id',
            ModelRegistry::use('Users')
                ->find()
                ->leftJoinWith('Posts')
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testHasManyInnerJoinSql(): void
    {
        $this->assertSame(
            'SELECT Users.id AS Users__id FROM users AS Users INNER JOIN posts AS Posts ON Posts.user_id = Users.id',
            ModelRegistry::use('Users')
                ->find()
                ->innerJoinWith('Posts')
                ->enableAutoFields(false)
                ->sql()
        );
    }

}
