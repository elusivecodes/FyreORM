<?php
declare(strict_types=1);

namespace Tests\Postgres\Model;

use function array_map;

trait CallbacksManyToManyTestTrait
{
    public function testAfterParseManyToMany(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1',
                ],
                [
                    'tag' => 'afterParse',
                ],
            ],
        ]);

        $this->assertSame(
            1,
            $post->tags[1]->test
        );
    }

    public function testAfterParseManyToManyMany(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $posts = $Posts->newEntities([
            [
                'user_id' => 1,
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
                'user_id' => 1,
                'title' => 'Test 2',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test3',
                    ],
                    [
                        'tag' => 'afterParse',
                    ],
                ],
            ],
        ]);

        $this->assertSame(
            1,
            $posts[1]->tags[1]->test
        );
    }

    public function testAfterRulesManyManyToMany(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $posts = $Posts->newEntities([
            [
                'user_id' => 1,
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
                'user_id' => 1,
                'title' => 'Test 2',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test3',
                    ],
                    [
                        'tag' => 'failAfterRules',
                    ],
                ],
            ],
        ]);

        $this->assertFalse(
            $Posts->saveMany($posts)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->id,
                $posts
            )
        );

        $this->assertSame(
            [
                [null, null],
                [null, null],
            ],
            array_map(
                fn($post) => array_map(
                    fn($tag) => $tag->id,
                    $post->tags
                ),
                $posts
            )
        );

        $this->assertSame(
            0,
            $Posts->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('PostsTags')->find()->count()
        );
    }

    public function testAfterRulesManyToMany(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1',
                ],
                [
                    'tag' => 'failAfterRules',
                ],
            ],
        ]);

        $this->assertFalse(
            $Posts->save($post)
        );

        $this->assertNull(
            $post->id
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($tag) => $tag->id,
                $post->tags
            )
        );

        $this->assertSame(
            0,
            $Posts->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('PostsTags')->find()->count()
        );
    }

    public function testAfterSaveManyManyToMany(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $posts = $Posts->newEntities([
            [
                'user_id' => 1,
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
                'user_id' => 1,
                'title' => 'Test 2',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test3',
                    ],
                    [
                        'tag' => 'failAfterSave',
                    ],
                ],
            ],
        ]);

        $this->assertFalse(
            $Posts->saveMany($posts)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->id,
                $posts
            )
        );

        $this->assertSame(
            [
                [null, null],
                [null, null],
            ],
            array_map(
                fn($post) => array_map(
                    fn($tag) => $tag->id,
                    $post->tags
                ),
                $posts
            )
        );

        $this->assertSame(
            0,
            $Posts->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('PostsTags')->find()->count()
        );
    }

    public function testAfterSaveManyToMany(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1',
                ],
                [
                    'tag' => 'failAfterSave',
                ],
            ],
        ]);

        $this->assertFalse(
            $Posts->save($post)
        );

        $this->assertNull(
            $post->id
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($tag) => $tag->id,
                $post->tags
            )
        );

        $this->assertSame(
            0,
            $Posts->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('PostsTags')->find()->count()
        );
    }

    public function testBeforeParseManyToMany(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => '  test1  ',
                ],
                [
                    'tag' => '  test2  ',
                ],
            ],
        ]);

        $this->assertSame(
            'test1',
            $post->tags[0]->tag
        );

        $this->assertSame(
            'test2',
            $post->tags[1]->tag
        );
    }

    public function testBeforeParseManyToManyMany(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $posts = $Posts->newEntities([
            [
                'user_id' => 1,
                'title' => 'Test 1',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => '  test1  ',
                    ],
                    [
                        'tag' => '  test2  ',
                    ],
                ],
            ],
            [
                'user_id' => 1,
                'title' => 'Test 2',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => '  test3  ',
                    ],
                    [
                        'tag' => '  test4  ',
                    ],
                ],
            ],
        ]);

        $this->assertSame(
            'test1',
            $posts[0]->tags[0]->tag
        );

        $this->assertSame(
            'test2',
            $posts[0]->tags[1]->tag
        );

        $this->assertSame(
            'test3',
            $posts[1]->tags[0]->tag
        );

        $this->assertSame(
            'test4',
            $posts[1]->tags[1]->tag
        );
    }

    public function testBeforeRulesManyManyToMany(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $posts = $Posts->newEntities([
            [
                'user_id' => 1,
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
                'user_id' => 1,
                'title' => 'Test 2',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test3',
                    ],
                    [
                        'tag' => 'failBeforeRules',
                    ],
                ],
            ],
        ]);

        $this->assertFalse(
            $Posts->saveMany($posts)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->id,
                $posts
            )
        );

        $this->assertSame(
            [
                [null, null],
                [null, null],
            ],
            array_map(
                fn($post) => array_map(
                    fn($tag) => $tag->id,
                    $post->tags
                ),
                $posts
            )
        );

        $this->assertSame(
            0,
            $Posts->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('PostsTags')->find()->count()
        );
    }

    public function testBeforeRulesManyToMany(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1',
                ],
                [
                    'tag' => 'failBeforeRules',
                ],
            ],
        ]);

        $this->assertFalse(
            $Posts->save($post)
        );

        $this->assertNull(
            $post->id
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($tag) => $tag->id,
                $post->tags
            )
        );

        $this->assertSame(
            0,
            $Posts->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('PostsTags')->find()->count()
        );
    }

    public function testBeforeSaveManyManyToMany(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $posts = $Posts->newEntities([
            [
                'user_id' => 1,
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
                'user_id' => 1,
                'title' => 'Test 2',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test3',
                    ],
                    [
                        'tag' => 'failBeforeSave',
                    ],
                ],
            ],
        ]);

        $this->assertFalse(
            $Posts->saveMany($posts)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->id,
                $posts
            )
        );

        $this->assertSame(
            [
                [null, null],
                [null, null],
            ],
            array_map(
                fn($post) => array_map(
                    fn($tag) => $tag->id,
                    $post->tags
                ),
                $posts
            )
        );

        $this->assertSame(
            0,
            $Posts->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('PostsTags')->find()->count()
        );
    }

    public function testBeforeSaveManyToMany(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1',
                ],
                [
                    'tag' => 'failBeforeSave',
                ],
            ],
        ]);

        $this->assertFalse(
            $Posts->save($post)
        );

        $this->assertNull(
            $post->id
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($tag) => $tag->id,
                $post->tags
            )
        );

        $this->assertSame(
            0,
            $Posts->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('PostsTags')->find()->count()
        );
    }

    public function testRulesManyToMany(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1',
                ],
                [
                    'tag' => 'failRules',
                ],
            ],
        ]);

        $this->assertFalse(
            $Posts->save($post)
        );

        $this->assertNull(
            $post->id
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($tag) => $tag->id,
                $post->tags
            )
        );

        $this->assertSame(
            0,
            $Posts->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('PostsTags')->find()->count()
        );
    }

    public function testRulesNoCheckRulesManyToMany(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1',
                ],
                [
                    'tag' => 'failRules',
                ],
            ],
        ]);

        $this->assertTrue(
            $Posts->save($post, [
                'checkRules' => false,
            ])
        );

        $this->assertSame(
            1,
            $Posts->find()->count()
        );

        $this->assertSame(
            2,
            $this->modelRegistry->use('Tags')->find()->count()
        );

        $this->assertSame(
            2,
            $this->modelRegistry->use('PostsTags')->find()->count()
        );
    }

    public function testValidationManyToMany(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1',
                ],
                [
                    'tag' => '',
                ],
            ],
        ]);

        $this->assertFalse(
            $Posts->save($post)
        );

        $this->assertNull(
            $post->id
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($tag) => $tag->id,
                $post->tags
            )
        );

        $this->assertSame(
            0,
            $Posts->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('PostsTags')->find()->count()
        );
    }

    public function testValidationNoCheckRulesManyToMany(): void
    {
        $Posts = $this->modelRegistry->use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1',
                ],
                [
                    'tag' => '',
                ],
            ],
        ]);

        $this->assertFalse(
            $Posts->save($post, [
                'checkRules' => false,
            ])
        );

        $this->assertNull(
            $post->id
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($post) => $post->id,
                $post->tags
            )
        );

        $this->assertSame(
            0,
            $Posts->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('PostsTags')->find()->count()
        );
    }
}
