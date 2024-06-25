<?php
declare(strict_types=1);

namespace Tests\Model;

use Fyre\ORM\ModelRegistry;

use function array_map;

trait LoadIntoTestTrait
{
    public function testLoadInto(): void
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

        $user = $Users->get(1);

        $Users->loadInto($user, [
            'Addresses',
            'Posts' => [
                'Tags',
            ],
        ]);

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

    public function testLoadIntoOverwrites(): void
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

        $user = $Users->get(1);

        $Users->loadInto($user, [
            'Posts',
        ]);

        $this->assertSame(
            [1, 2],
            array_map(
                fn($post) => $post->id,
                $user->posts
            )
        );
    }
}
