<?php
declare(strict_types=1);

namespace Tests\Sqlite\Model;

use Fyre\ORM\ModelRegistry;

use function array_map;
use function range;

trait QueryTestTrait
{
    public function testDelete(): void
    {
        $Items = ModelRegistry::use('Items');

        $item = $Items->newEntity([
            'name' => 'Test',
        ]);

        $this->assertTrue(
            $Items->save($item)
        );

        $this->assertTrue(
            $Items->delete($item)
        );

        $this->assertSame(
            0,
            $Items->find()->count()
        );
    }

    public function testDeleteMany(): void
    {
        $Items = ModelRegistry::use('Items');

        $items = $Items->newEntities([
            [
                'name' => 'Test 1',
            ],
            [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Items->saveMany($items)
        );

        $this->assertTrue(
            $Items->deleteMany($items)
        );

        $this->assertSame(
            0,
            $Items->find()->count()
        );
    }

    public function testExists(): void
    {
        $Items = ModelRegistry::use('Items');

        $item = $Items->newEntity([
            'name' => 'Test',
        ]);

        $this->assertTrue(
            $Items->save($item)
        );

        $this->assertTrue(
            $Items->exists(['name' => 'Test'])
        );
    }

    public function testExistsNotExists(): void
    {
        $this->assertFalse(
            ModelRegistry::use('Items')->exists(['name' => 'Test'])
        );
    }

    public function testFindAutoFields(): void
    {
        $Items = ModelRegistry::use('Items');

        $item = $Items->newEntity([
            'name' => 'Test',
        ]);

        $this->assertTrue(
            $Items->save($item)
        );

        $item = $Items->get(1, [
            'autoFields' => false,
        ]);

        $this->assertSame(
            [
                'id' => 1,
            ],
            $item->toArray()
        );
    }

    public function testFindOptionSql(): void
    {
        $this->assertSame(
            'SELECT Items.id AS Items__id, CONCAT(Items.name, " ", Items2.name) AS title FROM items AS Items LEFT JOIN items AS Items2 ON Items2.id = Items.id WHERE Items.id = 1 GROUP BY Items.id ORDER BY Items.name DESC HAVING title = \'Test Test\' LIMIT 1 FOR UPDATE',
            ModelRegistry::use('Items')->find([
                'fields' => [
                    'title' => 'CONCAT(Items.name, " ", Items2.name)',
                ],
                'join' => [
                    'Items2' => [
                        'table' => 'items',
                        'type' => 'LEFT',
                        'conditions' => [
                            'Items2.id = Items.id',
                        ],
                    ],
                ],
                'conditions' => [
                    'Items.id' => 1,
                ],
                'groupBy' => [
                    'Items.id',
                ],
                'orderBy' => [
                    'Items.name' => 'DESC',
                ],
                'having' => [
                    'title' => 'Test Test',
                ],
                'limit' => 1,
                'offset' => 0,
                'epilog' => 'FOR UPDATE',
            ])->sql()
        );
    }

    public function testFindSubquery(): void
    {
        $Items = ModelRegistry::use('Items');

        $this->assertSame(
            'SELECT Items.id AS Items__id, (SELECT Users.name AS user_name FROM users AS Users INNER JOIN posts AS Posts ON Posts.user_id = Users.id WHERE Users.id = Items.id LIMIT 1) AS user_name FROM items AS Items',
            $Items->find([
                'fields' => [
                    'user_name' => ModelRegistry::use('Users')
                        ->subquery()
                        ->select([
                            'user_name' => 'Users.name',
                        ])
                        ->innerJoinWith('Posts')
                        ->where([
                            'Users.id = Items.id',
                        ])
                        ->limit(1),
                ],
            ])->sql()
        );
    }

    public function testFindSubqueryAlias(): void
    {
        $Items = ModelRegistry::use('Items');

        $this->assertSame(
            'SELECT Items.id AS Items__id, (SELECT Alias.name AS user_name FROM users AS Alias INNER JOIN posts AS Posts ON Posts.user_id = Alias.id WHERE Alias.id = Items.id LIMIT 1) AS user_name FROM items AS Items',
            $Items->find([
                'fields' => [
                    'user_name' => ModelRegistry::use('Users')
                        ->subquery([
                            'alias' => 'Alias',
                        ])
                        ->select([
                            'user_name' => 'Alias.name',
                        ])
                        ->innerJoinWith('Posts')
                        ->where([
                            'Alias.id = Items.id',
                        ])
                        ->limit(1),
                ],
            ])->sql()
        );
    }

    public function testGet(): void
    {
        $Items = ModelRegistry::use('Items');

        $items = $Items->newEntities([
            [
                'name' => 'Test 1',
            ],
            [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Items->saveMany($items)
        );

        $item = $Items->get(2);

        $this->assertSame(
            2,
            $item->id
        );
    }

    public function testGetInvalid(): void
    {
        $this->assertNull(
            ModelRegistry::use('Items')->get(1)
        );
    }

    public function testInsert(): void
    {
        $Items = ModelRegistry::use('Items');

        $item = $Items->newEntity([
            'name' => 'Test',
        ]);

        $this->assertTrue(
            $Items->save($item)
        );

        $this->assertSame(
            1,
            $item->id
        );

        $this->assertFalse(
            $item->isNew()
        );

        $this->assertFalse(
            $item->isDirty()
        );
    }

    public function testInsertMany(): void
    {
        $Items = ModelRegistry::use('Items');

        $items = $Items->newEntities([
            [
                'name' => 'Test 1',
            ],
            [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Items->saveMany($items)
        );

        $this->assertSame(
            [1, 2],
            array_map(
                fn($item) => $item->id,
                $items
            )
        );

        $this->assertFalse(
            $items[0]->isNew()
        );

        $this->assertFalse(
            $items[1]->isNew()
        );

        $this->assertFalse(
            $items[0]->isDirty()
        );

        $this->assertFalse(
            $items[1]->isDirty()
        );
    }

    public function testInsertManyBatch(): void
    {
        $Items = ModelRegistry::use('Items');

        $data = [];

        for ($i = 0; $i < 1000; $i++) {
            $data[] = [
                'name' => 'Test '.($i + 1),
            ];
        }

        $items = $Items->newEntities($data);

        $this->assertTrue(
            $Items->saveMany($items)
        );

        $this->assertSame(
            range(1, 1000),
            array_map(
                fn($item) => $item->id,
                $items
            )
        );
    }

    public function testUpdate(): void
    {
        $Items = ModelRegistry::use('Items');

        $item = $Items->newEntity([
            'name' => 'Test',
        ]);

        $this->assertTrue(
            $Items->save($item)
        );

        $Items->patchEntity($item, [
            'name' => 'Test 2',
        ]);

        $this->assertTrue(
            $Items->save($item)
        );

        $this->assertFalse(
            $item->isDirty()
        );

        $item = $Items->get(1);

        $this->assertSame(
            [
                'id' => 1,
                'name' => 'Test 2',
            ],
            $item->toArray()
        );
    }

    public function testUpdateMany(): void
    {
        $Items = ModelRegistry::use('Items');

        $items = $Items->newEntities([
            [
                'name' => 'Test 1',
            ],
            [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Items->saveMany($items)
        );

        $this->assertFalse(
            $items[0]->isDirty()
        );

        $this->assertFalse(
            $items[1]->isDirty()
        );

        $Items->patchEntities($items, [
            [
                'name' => 'Test 3',
            ],
            [
                'name' => 'Test 4',
            ],
        ]);

        $this->assertTrue(
            $Items->saveMany($items)
        );

        $items = $Items->find()->all();

        $this->assertSame(
            [
                [
                    'id' => 1,
                    'name' => 'Test 3',
                ],
                [
                    'id' => 2,
                    'name' => 'Test 4',
                ],
            ],
            array_map(
                fn($item) => $item->toArray(),
                $items,
            )
        );
    }

    public function testUpdateManyBatch(): void
    {
        $Items = ModelRegistry::use('Items');

        $data = [];

        for ($i = 0; $i < 1000; $i++) {
            $data[] = [
                'name' => 'Test',
            ];
        }

        $items = $Items->newEntities($data);

        $this->assertTrue(
            $Items->saveMany($items)
        );

        $data = [];

        foreach ($items as $i => $item) {
            $data[] = [
                'name' => 'Test '.($i + 1),
            ];
        }

        $Items->patchEntities($items, $data);

        $this->assertTrue(
            $Items->saveMany($items)
        );

        $items = $Items->find()->all();

        $this->assertSame(
            array_map(
                fn($i) => 'Test '.$i,
                range(1, 1000)
            ),
            array_map(
                fn($item) => $item->name,
                $items
            )
        );
    }
}
