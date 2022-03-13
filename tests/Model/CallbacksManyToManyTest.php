<?php
declare(strict_types=1);

namespace Tests\Model;

use
    Fyre\ORM\ModelRegistry;

use function
    array_map;

trait CallbacksManyToManyTest
{

    public function testBeforeSaveManyToMany(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => 'failBeforeSave'
                ]
            ]
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
            ModelRegistry::use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('PostsTags')->find()->count()
        );
    }

    public function testAfterSaveManyToMany(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => 'failAfterSave'
                ]
            ]
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
            ModelRegistry::use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('PostsTags')->find()->count()
        );
    }

    public function testBeforeRulesBelongsToManyToMany(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => 'failBeforeRules'
                ]
            ]
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
            ModelRegistry::use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('PostsTags')->find()->count()
        );
    }

    public function testAfterRulesManyToMany(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => 'failAfterRules'
                ]
            ]
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
            ModelRegistry::use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('PostsTags')->find()->count()
        );
    }

    public function testBeforeSaveManyManyToMany(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $posts = $Posts->newEntities([
            [
                'user_id' => 1,
                'title' => 'Test 1',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test1'
                    ],
                    [
                        'tag' => 'test2'
                    ]
                ]
            ],
            [
                'user_id' => 1,
                'title' => 'Test 2',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test3'
                    ],
                    [
                        'tag' => 'failBeforeSave'
                    ]
                ]
            ]
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
                [null, null]
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
            ModelRegistry::use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('PostsTags')->find()->count()
        );
    }

    public function testAfterSaveManyManyToMany(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $posts = $Posts->newEntities([
            [
                'user_id' => 1,
                'title' => 'Test 1',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test1'
                    ],
                    [
                        'tag' => 'test2'
                    ]
                ]
            ],
            [
                'user_id' => 1,
                'title' => 'Test 2',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test3'
                    ],
                    [
                        'tag' => 'failAfterSave'
                    ]
                ]
            ]
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
                [null, null]
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
            ModelRegistry::use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('PostsTags')->find()->count()
        );
    }

    public function testBeforeRulesManyManyToMany(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $posts = $Posts->newEntities([
            [
                'user_id' => 1,
                'title' => 'Test 1',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test1'
                    ],
                    [
                        'tag' => 'test2'
                    ]
                ]
            ],
            [
                'user_id' => 1,
                'title' => 'Test 2',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test3'
                    ],
                    [
                        'tag' => 'failBeforeRules'
                    ]
                ]
            ]
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
                [null, null]
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
            ModelRegistry::use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('PostsTags')->find()->count()
        );
    }

    public function testAfterRulesManyManyToMany(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $posts = $Posts->newEntities([
            [
                'user_id' => 1,
                'title' => 'Test 1',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test1'
                    ],
                    [
                        'tag' => 'test2'
                    ]
                ]
            ],
            [
                'user_id' => 1,
                'title' => 'Test 2',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test3'
                    ],
                    [
                        'tag' => 'failAfterRules'
                    ]
                ]
            ]
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
                [null, null]
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
            ModelRegistry::use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('PostsTags')->find()->count()
        );
    }

    public function testValidationManyToMany(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => ''
                ]
            ]
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
            ModelRegistry::use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('PostsTags')->find()->count()
        );
    }

    public function testValidationNoCheckRulesManyToMany(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => ''
                ]
            ]
        ]);

        $this->assertFalse(
            $Posts->save($post, [
                'checkRules' => false
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
            ModelRegistry::use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('PostsTags')->find()->count()
        );
    }

    public function testRulesManyToMany(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => 'failRules'
                ]
            ]
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
            ModelRegistry::use('Tags')->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('PostsTags')->find()->count()
        );
    }

    public function testRulesNoCheckRulesManyToMany(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => 'failRules'
                ]
            ]
        ]);

        $this->assertTrue(
            $Posts->save($post, [
                'checkRules' => false
            ])
        );

        $this->assertSame(
            1,
            $Posts->find()->count()
        );

        $this->assertSame(
            2,
            ModelRegistry::use('Tags')->find()->count()
        );

        $this->assertSame(
            2,
            ModelRegistry::use('PostsTags')->find()->count()
        );
    }

}
