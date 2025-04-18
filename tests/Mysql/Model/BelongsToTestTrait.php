<?php
declare(strict_types=1);

namespace Tests\Mysql\Model;

use Fyre\ORM\Exceptions\OrmException;
use Fyre\ORM\Queries\SelectQuery;
use Tests\Mock\Entity\Address;
use Tests\Mock\Entity\User;

trait BelongsToTestTrait
{
    public function testBelongsToContainConditionsSql(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $Addresses->Users->setConditions([
            'Users.name' => 'test',
        ]);

        $this->assertSame(
            'SELECT Addresses.id AS Addresses__id, Users.id AS Users__id FROM addresses AS Addresses LEFT JOIN users AS Users ON Users.id = Addresses.user_id AND Users.name = \'test\'',
            $Addresses->find([
                'contain' => [
                    'Users',
                ],
            ])
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testBelongsToContainJoinTypeSql(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $Addresses->Users->setJoinType('inner');

        $this->assertSame(
            'SELECT Addresses.id AS Addresses__id, Users.id AS Users__id FROM addresses AS Addresses INNER JOIN users AS Users ON Users.id = Addresses.user_id',
            $Addresses->find([
                'contain' => [
                    'Users',
                ],
            ])
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testBelongsToContainTypeJoinSql(): void
    {
        $this->assertSame(
            'SELECT Addresses.id AS Addresses__id, Users.id AS Users__id FROM addresses AS Addresses INNER JOIN users AS Users ON Users.id = Addresses.user_id',
            $this->modelRegistry->use('Addresses')
                ->find([
                    'contain' => [
                        'Users' => [
                            'type' => 'INNER',
                        ],
                    ],
                ])
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testBelongsToDelete(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Addresses->save($address)
        );

        $this->assertTrue(
            $Addresses->delete($address)
        );

        $this->assertSame(
            0,
            $Addresses->find()->count()
        );

        $this->assertSame(
            1,
            $this->modelRegistry->use('Users')->find()->count()
        );
    }

    public function testBelongsToDeleteMany(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $addresses = $Addresses->newEntities([
            [
                'suburb' => 'Test 1',
                'user' => [
                    'name' => 'Test 1',
                ],
            ],
            [
                'suburb' => 'Test 2',
                'user' => [
                    'name' => 'Test 2',
                ],
            ],
        ]);

        $this->assertTrue(
            $Addresses->saveMany($addresses)
        );

        $this->assertTrue(
            $Addresses->deleteMany($addresses)
        );

        $this->assertSame(
            0,
            $Addresses->find()->count()
        );

        $this->assertSame(
            2,
            $this->modelRegistry->use('Users')->find()->count()
        );
    }

    public function testBelongsToFind(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Addresses->save($address)
        );

        $address = $Addresses->get(1, [
            'contain' => [
                'Users',
            ],
        ]);

        $this->assertSame(
            1,
            $address->id
        );

        $this->assertSame(
            1,
            $address->user->id
        );

        $this->assertInstanceOf(
            Address::class,
            $address
        );

        $this->assertInstanceOf(
            User::class,
            $address->user
        );

        $this->assertFalse(
            $address->isNew()
        );

        $this->assertFalse(
            $address->user->isNew()
        );
    }

    public function testBelongsToFindCallback(): void
    {
        $this->expectException(OrmException::class);

        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Addresses->save($address)
        );

        $address = $Addresses->get(1, [
            'contain' => [
                'Users' => [
                    'callback' => fn(SelectQuery $query): SelectQuery => $query->where(['Users.id' => 1]),
                ],
            ],
        ]);
    }

    public function testBelongsToFindRelated(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Addresses->save($address)
        );

        $address = $Addresses->get(1);

        $user = $Addresses->Users->findRelated([$address])->first();

        $this->assertSame(
            1,
            $user->id
        );

        $this->assertInstanceOf(
            User::class,
            $user
        );
    }

    public function testBelongsToFindSql(): void
    {
        $this->assertSame(
            'SELECT Addresses.id AS Addresses__id, Users.id AS Users__id FROM addresses AS Addresses LEFT JOIN users AS Users ON Users.id = Addresses.user_id',
            $this->modelRegistry->use('Addresses')
                ->find([
                    'contain' => [
                        'Users',
                    ],
                ])
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testBelongsToInnerJoinSql(): void
    {
        $this->assertSame(
            'SELECT Addresses.id AS Addresses__id FROM addresses AS Addresses INNER JOIN users AS Users ON Users.id = Addresses.user_id',
            $this->modelRegistry->use('Addresses')
                ->find()
                ->innerJoinWith('Users')
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testBelongsToInsert(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Addresses->save($address)
        );

        $this->assertSame(
            1,
            $address->id
        );

        $this->assertSame(
            1,
            $address->user->id
        );

        $this->assertSame(
            1,
            $address->user_id
        );

        $this->assertFalse(
            $address->isNew()
        );

        $this->assertFalse(
            $address->user->isNew()
        );

        $this->assertFalse(
            $address->isDirty()
        );

        $this->assertFalse(
            $address->user->isDirty()
        );
    }

    public function testBelongsToInsertMany(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $addresses = [
            $Addresses->newEntity([
                'suburb' => 'Test 1',
                'user' => [
                    'name' => 'Test 1',
                ],
            ]),
            $Addresses->newEntity([
                'suburb' => 'Test 2',
                'user' => [
                    'name' => 'Test 2',
                ],
            ]),
        ];

        $this->assertTrue(
            $Addresses->saveMany($addresses)
        );

        $this->assertSame(
            [1, 2],
            array_map(
                fn($address) => $address->id,
                $addresses
            )
        );

        $this->assertSame(
            [1, 2],
            array_map(
                fn($address) => $address->user->id,
                $addresses
            )
        );

        $this->assertSame(
            [1, 2],
            array_map(
                fn($address) => $address->user_id,
                $addresses
            )
        );

        $this->assertFalse(
            $addresses[0]->isNew()
        );

        $this->assertFalse(
            $addresses[1]->isNew()
        );

        $this->assertFalse(
            $addresses[0]->user->isNew()
        );

        $this->assertFalse(
            $addresses[1]->user->isNew()
        );

        $this->assertFalse(
            $addresses[0]->isDirty()
        );

        $this->assertFalse(
            $addresses[1]->isDirty()
        );

        $this->assertFalse(
            $addresses[0]->user->isDirty()
        );

        $this->assertFalse(
            $addresses[1]->user->isDirty()
        );
    }

    public function testBelongsToLeftJoinSql(): void
    {
        $this->assertSame(
            'SELECT Addresses.id AS Addresses__id FROM addresses AS Addresses LEFT JOIN users AS Users ON Users.id = Addresses.user_id',
            $this->modelRegistry->use('Addresses')
                ->find()
                ->leftJoinWith('Users')
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testBelongsToStrategyFind(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Addresses->save($address)
        );

        $address = $Addresses->get(1, [
            'contain' => [
                'Users' => [
                    'strategy' => 'select',
                ],
            ],
        ]);

        $this->assertSame(
            1,
            $address->id
        );

        $this->assertSame(
            1,
            $address->user->id
        );

        $this->assertInstanceOf(
            Address::class,
            $address
        );

        $this->assertInstanceOf(
            User::class,
            $address->user
        );

        $this->assertFalse(
            $address->isNew()
        );

        $this->assertFalse(
            $address->user->isNew()
        );
    }

    public function testBelongsToStrategyFindCallback(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Addresses->save($address)
        );

        $address = $Addresses->get(1, [
            'contain' => [
                'Users' => [
                    'strategy' => 'select',
                    'callback' => fn(SelectQuery $query): SelectQuery => $query->where(['Users.id' => 2]),
                ],
            ],
        ]);

        $this->assertSame(
            1,
            $address->id
        );

        $this->assertNull(
            $address->user
        );

        $this->assertInstanceOf(
            Address::class,
            $address
        );

        $this->assertFalse(
            $address->isNew()
        );
    }

    public function testBelongsToStrategyFindSql(): void
    {
        $this->assertSame(
            'SELECT Addresses.id AS Addresses__id, Addresses.user_id AS Addresses__user_id FROM addresses AS Addresses',
            $this->modelRegistry->use('Addresses')
                ->find([
                    'contain' => [
                        'Users' => [
                            'strategy' => 'select',
                        ],
                    ],
                ])
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testBelongsToUpdate(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Addresses->save($address)
        );

        $Addresses->patchEntity($address, [
            'suburb' => 'Test 2',
            'user' => [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Addresses->save($address)
        );

        $this->assertFalse(
            $address->isDirty()
        );

        $this->assertFalse(
            $address->user->isDirty()
        );

        $address = $Addresses->get(1, [
            'contain' => [
                'Users',
            ],
        ]);

        $this->assertSame(
            'Test 2',
            $address->suburb
        );

        $this->assertSame(
            'Test 2',
            $address->user->name
        );
    }

    public function testBelongsToUpdateMany(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $addresses = $Addresses->newEntities([
            [
                'suburb' => 'Test 1',
                'user' => [
                    'name' => 'Test 1',
                ],
            ],
            [
                'suburb' => 'Test 2',
                'user' => [
                    'name' => 'Test 2',
                ],
            ],
        ]);

        $this->assertTrue(
            $Addresses->saveMany($addresses)
        );

        $Addresses->patchEntities($addresses, [
            [
                'suburb' => 'Test 3',
                'user' => [
                    'name' => 'Test 3',
                ],
            ],
            [
                'suburb' => 'Test 4',
                'user' => [
                    'name' => 'Test 4',
                ],
            ],
        ]);

        $this->assertTrue(
            $Addresses->saveMany($addresses)
        );

        $this->assertFalse(
            $addresses[0]->isDirty()
        );

        $this->assertFalse(
            $addresses[1]->isDirty()
        );

        $this->assertFalse(
            $addresses[0]->user->isDirty()
        );

        $this->assertFalse(
            $addresses[1]->user->isDirty()
        );

        $addresses = $Addresses->find([
            'contain' => [
                'Users',
            ],
        ])->toArray();

        $this->assertSame(
            'Test 3',
            $addresses[0]->suburb
        );

        $this->assertSame(
            'Test 3',
            $addresses[0]->user->name
        );

        $this->assertSame(
            'Test 4',
            $addresses[1]->suburb
        );

        $this->assertSame(
            'Test 4',
            $addresses[1]->user->name
        );
    }
}
