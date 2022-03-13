<?php
declare(strict_types=1);

namespace Tests\Model;

use
    Fyre\ORM\ModelRegistry,
    Tests\Mock\Entity\Address,
    Tests\Mock\Entity\Post,
    Tests\Mock\Entity\Tag;

trait PatchEntityTest
{

    public function testPatchEntity(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEmptyEntity();

        $Test->patchEntity($test, [
            'name' => 'Test'
        ]);

        $this->assertSame(
            'Test',
            $test->get('name')
        );

        $this->assertTrue(
            $test->isDirty()
        );
    }

    public function testPatchEntityBelongsTo(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'Test 1'
            ]
        ]);

        $address->clean();
        $address->user->clean();

        $Addresses->patchEntity($address, [
            'user' => [
                'name' => 'Test 2'
            ]
        ]);

        $this->assertTrue(
            $address->isDirty()
        );

        $this->assertTrue(
            $address->user->isDirty()
        );
    }

    public function testPatchEntityHasMany(): void
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

        $user->clean();
        $user->posts[0]->clean();
        $user->posts[1]->clean();

        $Users->patchEntity($user, [
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
            $user->isDirty()
        );

        $this->assertTrue(
            $user->posts[0]->isDirty()
        );

        $this->assertTrue(
            $user->posts[1]->isDirty()
        );
    }

    public function testPatchEntityHasOne(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'Test 1'
            ]
        ]);

        $user->clean();
        $user->address->clean();

        $Users->patchEntity($user, [
            'address' => [
                'suburb' => 'Test 2'
            ]
        ]);

        $this->assertTrue(
            $user->isDirty()
        );

        $this->assertTrue(
            $user->address->isDirty()
        );
    }

    public function testPatchEntityManyToMany(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                'tag' => 'test1'
            ]
        ]);

        $post->clean();

        $Posts->patchEntity($post, [
            'tags' => [
                [
                    'tag' => 'test2'
                ]
            ]
        ]);

        $this->assertTrue(
            $post->isDirty()
        );
    }

    public function testPatchEntityContain(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test'
        ]);

        $Users->patchEntity($user, [
            'posts' => [
                [
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
                    'title' => 'Test 2',
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
            ],
            'address' => [
                'suburb' => 'Test'
            ]
        ]);

        $this->assertInstanceOf(
            Post::class,
            $user->posts[0]
        );

        $this->assertInstanceOf(
            Post::class,
            $user->posts[1]
        );

        $this->assertInstanceOf(
            Address::class,
            $user->address
        );

        $this->assertInstanceOf(
            Tag::class,
            $user->posts[0]->tags[0]
        );

        $this->assertInstanceOf(
            Tag::class,
            $user->posts[0]->tags[1]
        );

        $this->assertInstanceOf(
            Tag::class,
            $user->posts[1]->tags[0]
        );

        $this->assertInstanceOf(
            Tag::class,
            $user->posts[1]->tags[1]
        );
    }

    public function testPatchEntityAssociated(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test'
        ]);

        $Users->patchEntity($user, [
            'posts' => [
                [
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
                    'title' => 'Test 2',
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
            ],
            'address' => [
                'suburb' => 'Test'
            ]
        ], [
            'associated' => [
                'Posts'
            ]
        ]);

        $this->assertInstanceOf(
            Post::class,
            $user->posts[0]
        );

        $this->assertInstanceOf(
            Post::class,
            $user->posts[1]
        );

        $this->assertNull(
            $user->address
        );

        $this->assertNull(
            $user->posts[0]->tags
        );

        $this->assertNull(
            $user->posts[1]->tags
        );
    }

}
