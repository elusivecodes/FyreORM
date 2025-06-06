<?php
declare(strict_types=1);

namespace Tests\Sqlite\Model;

use function array_map;

trait CallbacksHasManyTestTrait
{
    public function testAfterParseHasMany(): void
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
                    'title' => 'afterParse',
                    'content' => 'This is the content.',
                ],
            ],
        ]);

        $this->assertSame(
            1,
            $user->posts[1]->test
        );
    }

    public function testAfterParseHasManyMany(): void
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
                        'title' => 'Test   ',
                        'content' => 'This is the content.',
                    ],
                ],
            ],
            [
                'name' => 'Test 2',
                'posts' => [
                    [
                        'title' => 'Test   ',
                        'content' => 'This is the content.',
                    ],
                    [
                        'title' => 'afterParse',
                        'content' => 'This is the content.',
                    ],
                ],
            ],
        ]);

        $this->assertSame(
            1,
            $users[1]->posts[1]->test
        );
    }

    public function testAfterRulesHasMany(): void
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
                    'title' => 'failAfterRules',
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

    public function testAfterRulesManyHasMany(): void
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
                        'title' => 'failAfterRules',
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

    public function testAfterSaveHasMany(): void
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
                    'title' => 'failAfterSave',
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

    public function testAfterSaveManyHasMany(): void
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
                        'title' => 'failAfterSave',
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

    public function testBeforeParseHasMany(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'posts' => [
                [
                    'title' => '  Test 1  ',
                    'content' => 'This is the content.',
                ],
                [
                    'title' => '  Test 2  ',
                    'content' => 'This is the content.',
                ],
            ],
        ]);

        $this->assertSame(
            'Test 1',
            $user->posts[0]->title
        );

        $this->assertSame(
            'Test 2',
            $user->posts[1]->title
        );
    }

    public function testBeforeParseHasManyMany(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'posts' => [
                    [
                        'title' => '  Test 1  ',
                        'content' => 'This is the content.',
                    ],
                    [
                        'title' => '  Test 2  ',
                        'content' => 'This is the content.',
                    ],
                ],
            ],
            [
                'name' => 'Test 2',
                'posts' => [
                    [
                        'title' => '  Test 3  ',
                        'content' => 'This is the content.',
                    ],
                    [
                        'title' => '  Test 4  ',
                        'content' => 'This is the content.',
                    ],
                ],
            ],
        ]);

        $this->assertSame(
            'Test 1',
            $users[0]->posts[0]->title
        );

        $this->assertSame(
            'Test 2',
            $users[0]->posts[1]->title
        );

        $this->assertSame(
            'Test 3',
            $users[1]->posts[0]->title
        );

        $this->assertSame(
            'Test 4',
            $users[1]->posts[1]->title
        );
    }

    public function testBeforeRulesHasMany(): void
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
                    'title' => 'failBeforeRules',
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

    public function testBeforeRulesManyHasMany(): void
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
                        'title' => 'failBeforeRules',
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

    public function testBeforeSaveHasMany(): void
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
                    'title' => 'failBeforeSave',
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

    public function testBeforeSaveManyHasMany(): void
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
                        'title' => 'failBeforeSave',
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

    public function testRulesHasMany(): void
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
                    'title' => 'failRules',
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

    public function testRulesNoCheckRulesHasMany(): void
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
                    'title' => 'failRules',
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

    public function testValidationHasMany(): void
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
                    'title' => '',
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

    public function testValidationNoCheckRulesHasMany(): void
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
                    'title' => '',
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
