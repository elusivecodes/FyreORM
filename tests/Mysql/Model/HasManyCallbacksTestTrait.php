<?php
declare(strict_types=1);

namespace Tests\Mysql\Model;

use function array_map;

trait HasManyCallbacksTestTrait
{
    public function testHasManyAfterDelete(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'failAfterDelete',
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

        $this->assertFalse(
            $Users->delete($user)
        );

        $this->assertSame(
            1,
            $Users->find()->count()
        );

        $this->assertSame(
            2,
            $this->modelRegistry->use('Posts')->find()->count()
        );
    }

    public function testHasManyAfterDeleteMany(): void
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
                'name' => 'failAfterDelete',
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

        $this->assertFalse(
            $Users->deleteMany($users)
        );

        $this->assertSame(
            2,
            $Users->find()->count()
        );

        $this->assertSame(
            4,
            $this->modelRegistry->use('Posts')->find()->count()
        );
    }

    public function testHasManyAfterRules(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'failAfterRules',
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

        $this->assertFalse(
            $Users->save($user)
        );

        $this->assertNull(
            $user->id
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->id,
                $user->posts
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->user_id,
                $user->posts
            )
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

    public function testHasManyAfterRulesMany(): void
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
                'name' => 'failAfterRules',
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

        $this->assertFalse(
            $Users->saveMany($users)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($user) => $user->id,
                $users
            )
        );

        $this->assertSame(
            [
                [null, null],
                [null, null],
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
                [null, null],
                [null, null],
            ],
            array_map(
                fn($user) => array_map(
                    fn($post) => $post->user_id,
                    $user->posts
                ),
                $users
            )
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

    public function testHasManyAfterSave(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'failAfterSave',
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

        $this->assertFalse(
            $Users->save($user)
        );

        $this->assertNull(
            $user->id
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->id,
                $user->posts
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->user_id,
                $user->posts
            )
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

    public function testHasManyAfterSaveMany(): void
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
                'name' => 'failAfterSave',
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

        $this->assertFalse(
            $Users->saveMany($users)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($user) => $user->id,
                $users
            )
        );

        $this->assertSame(
            [
                [null, null],
                [null, null],
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
                [null, null],
                [null, null],
            ],
            array_map(
                fn($user) => array_map(
                    fn($post) => $post->user_id,
                    $user->posts
                ),
                $users
            )
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

    public function testHasManyBeforeDelete(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'failBeforeDelete',
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

        $this->assertFalse(
            $Users->delete($user)
        );

        $this->assertSame(
            1,
            $Users->find()->count()
        );

        $this->assertSame(
            2,
            $this->modelRegistry->use('Posts')->find()->count()
        );
    }

    public function testHasManyBeforeDeleteMany(): void
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
                'name' => 'failBeforeDelete',
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

        $this->assertFalse(
            $Users->deleteMany($users)
        );

        $this->assertSame(
            2,
            $Users->find()->count()
        );

        $this->assertSame(
            4,
            $this->modelRegistry->use('Posts')->find()->count()
        );
    }

    public function testHasManyBeforeRules(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'failBeforeRules',
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

        $this->assertFalse(
            $Users->save($user)
        );

        $this->assertNull(
            $user->id
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->id,
                $user->posts
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->user_id,
                $user->posts
            )
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

    public function testHasManyBeforeRulesMany(): void
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
                'name' => 'failBeforeRules',
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

        $this->assertFalse(
            $Users->saveMany($users)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($user) => $user->id,
                $users
            )
        );

        $this->assertSame(
            [
                [null, null],
                [null, null],
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
                [null, null],
                [null, null],
            ],
            array_map(
                fn($user) => array_map(
                    fn($post) => $post->user_id,
                    $user->posts
                ),
                $users
            )
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

    public function testHasManyBeforeSave(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'failBeforeSave',
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

        $this->assertFalse(
            $Users->save($user)
        );

        $this->assertNull(
            $user->id
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->id,
                $user->posts
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->user_id,
                $user->posts
            )
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

    public function testHasManyBeforeSaveMany(): void
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
                'name' => 'failBeforeSave',
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

        $this->assertFalse(
            $Users->saveMany($users)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($user) => $user->id,
                $users
            )
        );

        $this->assertSame(
            [
                [null, null],
                [null, null],
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
                [null, null],
                [null, null],
            ],
            array_map(
                fn($user) => array_map(
                    fn($post) => $post->user_id,
                    $user->posts
                ),
                $users
            )
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

    public function testHasManyRules(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'failRules',
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

        $this->assertFalse(
            $Users->save($user)
        );

        $this->assertNull(
            $user->id
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->id,
                $user->posts
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->user_id,
                $user->posts
            )
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

    public function testHasManyRulesNoCheckRules(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'failRules',
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
            $Users->save($user, [
                'checkRules' => false,
            ])
        );

        $this->assertSame(
            1,
            $Users->find()->count()
        );

        $this->assertSame(
            2,
            $this->modelRegistry->use('Posts')->find()->count()
        );
    }

    public function testHasManyValidation(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => '',
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

        $this->assertFalse(
            $Users->save($user)
        );

        $this->assertNull(
            $user->id
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->id,
                $user->posts
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->user_id,
                $user->posts
            )
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

    public function testHasManyValidationNoCheckRules(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => '',
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

        $this->assertFalse(
            $Users->save($user, [
                'checkRules' => false,
            ])
        );

        $this->assertNull(
            $user->id
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->id,
                $user->posts
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->user_id,
                $user->posts
            )
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
}
