<?php
declare(strict_types=1);

namespace Tests\Sqlite\Model;

use Tests\Mock\Entity\Address;
use Tests\Mock\Entity\Post;
use Tests\Mock\Entity\Tag;

trait PatchEntityTestTrait
{
    public function testPatchEntity(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $item = $Items->newEmptyEntity();

        $Items->patchEntity($item, [
            'name' => 'Test',
        ]);

        $this->assertSame(
            'Test',
            $item->get('name')
        );

        $this->assertTrue(
            $item->isDirty()
        );
    }

    public function testPatchEntityAssociated(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
        ]);

        $Users->patchEntity($user, [
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
        ], [
            'associated' => [
                'Posts',
            ],
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

    public function testPatchEntityBelongsTo(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'Test 1',
            ],
        ]);

        $address->clean();
        $address->user->clean();

        $Addresses->patchEntity($address, [
            'user' => [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $address->isDirty()
        );

        $this->assertTrue(
            $address->user->isDirty()
        );
    }

    public function testPatchEntityContain(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
        ]);

        $Users->patchEntity($user, [
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

    public function testPatchEntityHasMany(): void
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

        $user->clean();
        $user->posts[0]->clean();
        $user->posts[1]->clean();

        $Users->patchEntity($user, [
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
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'Test 1',
            ],
        ]);

        $user->clean();
        $user->address->clean();

        $Users->patchEntity($user, [
            'address' => [
                'suburb' => 'Test 2',
            ],
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
        $Posts = $this->modelRegistry->use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                'tag' => 'test1',
            ],
        ]);

        $post->clean();

        $Posts->patchEntity($post, [
            'tags' => [
                [
                    'tag' => 'test2',
                ],
            ],
        ]);

        $this->assertTrue(
            $post->isDirty()
        );
    }
}
