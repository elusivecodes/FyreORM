<?php
declare(strict_types=1);

namespace Tests\Model;

use
    Fyre\ORM\ModelRegistry;

use function
    array_map;

trait BelongsToCallbacksTest
{

    public function testBelongsToBeforeSave(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'failBeforeSave'
            ]
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBelongsToAfterSave(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'failAfterSave'
            ]
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBelongsToBeforeRules(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'failBeforeRules'
            ]
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBelongsToAfterRules(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'failAfterRules'
            ]
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBelongsToBeforeParse(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => '  Test  '
            ]
        ]);

        $this->assertSame(
            'Test',
            $address->user->name
        );
    }

    public function testBelongsToAfterParse(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'afterParse'
            ]
        ]);

        $this->assertSame(
            1,
            $address->user->test
        );
    }

    public function testBelongsToBeforeSaveMany(): void
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
                    'name' => 'failBeforeSave'
                ]
            ]
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBelongsToAfterSaveMany(): void
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
                    'name' => 'failAfterSave'
                ]
            ]
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBelongsToBeforeRulesMany(): void
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
                    'name' => 'failBeforeRules'
                ]
            ]
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBelongsToAfterRulesMany(): void
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
                    'name' => 'failAfterRules'
                ]
            ]
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBelongsToBeforeParseMany(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $addresses = $Addresses->newEntities([
            [
                'suburb' => 'Test 1',
                'user' => [
                    'name' => '  Test 1  '
                ]
            ],
            [
                'suburb' => 'Test 2',
                'user' => [
                    'name' => '  Test 2  '
                ]
            ]
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

    public function testBelongsToAfterParseMany(): void
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
                    'name' => 'afterParse'
                ]
            ]
        ]);

        $this->assertSame(
            1,
            $addresses[1]->user->test
        );
    }

    public function testBelongsToValidation(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => ''
            ]
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBelongsToValidationNoCheckRules(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => ''
            ]
        ]);

        $this->assertFalse(
            $Addresses->save($address, [
                'checkRules' => false
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBelongsToRules(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'failRules'
            ]
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBelongsToRulesNoCheckRules(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'Test',
            'user' => [
                'name' => 'failRules'
            ]
        ]);

        $this->assertTrue(
            $Addresses->save($address, [
                'checkRules' => false
            ])
        );

        $this->assertSame(
            1,
            $Addresses->find()->count()
        );

        $this->assertSame(
            1,
            ModelRegistry::use('Users')->find()->count()
        );
    }

}
