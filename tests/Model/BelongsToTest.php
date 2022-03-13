<?php
declare(strict_types=1);

namespace Tests\Model;

use
    Fyre\ORM\ModelRegistry,
    Tests\Mock\Entity\Address,
    Tests\Mock\Entity\User;

trait BelongsToTest
{

    public function testBelongsToInsert(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'Test'
            ]
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
        $Addresses = ModelRegistry::use('Addresses');

        $addresses = [
            $Addresses->newEntity([
                'suburb' => 'Test 1',
                'user' => [
                    'name' => 'Test 1'
                ]
            ]),
            $Addresses->newEntity([
                'suburb' => 'Test 2',
                'user' => [
                    'name' => 'Test 2'
                ]
            ])
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

    public function testBelongsToUpdate(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'Test'
            ]
        ]);

        $this->assertTrue(
            $Addresses->save($address)
        );

        $Addresses->patchEntity($address, [
            'suburb' => 'Test 2',
            'user' => [
                'name' => 'Test 2'
            ]
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
                'Users'
            ]
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
        $Addresses = ModelRegistry::use('Addresses');

        $addresses = $Addresses->newEntities([
            [
                'suburb' => 'Test 1',
                'user' => [
                    'name' => 'Test 1'
                ]
            ],
            [
                'suburb' => 'Test 2',
                'user' => [
                    'name' => 'Test 2'
                ]
            ]
        ]);

        $this->assertTrue(
            $Addresses->saveMany($addresses)
        );

        $Addresses->patchEntities($addresses, [
            [
                'suburb' => 'Test 3',
                'user' => [
                    'name' => 'Test 3'
                ]
            ],
            [
                'suburb' => 'Test 4',
                'user' => [
                    'name' => 'Test 4'
                ]
            ]
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
                'Users'
            ]
        ])->all();

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

    public function testBelongsToDelete(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'Test'
            ]
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBelongsToDeleteMany(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $addresses = $Addresses->newEntities([
            [
                'suburb' => 'Test 1',
                'user' => [
                    'name' => 'Test 1'
                ]
            ],
            [
                'suburb' => 'Test 2',
                'user' => [
                    'name' => 'Test 2'
                ]
            ]
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBelongsToFind(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'Test'
            ]
        ]);

        $this->assertTrue(
            $Addresses->save($address)
        );

        $address = $Addresses->get(1, [
            'contain' => [
                'Users'
            ]
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

    public function testBelongsToFindSql(): void
    {
        $this->assertSame(
            'SELECT Addresses.id AS Addresses__id, Users.id AS Users__id FROM addresses AS Addresses LEFT JOIN users AS Users ON Users.id = Addresses.user_id',
            ModelRegistry::use('Addresses')
                ->find([
                    'contain' => [
                        'Users'
                    ]
                ])
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testBelongsToLeftJoinSql(): void
    {
        $this->assertSame(
            'SELECT Addresses.id AS Addresses__id FROM addresses AS Addresses LEFT JOIN users AS Users ON Users.id = Addresses.user_id',
            ModelRegistry::use('Addresses')
                ->find()
                ->leftJoinWith('Users')
                ->enableAutoFields(false)
                ->sql()
        );
    }

    public function testBelongsToInnerJoinSql(): void
    {
        $this->assertSame(
            'SELECT Addresses.id AS Addresses__id FROM addresses AS Addresses INNER JOIN users AS Users ON Users.id = Addresses.user_id',
            ModelRegistry::use('Addresses')
                ->find()
                ->innerJoinWith('Users')
                ->enableAutoFields(false)
                ->sql()
        );
    }

}
