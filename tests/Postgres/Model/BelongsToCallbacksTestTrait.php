<?php
declare(strict_types=1);

namespace Tests\Postgres\Model;

use function array_map;

trait BelongsToCallbacksTestTrait
{
    public function testBelongsToAfterParse(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'afterParse',
            ],
        ]);

        $this->assertSame(
            1,
            $address->user->test
        );
    }

    public function testBelongsToAfterParseMany(): void
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
                    'name' => 'afterParse',
                ],
            ],
        ]);

        $this->assertSame(
            1,
            $addresses[1]->user->test
        );
    }

    public function testBelongsToAfterRules(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'failAfterRules',
            ],
        ]);

        $this->assertFalse(
            $Addresses->save($address)
        );

        $this->assertNull(
            $address->id
        );

        $this->assertNull(
            $address->user->id
        );

        $this->assertNull(
            $address->user_id
        );

        $this->assertSame(
            0,
            $Addresses->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Users')->find()->count()
        );
    }

    public function testBelongsToAfterRulesMany(): void
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
                    'name' => 'failAfterRules',
                ],
            ],
        ]);

        $this->assertFalse(
            $Addresses->saveMany($addresses)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($address) => $address->id,
                $addresses
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($address) => $address->user->id,
                $addresses
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($address) => $address->user_id,
                $addresses
            )
        );

        $this->assertSame(
            0,
            $Addresses->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Users')->find()->count()
        );
    }

    public function testBelongsToAfterSave(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'failAfterSave',
            ],
        ]);

        $this->assertFalse(
            $Addresses->save($address)
        );

        $this->assertNull(
            $address->id
        );

        $this->assertNull(
            $address->user->id
        );

        $this->assertNull(
            $address->user_id
        );

        $this->assertSame(
            0,
            $Addresses->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Users')->find()->count()
        );
    }

    public function testBelongsToAfterSaveMany(): void
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
                    'name' => 'failAfterSave',
                ],
            ],
        ]);

        $this->assertFalse(
            $Addresses->saveMany($addresses)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($address) => $address->id,
                $addresses
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($address) => $address->user->id,
                $addresses
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($address) => $address->user_id,
                $addresses
            )
        );

        $this->assertSame(
            0,
            $Addresses->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Users')->find()->count()
        );
    }

    public function testBelongsToBeforeParse(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => '  Test  ',
            ],
        ]);

        $this->assertSame(
            'Test',
            $address->user->name
        );
    }

    public function testBelongsToBeforeParseMany(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $addresses = $Addresses->newEntities([
            [
                'suburb' => 'Test 1',
                'user' => [
                    'name' => '  Test 1  ',
                ],
            ],
            [
                'suburb' => 'Test 2',
                'user' => [
                    'name' => '  Test 2  ',
                ],
            ],
        ]);

        $this->assertSame(
            'Test 1',
            $addresses[0]->user->name
        );

        $this->assertSame(
            'Test 2',
            $addresses[1]->user->name
        );
    }

    public function testBelongsToBeforeRules(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'failBeforeRules',
            ],
        ]);

        $this->assertFalse(
            $Addresses->save($address)
        );

        $this->assertNull(
            $address->id
        );

        $this->assertNull(
            $address->user->id
        );

        $this->assertNull(
            $address->user_id
        );

        $this->assertSame(
            0,
            $Addresses->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Users')->find()->count()
        );
    }

    public function testBelongsToBeforeRulesMany(): void
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
                    'name' => 'failBeforeRules',
                ],
            ],
        ]);

        $this->assertFalse(
            $Addresses->saveMany($addresses)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($address) => $address->id,
                $addresses
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($address) => $address->user->id,
                $addresses
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($address) => $address->user_id,
                $addresses
            )
        );

        $this->assertSame(
            0,
            $Addresses->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Users')->find()->count()
        );
    }

    public function testBelongsToBeforeSave(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'failBeforeSave',
            ],
        ]);

        $this->assertFalse(
            $Addresses->save($address)
        );

        $this->assertNull(
            $address->id
        );

        $this->assertNull(
            $address->user->id
        );

        $this->assertNull(
            $address->user_id
        );

        $this->assertSame(
            0,
            $Addresses->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Users')->find()->count()
        );
    }

    public function testBelongsToBeforeSaveMany(): void
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
                    'name' => 'failBeforeSave',
                ],
            ],
        ]);

        $this->assertFalse(
            $Addresses->saveMany($addresses)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($address) => $address->id,
                $addresses
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($address) => $address->user->id,
                $addresses
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($address) => $address->user_id,
                $addresses
            )
        );

        $this->assertSame(
            0,
            $Addresses->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Users')->find()->count()
        );
    }

    public function testBelongsToRules(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'failRules',
            ],
        ]);

        $this->assertFalse(
            $Addresses->save($address)
        );

        $this->assertNull(
            $address->id
        );

        $this->assertNull(
            $address->user->id
        );

        $this->assertNull(
            $address->user_id
        );

        $this->assertSame(
            0,
            $Addresses->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Users')->find()->count()
        );
    }

    public function testBelongsToRulesNoCheckRules(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'failRules',
            ],
        ]);

        $this->assertTrue(
            $Addresses->save($address, [
                'checkRules' => false,
            ])
        );

        $this->assertSame(
            1,
            $Addresses->find()->count()
        );

        $this->assertSame(
            1,
            $this->modelRegistry->use('Users')->find()->count()
        );
    }

    public function testBelongsToValidation(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => '',
            ],
        ]);

        $this->assertFalse(
            $Addresses->save($address)
        );

        $this->assertNull(
            $address->id
        );

        $this->assertNull(
            $address->user->id
        );

        $this->assertNull(
            $address->user_id
        );

        $this->assertSame(
            0,
            $Addresses->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Users')->find()->count()
        );
    }

    public function testBelongsToValidationNoCheckRules(): void
    {
        $Addresses = $this->modelRegistry->use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => '',
            ],
        ]);

        $this->assertFalse(
            $Addresses->save($address, [
                'checkRules' => false,
            ])
        );

        $this->assertNull(
            $address->id
        );

        $this->assertNull(
            $address->user->id
        );

        $this->assertNull(
            $address->user_id
        );

        $this->assertSame(
            0,
            $Addresses->find()->count()
        );

        $this->assertSame(
            0,
            $this->modelRegistry->use('Users')->find()->count()
        );
    }
}
