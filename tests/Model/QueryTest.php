<?php
declare(strict_types=1);

namespace Tests\Model;

use
    Fyre\ORM\ModelRegistry;

use function
    array_map,
    range;

trait QueryTest
{

    public function testInsert(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => 'Test'
        ]);

        $this->assertTrue(
            $Test->save($test)
        );

        $this->assertSame(
            1,
            $test->id
        );

        $this->assertFalse(
            $test->isNew()
        );

        $this->assertFalse(
            $test->isDirty()
        );
    }

    public function testInsertMany(): void
    {
        $Test = ModelRegistry::use('Test');

        $tests = $Test->newEntities([
            [
                'name' => 'Test 1'
            ],
            [
                'name' => 'Test 2'
            ]
        ]);

        $this->assertTrue(
            $Test->saveMany($tests)
        );

        $this->assertSame(
            [1, 2],
            array_map(
                fn($test) => $test->id,
                $tests
            )
        );

        $this->assertFalse(
            $tests[0]->isNew()
        );

        $this->assertFalse(
            $tests[1]->isNew()
        );

        $this->assertFalse(
            $tests[0]->isDirty()
        );

        $this->assertFalse(
            $tests[1]->isDirty()
        );
    }

    public function testInsertManyBatch(): void
    {
        $Test = ModelRegistry::use('Test');

        $data = [];

        for ($i = 0; $i < 1000; $i++) {
            $data[] = [
                'name' => 'Test '.($i + 1)
            ];
        }

        $tests = $Test->newEntities($data);

        $this->assertTrue(
            $Test->saveMany($tests)
        );

        $this->assertSame(
            range(1, 1000),
            array_map(
                fn($test) => $test->id,
                $tests
            )
        );
    }

    public function testUpdate(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => 'Test'
        ]);

        $this->assertTrue(
            $Test->save($test)
        );

        $Test->patchEntity($test, [
            'name' => 'Test 2'
        ]);

        $this->assertTrue(
            $Test->save($test)
        );

        $this->assertFalse(
            $test->isDirty()
        );

        $test = $Test->get(1);

        $this->assertSame(
            [
                'id' => 1,
                'name' => 'Test 2'
            ],
            $test->toArray()
        );
    }

    public function testUpdateMany(): void
    {
        $Test = ModelRegistry::use('Test');

        $tests = $Test->newEntities([
            [
                'name' => 'Test 1'
            ],
            [
                'name' => 'Test 2'
            ]
        ]);

        $this->assertTrue(
            $Test->saveMany($tests)
        );

        $this->assertFalse(
            $tests[0]->isDirty()
        );

        $this->assertFalse(
            $tests[1]->isDirty()
        );

        $Test->patchEntities($tests, [
            [
                'name' => 'Test 3'
            ],
            [
                'name' => 'Test 4'
            ]
        ]);

        $this->assertTrue(
            $Test->saveMany($tests)
        );

        $tests = $Test->find()->all();

        $this->assertSame(
            [
                [
                    'id' => 1,
                    'name' => 'Test 3'
                ],
                [
                    'id' => 2,
                    'name' => 'Test 4'
                ]
            ],
            array_map(
                fn($test) => $test->toArray(),
                $tests,
            )
        );
    }

    public function testUpdateManyBatch(): void
    {
        $Test = ModelRegistry::use('Test');

        $data = [];

        for ($i = 0; $i < 1000; $i++) {
            $data[] = [
                'name' => 'Test'
            ];
        }

        $tests = $Test->newEntities($data);

        $this->assertTrue(
            $Test->saveMany($tests)
        );

        $data = [];

        foreach ($tests AS $i => $test) {
            $data[] = [
                'name' => 'Test '.($i + 1)
            ];
        }

        $Test->patchEntities($tests, $data);

        $this->assertTrue(
            $Test->saveMany($tests)
        );

        $tests = $Test->find()->all();

        $this->assertSame(
            array_map(
                fn($i) => 'Test '.$i,
                range(1, 1000)
            ),
            array_map(
                fn($test) => $test->name,
                $tests
            )
        );
    }

    public function testDelete(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => 'Test'
        ]);

        $this->assertTrue(
            $Test->save($test)
        );

        $this->assertTrue(
            $Test->delete($test)
        );

        $this->assertSame(
            0,
            $Test->find()->count()
        );
    }

    public function testDeleteMany(): void
    {
        $Test = ModelRegistry::use('Test');

        $tests = $Test->newEntities([
            [
                'name' => 'Test 1'
            ],
            [
                'name' => 'Test 2'
            ]
        ]);

        $this->assertTrue(
            $Test->saveMany($tests)
        );

        $this->assertTrue(
            $Test->deleteMany($tests)
        );

        $this->assertSame(
            0,
            $Test->find()->count()
        );
    }

    public function testGet(): void
    {
        $Test = ModelRegistry::use('Test');

        $tests = $Test->newEntities([
            [
                'name' => 'Test 1'
            ],
            [
                'name' => 'Test 2'
            ]
        ]);

        $this->assertTrue(
            $Test->saveMany($tests)
        );

        $test = $Test->get(2);

        $this->assertSame(
            2,
            $test->id
        );
    }

    public function testGetInvalid(): void
    {
        $this->assertNull(
            ModelRegistry::use('Test')->get(1)
        );
    }

    public function testExists(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => 'Test'
        ]);

        $this->assertTrue(
            $Test->save($test)
        );

        $this->assertTrue(
            $Test->exists(['name' => 'Test'])
        );
    }

    public function testExistsNotExists(): void
    {
        $this->assertFalse(
            ModelRegistry::use('Test')->exists(['name' => 'Test'])
        );
    }

    public function testFindOptionSql(): void
    {
        $this->assertSame(
            'SELECT Test.id AS Test__id, CONCAT(Test.name, " ", Test2.name) AS title FROM test AS Test LEFT JOIN test AS Test2 ON Test2.id = Test.id WHERE Test.id = 1 ORDER BY Test.name DESC GROUP BY Test.id HAVING title = \'Test Test\' LIMIT 1 FOR UPDATE',
            ModelRegistry::use('Test')->find([
                'fields' => [
                    'title' => 'CONCAT(Test.name, " ", Test2.name)'
                ],
                'join' => [
                    'Test2' => [
                        'table' => 'test',
                        'type' => 'LEFT',
                        'conditions' => [
                            'Test2.id = Test.id'
                        ]
                    ]
                ],
                'conditions' => [
                    'Test.id' => 1
                ],
                'order' => [
                    'Test.name' => 'DESC'
                ],
                'group' => [
                    'Test.id'
                ],
                'having' => [
                    'title' => 'Test Test'
                ],
                'limit' => 1,
                'offset' => 0,
                'epilog' => 'FOR UPDATE'
            ])->sql()
        );
    }

    public function testFindSubquery(): void
    {
        $Test = ModelRegistry::use('Test');

        $this->assertSame(
            'SELECT Test.id AS Test__id, (SELECT Users.name AS user_name FROM users AS Users INNER JOIN posts AS Posts ON Posts.user_id = Users.id WHERE Users.id = Test.id LIMIT 1) AS user_name FROM test AS Test',
            $Test->find([
                'fields' => [
                    'user_name' => ModelRegistry::use('Users')
                        ->subquery()
                        ->select([
                            'user_name' => 'Users.name'
                        ])
                        ->innerJoinWith('Posts')
                        ->where([
                            'Users.id = Test.id'
                        ])
                        ->limit(1)
                ]
            ])->sql()
        );
    }

    public function testFindSubqueryAlias(): void
    {
        $Test = ModelRegistry::use('Test');

        $this->assertSame(
            'SELECT Test.id AS Test__id, (SELECT Alias.name AS user_name FROM users AS Alias INNER JOIN posts AS Posts ON Posts.user_id = Alias.id WHERE Alias.id = Test.id LIMIT 1) AS user_name FROM test AS Test',
            $Test->find([
                'fields' => [
                    'user_name' => ModelRegistry::use('Users')
                        ->subquery([
                            'alias' => 'Alias'
                        ])
                        ->select([
                            'user_name' => 'Alias.name'
                        ])
                        ->innerJoinWith('Posts')
                        ->where([
                            'Alias.id = Test.id'
                        ])
                        ->limit(1)
                ]
            ])->sql()
        );
    }

    public function testFindAutoFields(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => 'Test'
        ]);

        $this->assertTrue(
            $Test->save($test)
        );

        $test = $Test->get(1, [
            'autoFields' => false
        ]);

        $this->assertSame(
            [
                'id' => 1
            ],
            $test->toArray()
        );
    }

}
