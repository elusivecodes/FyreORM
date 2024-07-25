<?php
declare(strict_types=1);

namespace Tests\Postgres\Model;

use Fyre\Entity\Entity;
use Fyre\ORM\ModelRegistry;
use Tests\Mock\Entity\Post;
use Tests\Mock\Entity\Tag;

trait ManyToManyTestTrait
{
    public function testManyToManyDelete(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1',
                ],
                [
                    'tag' => 'test2',
                ],
            ],
        ]);

        $this->assertTrue(
            $Posts->save($post)
        );

        $this->assertTrue(
            $Posts->delete($post)
        );

        $this->assertSame(
            0,
            $Posts->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('PostsTags')->find()->count()
        );

        $this->assertSame(
            2,
            ModelRegistry::use('Tags')->find()->count()
        );
    }

    public function testManyToManyDeleteMany(): void
    {
        $Posts = ModelRegistry::use('Posts');

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
                        'tag' => 'test4',
                    ],
                ],
            ],
        ]);

        $this->assertTrue(
            $Posts->saveMany($posts)
        );

        $this->assertTrue(
            $Posts->deleteMany($posts)
        );

        $this->assertSame(
            0,
            $Posts->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('PostsTags')->find()->count()
        );

        $this->assertSame(
            4,
            ModelRegistry::use('Tags')->find()->count()
        );
    }

    public function testManyToManyFind(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1',
                ],
                [
                    'tag' => 'test2',
                ],
            ],
        ]);

        $this->assertTrue(
            $Posts->save($post)
        );

        $post = $Posts->get(1, [
            'contain' => [
                'Tags',
            ],
        ]);

        $this->assertSame(
            1,
            $post->id
        );

        $this->assertSame(
            [1, 2],
            array_map(
                fn($tag) => $tag->id,
                $post->tags
            )
        );

        $this->assertSame(
            [1, 2],
            array_map(
                fn($tag) => $tag->_joinData->id,
                $post->tags
            )
        );

        $this->assertInstanceOf(
            Post::class,
            $post
        );

        $this->assertInstanceOf(
            Tag::class,
            $post->tags[0]
        );

        $this->assertInstanceOf(
            Tag::class,
            $post->tags[1]
        );

        $this->assertInstanceOf(
            Entity::class,
            $post->tags[0]->_joinData
        );

        $this->assertInstanceOf(
            Entity::class,
            $post->tags[1]->_joinData
        );

        $this->assertFalse(
            $post->isNew()
        );

        $this->assertFalse(
            $post->tags[0]->isNew()
        );

        $this->assertFalse(
            $post->tags[1]->isNew()
        );

        $this->assertFalse(
            $post->tags[0]->_joinData->isNew()
        );

        $this->assertFalse(
            $post->tags[1]->_joinData->isNew()
        );
    }

    public function testManyToManyInnerJoinSql(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS "Posts__id" FROM posts AS Posts INNER JOIN posts_tags AS PostsTags ON PostsTags.post_id = Posts.id INNER JOIN tags AS Tags ON Tags.id = PostsTags.tag_id',
            ModelRegistry::use('Posts')
                ->find()
                ->innerJoinWith('Tags')
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testManyToManyInsert(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1',
                ],
                [
                    'tag' => 'test2',
                ],
            ],
        ]);

        $this->assertTrue(
            $Posts->save($post)
        );

        $this->assertSame(
            1,
            $post->id
        );

        $this->assertSame(
            [1, 2],
            array_map(
                fn($tag) => $tag->id,
                $post->tags
            )
        );

        $this->assertFalse(
            $post->isNew()
        );

        $this->assertFalse(
            $post->tags[0]->isNew()
        );

        $this->assertFalse(
            $post->tags[1]->isNew()
        );

        $this->assertFalse(
            $post->isDirty()
        );

        $this->assertFalse(
            $post->tags[0]->isDirty()
        );

        $this->assertFalse(
            $post->tags[1]->isDirty()
        );
    }

    public function testManyToManyInsertMany(): void
    {
        $Posts = ModelRegistry::use('Posts');

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
                        'tag' => 'test4',
                    ],
                ],
            ],
        ]);

        $this->assertTrue(
            $Posts->saveMany($posts)
        );

        $this->assertSame(
            [1, 2],
            array_map(
                fn($post) => $post->id,
                $posts
            )
        );

        $this->assertSame(
            [
                [1, 2],
                [3, 4],
            ],
            array_map(
                fn($post) => array_map(
                    fn($tag) => $tag->id,
                    $post->tags
                ),
                $posts
            )
        );

        $this->assertFalse(
            $posts[0]->isNew()
        );

        $this->assertFalse(
            $posts[1]->isNew()
        );

        $this->assertFalse(
            $posts[0]->tags[0]->isNew()
        );

        $this->assertFalse(
            $posts[0]->tags[1]->isNew()
        );

        $this->assertFalse(
            $posts[1]->tags[0]->isNew()
        );

        $this->assertFalse(
            $posts[1]->tags[1]->isNew()
        );

        $this->assertFalse(
            $posts[0]->isDirty()
        );

        $this->assertFalse(
            $posts[1]->isDirty()
        );

        $this->assertFalse(
            $posts[0]->tags[0]->isDirty()
        );

        $this->assertFalse(
            $posts[0]->tags[1]->isDirty()
        );

        $this->assertFalse(
            $posts[1]->tags[0]->isDirty()
        );

        $this->assertFalse(
            $posts[1]->tags[1]->isDirty()
        );
    }

    public function testManyToManyLeftJoinSql(): void
    {
        $this->assertSame(
            'SELECT Posts.id AS "Posts__id" FROM posts AS Posts LEFT JOIN posts_tags AS PostsTags ON PostsTags.post_id = Posts.id LEFT JOIN tags AS Tags ON Tags.id = PostsTags.tag_id',
            ModelRegistry::use('Posts')
                ->find()
                ->leftJoinWith('Tags')
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testManyToManyReplace(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1',
                ],
                [
                    'tag' => 'test2',
                ],
            ],
        ]);

        $this->assertTrue(
            $Posts->save($post)
        );

        $post->tags = [];

        $this->assertTrue(
            $Posts->save($post)
        );

        $this->assertSame(
            0,
            ModelRegistry::use('PostsTags')->find()->count()
        );

        $this->assertSame(
            2,
            ModelRegistry::use('Tags')->find()->count()
        );
    }

    public function testManyToManyReplaceMany(): void
    {
        $Posts = ModelRegistry::use('Posts');

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
                        'tag' => 'test4',
                    ],
                ],
            ],
        ]);

        $this->assertTrue(
            $Posts->saveMany($posts)
        );

        $Posts->patchEntities($posts, [
            [
                'tags' => [],
            ],
            [
                'tags' => [],
            ],
        ]);

        $this->assertTrue(
            $Posts->saveMany($posts)
        );

        $this->assertSame(
            0,
            ModelRegistry::use('PostsTags')->find()->count()
        );

        $this->assertSame(
            4,
            ModelRegistry::use('Tags')->find()->count()
        );
    }

    public function testManyToManySelfSql(): void
    {
        $Items = ModelRegistry::use('Items');

        $Items->manyToMany('ChildItems', [
            'className' => 'Items',
            'through' => 'Contains',
            'foreignKey' => 'item_id',
            'targetForeignKey' => 'contained_item_id',
        ]);

        $Items->manyToMany('ParentItems', [
            'className' => 'Items',
            'through' => 'Contains',
            'foreignKey' => 'contained_item_id',
            'targetForeignKey' => 'item_id',
        ]);

        $this->assertSame(
            'SELECT Items.id AS "Items__id" FROM items AS Items INNER JOIN contains AS Contains ON Contains.item_id = Items.id INNER JOIN items AS ChildItems ON ChildItems.id = Contains.contained_item_id',
            $Items->find()
                ->innerJoinWith('ChildItems')
                ->enableAutoFields(false)
                ->sql()
        );

        $this->assertSame(
            'SELECT Items.id AS "Items__id" FROM items AS Items INNER JOIN contains AS Contains ON Contains.contained_item_id = Items.id INNER JOIN items AS ParentItems ON ParentItems.id = Contains.item_id',
            $Items->find()
                ->innerJoinWith('ParentItems')
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testManyToManyStrategyFind(): void
    {
        $Posts = ModelRegistry::use('Posts');

        $post = $Posts->newEntity([
            'user_id' => 1,
            'title' => 'Test',
            'content' => 'This is the content.',
            'tags' => [
                [
                    'tag' => 'test1',
                ],
                [
                    'tag' => 'test2',
                ],
            ],
        ]);

        $this->assertTrue(
            $Posts->save($post)
        );

        $post = $Posts->get(1, [
            'contain' => [
                'Tags' => [
                    'strategy' => 'subquery',
                ],
            ],
        ]);

        $this->assertSame(
            1,
            $post->id
        );

        $this->assertSame(
            [1, 2],
            array_map(
                fn($tag) => $tag->id,
                $post->tags
            )
        );

        $this->assertSame(
            [1, 2],
            array_map(
                fn($tag) => $tag->_joinData->id,
                $post->tags
            )
        );

        $this->assertInstanceOf(
            Post::class,
            $post
        );

        $this->assertInstanceOf(
            Tag::class,
            $post->tags[0]
        );

        $this->assertInstanceOf(
            Tag::class,
            $post->tags[1]
        );

        $this->assertInstanceOf(
            Entity::class,
            $post->tags[0]->_joinData
        );

        $this->assertInstanceOf(
            Entity::class,
            $post->tags[1]->_joinData
        );

        $this->assertFalse(
            $post->isNew()
        );

        $this->assertFalse(
            $post->tags[0]->isNew()
        );

        $this->assertFalse(
            $post->tags[1]->isNew()
        );

        $this->assertFalse(
            $post->tags[0]->_joinData->isNew()
        );

        $this->assertFalse(
            $post->tags[1]->_joinData->isNew()
        );
    }
}
