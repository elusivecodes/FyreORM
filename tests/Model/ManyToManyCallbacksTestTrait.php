<?php
declare(strict_types=1);

namespace Tests\Model;

use Fyre\ORM\ModelRegistry;

use function array_map;

trait ManyToManyCallbacksTestTrait
{

    public function testManyToManyBeforeSave(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'failBeforeSave',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => 'test2'
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

    public function testManyToManyAfterSave(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'failAfterSave',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => 'test2'
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

    public function testManyToManyBeforeRules(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'failBeforeRules',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => 'test2'
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

    public function testManyToManyAfterRules(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'failAfterRules',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => 'test2'
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

    public function testManyToManyBeforeDelete(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'failBeforeDelete',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => 'test2'
                ]
            ]
        ]);

        $this->assertTrue(
            $Posts->save($post)
        );

        $this->assertFalse(
            $Posts->delete($post)
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

    public function testManyToManyAfterDelete(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'failAfterDelete',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => 'test2'
                ]
            ]
        ]);

        $this->assertTrue(
            $Posts->save($post)
        );

        $this->assertFalse(
            $Posts->delete($post)
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

    public function testManyToManyBeforeSaveMany(): void
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
                'title' => 'failBeforeSave',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test3'
                    ],
                    [
                        'tag' => 'test4'
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

    public function testManyToManyAfterSaveMany(): void
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
                'title' => 'failAfterSave',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test3'
                    ],
                    [
                        'tag' => 'test4'
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

    public function testManyToManyBeforeRulesMany(): void
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
                'title' => 'failBeforeRules',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test3'
                    ],
                    [
                        'tag' => 'test4'
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

    public function testManyToManyAfterRulesMany(): void
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
                'title' => 'failAfterRules',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test3'
                    ],
                    [
                        'tag' => 'test4'
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

    public function testManyToManyBeforeDeleteMany(): void
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
                'title' => 'failBeforeDelete',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test3'
                    ],
                    [
                        'tag' => 'test4'
                    ]
                ]
            ]
        ]);

        $this->assertTrue(
            $Posts->saveMany($posts)
        );

        $this->assertFalse(
            $Posts->deleteMany($posts)
        );

        $this->assertSame(
            2,
            $Posts->find()->count()
        );

        $this->assertSame(
            4,
            ModelRegistry::use('Tags')->find()->count()
        );

        $this->assertSame(
            4,
            ModelRegistry::use('PostsTags')->find()->count()
        );
    }

    public function testManyToManyAfterDeleteMany(): void
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
                'title' => 'failAfterDelete',
                'content' => 'This is the content.',
                'tags' => [
                    [
                        'tag' => 'test3'
                    ],
                    [
                        'tag' => 'test4'
                    ]
                ]
            ]
        ]);

        $this->assertTrue(
            $Posts->saveMany($posts)
        );

        $this->assertFalse(
            $Posts->deleteMany($posts)
        );

        $this->assertSame(
            2,
            $Posts->find()->count()
        );

        $this->assertSame(
            4,
            ModelRegistry::use('Tags')->find()->count()
        );

        $this->assertSame(
            4,
            ModelRegistry::use('PostsTags')->find()->count()
        );
    }

    public function testManyToManyValidation(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => '',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => 'test2'
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

    public function testManyToManyValidationNoCheckRules(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => '',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => 'test2'
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

    public function testManyToManyRules(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'failRules',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => 'test2'
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

    public function testManyToManyRulesNoCheckRules(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'failRules',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1'
                ],
                [
                    'tag' => 'test2'
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
