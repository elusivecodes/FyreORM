<?php
declare(strict_types=1);

namespace Tests\Mysql\Model;

use Fyre\ORM\Exceptions\OrmException;
use Fyre\ORM\Queries\SelectQuery;
use Tests\Mock\Entity\Address;
use Tests\Mock\Entity\User;

trait HasOneTestTrait
{
    public function testHasOneContainConditionsSql(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $Users->Addresses->setConditions([
            'Addresses.address_1' => 'test',
        ]);

        $this->assertSame(
            'SELECT Users.id AS Users__id, Addresses.id AS Addresses__id FROM users AS Users LEFT JOIN addresses AS Addresses ON Addresses.user_id = Users.id AND Addresses.address_1 = \'test\'',
            $Users->find([
                'contain' => [
                    'Addresses',
                ],
            ])
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testHasOneContainJoinTypeSql(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $Users->Addresses->setJoinType('inner');

        $this->assertSame(
            'SELECT Users.id AS Users__id, Addresses.id AS Addresses__id FROM users AS Users INNER JOIN addresses AS Addresses ON Addresses.user_id = Users.id',
            $Users->find([
                'contain' => [
                    'Addresses',
                ],
            ])
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testHasOneContainTypeJoinSql(): void
    {
        $this->assertSame(
            'SELECT Users.id AS Users__id, Addresses.id AS Addresses__id FROM users AS Users INNER JOIN addresses AS Addresses ON Addresses.user_id = Users.id',
            $this->modelRegistry->use('Users')
                ->find([
                    'contain' => [
                        'Addresses' => [
                            'type' => 'INNER',
                        ],
                    ],
                ])
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testHasOneDelete(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $this->assertTrue(
            $Users->delete($user)
        );

        $this->assertSame(
            0,
            $Users->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Addresses')->find()->count()
        );
    }

    public function testHasOneDeleteMany(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'address' => [
                    'suburb' => 'Test 1',
                ],
            ],
            [
                'name' => 'Test 2',
                'address' => [
                    'suburb' => 'Test 2',
                ],
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertTrue(
            $Users->deleteMany($users)
        );

        $this->assertSame(
            0,
            $Users->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Addresses')->find()->count()
        );
    }

    public function testHasOneFind(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $user = $Users->get(1, [
            'contain' => [
                'Addresses',
            ],
        ]);

        $this->assertSame(
            1,
            $user->id
        );

        $this->assertSame(
            1,
            $user->address->id
        );

        $this->assertInstanceOf(
            User::class,
            $user
        );

        $this->assertInstanceOf(
            Address::class,
            $user->address
        );

        $this->assertFalse(
            $user->isNew()
        );

        $this->assertFalse(
            $user->address->isNew()
        );
    }

    public function testHasOneFindCallback(): void
    {
        $this->expectException(OrmException::class);

        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $user = $Users->get(1, [
            'contain' => [
                'Addresses' => [
                    'callback' => fn(SelectQuery $query): SelectQuery => $query->where(['Addresses.id' => 1]),
                ],
            ],
        ]);
    }

    public function testHasOneFindRelated(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $user = $Users->get(1);

        $address = $Users->Addresses->findRelated([$user])->first();

        $this->assertSame(
            1,
            $address->id
        );

        $this->assertInstanceOf(
            Address::class,
            $address
        );
    }

    public function testHasOneFindSql(): void
    {
        $this->assertSame(
            'SELECT Users.id AS Users__id, Addresses.id AS Addresses__id FROM users AS Users LEFT JOIN addresses AS Addresses ON Addresses.user_id = Users.id',
            $this->modelRegistry->use('Users')
                ->find([
                    'fields' => [
                        'Users.id',
                    ],
                    'contain' => [
                        'Addresses',
                    ],
                ])
                ->sql()
        );
    }

    public function testHasOneInnerJoinSql(): void
    {
        $this->assertSame(
            'SELECT Users.id AS Users__id FROM users AS Users INNER JOIN addresses AS Addresses ON Addresses.user_id = Users.id',
            $this->modelRegistry->use('Users')
                ->find()
                ->innerJoinWith('Addresses')
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testHasOneInsert(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $this->assertSame(
            1,
            $user->id
        );

        $this->assertSame(
            1,
            $user->address->id
        );

        $this->assertSame(
            1,
            $user->address->user_id
        );

        $this->assertFalse(
            $user->isNew()
        );

        $this->assertFalse(
            $user->address->isNew()
        );

        $this->assertFalse(
            $user->isDirty()
        );

        $this->assertFalse(
            $user->address->isDirty()
        );
    }

    public function testHasOneInsertMany(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'address' => [
                    'suburb' => 'Test 1',
                ],
            ],
            [
                'name' => 'Test 2',
                'address' => [
                    'suburb' => 'Test 2',
                ],
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertSame(
            [1, 2],
            array_map(
                fn($user) => $user->id,
                $users
            )
        );

        $this->assertSame(
            [1, 2],
            array_map(
                fn($user) => $user->address->id,
                $users
            )
        );

        $this->assertSame(
            [1, 2],
            array_map(
                fn($user) => $user->address->user_id,
                $users
            )
        );

        $this->assertFalse(
            $users[0]->isNew()
        );

        $this->assertFalse(
            $users[1]->isNew()
        );

        $this->assertFalse(
            $users[0]->address->isNew()
        );

        $this->assertFalse(
            $users[1]->address->isNew()
        );

        $this->assertFalse(
            $users[0]->isDirty()
        );

        $this->assertFalse(
            $users[1]->isDirty()
        );

        $this->assertFalse(
            $users[0]->address->isDirty()
        );

        $this->assertFalse(
            $users[1]->address->isDirty()
        );
    }

    public function testHasOneLeftJoinSql(): void
    {
        $this->assertSame(
            'SELECT Users.id AS Users__id FROM users AS Users LEFT JOIN addresses AS Addresses ON Addresses.user_id = Users.id',
            $this->modelRegistry->use('Users')
                ->find()
                ->leftJoinWith('Addresses')
                ->disableAutoFields()
                ->sql()
        );
    }

    public function testHasOneStrategyFind(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $user = $Users->get(1, [
            'contain' => [
                'Addresses' => [
                    'strategy' => 'select',
                ],
            ],
        ]);

        $this->assertSame(
            1,
            $user->id
        );

        $this->assertSame(
            1,
            $user->address->id
        );

        $this->assertInstanceOf(
            User::class,
            $user
        );

        $this->assertInstanceOf(
            Address::class,
            $user->address
        );

        $this->assertFalse(
            $user->isNew()
        );

        $this->assertFalse(
            $user->address->isNew()
        );
    }

    public function testHasOneStrategyFindCallback(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $user = $Users->get(1, [
            'contain' => [
                'Addresses' => [
                    'strategy' => 'select',
                    'callback' => fn(SelectQuery $query): SelectQuery => $query->where(['Addresses.id' => 2]),
                ],
            ],
        ]);

        $this->assertSame(
            1,
            $user->id
        );

        $this->assertNull(
            $user->address
        );

        $this->assertInstanceOf(
            User::class,
            $user
        );

        $this->assertFalse(
            $user->isNew()
        );
    }

    public function testHasOneStrategyFindSql(): void
    {
        $this->assertSame(
            'SELECT Users.id AS Users__id FROM users AS Users',
            $this->modelRegistry->use('Users')
                ->find([
                    'fields' => [
                        'Users.id',
                    ],
                    'contain' => [
                        'Addresses' => [
                            'strategy' => 'select',
                        ],
                    ],
                ])
                ->sql()
        );
    }

    public function testHasOneUpdate(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test 1',
            'address' => [
                'suburb' => 'Test 1',
            ],
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $Users->patchEntity($user, [
            'name' => 'Test 2',
            'address' => [
                'suburb' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $this->assertFalse(
            $user->isDirty()
        );

        $this->assertFalse(
            $user->address->isDirty()
        );

        $user = $Users->get(1, [
            'contain' => [
                'Addresses',
            ],
        ]);

        $this->assertSame(
            'Test 2',
            $user->name
        );

        $this->assertSame(
            'Test 2',
            $user->address->suburb
        );
    }

    public function testHasOneUpdateMany(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'address' => [
                    'suburb' => 'Test 1',
                ],
            ],
            [
                'name' => 'Test 2',
                'address' => [
                    'suburb' => 'Test 2',
                ],
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $Users->patchEntities($users, [
            [
                'name' => 'Test 3',
                'address' => [
                    'suburb' => 'Test 3',
                ],
            ],
            [
                'name' => 'Test 4',
                'address' => [
                    'suburb' => 'Test 4',
                ],
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertFalse(
            $users[0]->isDirty()
        );

        $this->assertFalse(
            $users[1]->isDirty()
        );

        $this->assertFalse(
            $users[0]->address->isDirty()
        );

        $this->assertFalse(
            $users[1]->address->isDirty()
        );

        $users = $Users->find([
            'contain' => [
                'Addresses',
            ],
        ])->toArray();

        $this->assertSame(
            'Test 3',
            $users[0]->name
        );

        $this->assertSame(
            'Test 3',
            $users[0]->address->suburb
        );

        $this->assertSame(
            'Test 4',
            $users[1]->name
        );

        $this->assertSame(
            'Test 4',
            $users[1]->address->suburb
        );
    }
}
