<?php
declare(strict_types=1);

namespace Tests\Postgres\Model;

use Fyre\ORM\Queries\SelectQuery;
use Tests\Mock\Entity\Post;
use Tests\Mock\Entity\User;

use function array_map;

trait HasManyTestTrait
{
    public function testHasManyAppend(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $Users->Posts->setSaveStrategy('append');

        $user = $Users->newEntity([
            'name' => 'Test',
            'posts' => [
                [
                    'title' => 'Test 1',
                    'content' => 'This is the content.',
                ],
            ],
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $user->posts = null;

        $Users->patchEntity($user, [
            'posts' => [
                [
                    'title' => 'Test 2',
                    'content' => 'This is the content.',
                ],
            ],
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $this->assertSame(
            [1, 1],
            array_map(
                fn($post) => $post->user_id,
                $this->modelRegistry->use('Posts')
                    ->find()
                    ->toArray()
            )
        );
    }

    public function testHasManyAppendMany(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $Users->Posts->setSaveStrategy('append');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'posts' => [
                    [
                        'title' => 'Test 1',
                        'content' => 'This is the content.',
                    ],
                ],
            ],
            [
                'name' => 'Test 2',
                'posts' => [
                    [
                        'title' => 'Test 3',
                        'content' => 'This is the content.',
                    ],
                ],
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $users[0]->posts = null;
        $users[1]->posts = null;

        $Users->patchEntities($users, [
            [
                'posts' => [

                    [
                        'title' => 'Test 2',
                        'content' => 'This is the content.',
                    ],
                ],
            ],
            [
                'posts' => [
                    [
                        'title' => 'Test 4',
                        'content' => 'This is the content.',
                    ],
                ],
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertSame(
            [1, 2, 1, 2],
            array_map(
                fn($post) => $post->user_id,
                $this->modelRegistry->use('Posts')
                    ->find()
                    ->toArray()
            )
        );
    }

    public function testHasManyDelete(): void
    {
        $Users = $this->modelRegistry->use('Users');

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

        $this->assertTrue(
            $Users->delete($user)
        );

        $this->assertSame(
            0,
            $Users->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Posts')->find()->count()
        );
    }

    public function testHasManyDeleteMany(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
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
            ],
            [
                'name' => 'Test 2',
                'posts' => [
                    [
                        'title' => 'Test 3',
                        'content' => 'This is the content.',
                    ],
                    [
                        'title' => 'Test 4',
                        'content' => 'This is the content.',
                    ],
                ],
            ],
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
            $this->modelRegistry->use('Posts')->find()->count()
        );
    }

    public function testHasManyDeleteManyUnlink(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $Users->Posts->setDependent(false);

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
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
            ],
            [
                'name' => 'Test 2',
                'posts' => [
                    [
                        'title' => 'Test 3',
                        'content' => 'This is the content.',
                    ],
                    [
                        'title' => 'Test 4',
                        'content' => 'This is the content.',
                    ],
                ],
            ],
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
                $this->modelRegistry->use('Posts')
                    ->find()
                    ->toArray()
            )
        );
    }

    public function testHasManyDeleteUnlink(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $Users->Posts->setDependent(false);

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

        $this->assertTrue(
            $Users->delete($user)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->user_id,
                $this->modelRegistry->use('Posts')
                    ->find()
                    ->toArray()
            )
        );
    }

    public function testHasManyFind(): void
    {
        $Users = $this->modelRegistry->use('Users');

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
                'Posts',
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

    public function testHasManyFindCallback(): void
    {
        $Users = $this->modelRegistry->use('Users');

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
                    'callback' => fn(SelectQuery $query): SelectQuery => $query->where(['Posts.id' => 2]),
                ],
            ],
        ]);

        $this->assertSame(
            1,
            $user->id
        );

        $this->assertSame(
            [2],
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

        $this->assertFalse(
            $user->isNew()
        );

        $this->assertFalse(
            $user->posts[0]->isNew()
        );
    }

    public function testHasManyFindRelated(): void
    {
        $Users = $this->modelRegistry->use('Users');

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

        $user = $Users->get(1);

        $posts = $Users->Posts->findRelated([$user])->toArray();

        $this->assertSame(
            [1, 2],
            array_map(
                fn($post) => $post->id,
                $posts
            )
        );

        $this->assertInstanceOf(
            Post::class,
            $posts[0]
        );

        $this->assertInstanceOf(
            Post::class,
            $posts[1]
        );
    }

    public function testHasManyInnerJoinSql(): void
    {
        $this->assertSame(
            'SELECT Users.id AS "Users__id" FROM users AS Users INNER JOIN posts AS Posts ON Posts.user_id = Users.id',
            $this->modelRegistry->use('Users')
                ->find()
                ->innerJoinWith('Posts')
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testHasManyInsert(): void
    {
        $Users = $this->modelRegistry->use('Users');

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
        $Users = $this->modelRegistry->use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
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
            ],
            [
                'name' => 'Test 2',
                'posts' => [
                    [
                        'title' => 'Test 3',
                        'content' => 'This is the content.',
                    ],
                    [
                        'title' => 'Test 4',
                        'content' => 'This is the content.',
                    ],
                ],
            ],
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
                [3, 4],
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
                [2, 2],
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

    public function testHasManyLeftJoinSql(): void
    {
        $this->assertSame(
            'SELECT Users.id AS "Users__id" FROM users AS Users LEFT JOIN posts AS Posts ON Posts.user_id = Users.id',
            $this->modelRegistry->use('Users')
                ->find()
                ->leftJoinWith('Posts')
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testHasManyReplace(): void
    {
        $Users = $this->modelRegistry->use('Users');

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

        $user->posts = [];

        $this->assertTrue(
            $Users->save($user)
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Posts')->find()->count()
        );
    }

    public function testHasManyReplaceMany(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
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
            ],
            [
                'name' => 'Test 2',
                'posts' => [
                    [
                        'title' => 'Test 3',
                        'content' => 'This is the content.',
                    ],
                    [
                        'title' => 'Test 4',
                        'content' => 'This is the content.',
                    ],
                ],
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $Users->patchEntities($users, [
            [
                'posts' => [],
            ],
            [
                'posts' => [],
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Posts')->find()->count()
        );
    }

    public function testHasManyReplaceManyUnlink(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $Users->Posts->setDependent(false);

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
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
            ],
            [
                'name' => 'Test 2',
                'posts' => [
                    [
                        'title' => 'Test 3',
                        'content' => 'This is the content.',
                    ],
                    [
                        'title' => 'Test 4',
                        'content' => 'This is the content.',
                    ],
                ],
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $Users->patchEntities($users, [
            [
                'posts' => [],
            ],
            [
                'posts' => [],
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertSame(
            [null, null, null, null],
            array_map(
                fn($post) => $post->user_id,
                $this->modelRegistry->use('Posts')
                    ->find()
                    ->toArray()
            )
        );
    }

    public function testHasManyReplaceUnlink(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $Users->Posts->setDependent(false);

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

        $user->posts = [];

        $this->assertTrue(
            $Users->save($user)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->user_id,
                $this->modelRegistry->use('Posts')
                    ->find()
                    ->toArray()
            )
        );
    }

    public function testHasManySort(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $Users->Posts->setSort([
            'Posts.id' => 'DESC',
        ]);

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
                'Posts',
            ],
        ]);

        $this->assertSame(
            1,
            $user->id
        );

        $this->assertSame(
            [2, 1],
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

    public function testHasManyStrategyFind(): void
    {
        $Users = $this->modelRegistry->use('Users');

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
                    'strategy' => 'subquery',
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

    public function testHasManyUpdate(): void
    {
        $Users = $this->modelRegistry->use('Users');

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

        $Users->patchEntity($user, [
            'name' => 'Test 2',
            'posts' => [
                [
                    'title' => 'Test 3',
                ],
                [
                    'title' => 'Test 4',
                ],
            ],
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
                'Posts',
            ],
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
        $Users = $this->modelRegistry->use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
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
            ],
            [
                'name' => 'Test 2',
                'posts' => [
                    [
                        'title' => 'Test 3',
                        'content' => 'This is the content.',
                    ],
                    [
                        'title' => 'Test 4',
                        'content' => 'This is the content.',
                    ],
                ],
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $Users->patchEntities($users, [
            [
                'name' => 'Test 3',
                'posts' => [
                    [
                        'title' => 'Test 5',
                    ],
                    [
                        'title' => 'Test 6',
                    ],
                ],
            ],
            [
                'name' => 'Test 4',
                'posts' => [
                    [
                        'title' => 'Test 7',
                    ],
                    [
                        'title' => 'Test 8',
                    ],
                ],
            ],
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
                'Posts',
            ],
        ])->toArray();

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
                ['Test 7', 'Test 8'],
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
}
