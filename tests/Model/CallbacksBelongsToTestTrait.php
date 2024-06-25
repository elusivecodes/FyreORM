<?php
declare(strict_types=1);

namespace Tests\Model;

use Fyre\ORM\ModelRegistry;

use function array_map;

trait CallbacksBelongsToTestTrait
{
    public function testAfterDeleteBelongsTo(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'failAfterDelete',
            'user' => [
                'name' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Addresses->save($address)
        );

        $this->assertFalse(
            $Addresses->delete($address)
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

    public function testAfterDeleteManyBelongsTo(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $addresses = $Addresses->newEntities([
            [
                'suburb' => 'Test 1',
                'user' => [
                    'name' => 'Test 1',
                ],
            ],
            [
                'suburb' => 'failAfterDelete',
                'user' => [
                    'name' => 'Test 2',
                ],
            ],
        ]);

        $this->assertTrue(
            $Addresses->saveMany($addresses)
        );

        $this->assertFalse(
            $Addresses->deleteMany($addresses)
        );

        $this->assertSame(
            2,
            $Addresses->find()->count()
        );

        $this->assertSame(
            2,
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testAfterRulesBelongsTo(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'failAfterRules',
            'user' => [
                'name' => 'Test',
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testAfterRulesManyBelongsTo(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $addresses = $Addresses->newEntities([
            [
                'suburb' => 'Test 1',
                'user' => [
                    'name' => 'Test 1',
                ],
            ],
            [
                'suburb' => 'failAfterRules',
                'user' => [
                    'name' => 'Test 2',
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testAfterSaveBelongsTo(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'failAfterSave',
            'user' => [
                'name' => 'Test',
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testAfterSaveManyBelongsTo(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $addresses = $Addresses->newEntities([
            [
                'suburb' => 'Test 1',
                'user' => [
                    'name' => 'Test 1',
                ],
            ],
            [
                'suburb' => 'failAfterSave',
                'user' => [
                    'name' => 'Test 2',
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBeforeDeleteBelongsTo(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'failBeforeDelete',
            'user' => [
                'name' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Addresses->save($address)
        );

        $this->assertFalse(
            $Addresses->delete($address)
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

    public function testBeforeDeleteManyBelongsTo(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $addresses = $Addresses->newEntities([
            [
                'suburb' => 'Test 1',
                'user' => [
                    'name' => 'Test 1',
                ],
            ],
            [
                'suburb' => 'failBeforeDelete',
                'user' => [
                    'name' => 'Test 2',
                ],
            ],
        ]);

        $this->assertTrue(
            $Addresses->saveMany($addresses)
        );

        $this->assertFalse(
            $Addresses->deleteMany($addresses)
        );

        $this->assertSame(
            2,
            $Addresses->find()->count()
        );

        $this->assertSame(
            2,
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBeforeRulesBelongsTo(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'failBeforeRules',
            'user' => [
                'name' => 'Test',
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBeforeRulesManyBelongsTo(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $addresses = $Addresses->newEntities([
            [
                'suburb' => 'Test 1',
                'user' => [
                    'name' => 'Test 1',
                ],
            ],
            [
                'suburb' => 'failBeforeRules',
                'user' => [
                    'name' => 'Test 2',
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBeforeSaveBelongsTo(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'failBeforeSave',
            'user' => [
                'name' => 'Test',
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testBeforeSaveManyBelongsTo(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $addresses = $Addresses->newEntities([
            [
                'suburb' => 'Test 1',
                'user' => [
                    'name' => 'Test 1',
                ],
            ],
            [
                'suburb' => 'failBeforeSave',
                'user' => [
                    'name' => 'Test 2',
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testRulesBelongsTo(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'failRules',
            'user' => [
                'name' => 'Test',
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testRulesNoCheckRulesBelongsTo(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => 'failRules',
            'user' => [
                'name' => 'Test',
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testValidationBelongsTo(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => '',
            'user' => [
                'name' => 'Test',
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
            ModelRegistry::use('Users')->find()->count()
        );
    }

    public function testValidationNoCheckRulesBelongsTo(): void
    {
        $Addresses = ModelRegistry::use('Addresses');

        $address = $Addresses->newEntity([
            'suburb' => '',
            'user' => [
                'name' => 'Test',
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
            ModelRegistry::use('Users')->find()->count()
        );
    }
}
