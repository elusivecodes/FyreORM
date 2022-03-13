<?php
declare(strict_types=1);

namespace Tests\Model;

use
    Fyre\ORM\ModelRegistry;

use function
    array_map;

trait CallbacksHasOneTest
{

    public function testBeforeSaveHasOne(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'failBeforeSave'
            ]
        ]);

        $this->assertFalse(
            $Users->save($user)
        );

        $this->assertNull(
            $user->id
        );

        $this->assertNull(
            $user->address->id
        );

        $this->assertNull(
            $user->address->user_id
        );

        $this->assertSame(
            0,
            $Users->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('Addresses')->find()->count()
        );
    }

    public function testAfterSaveHasOne(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'failAfterSave'
            ]
        ]);

        $this->assertFalse(
            $Users->save($user)
        );

        $this->assertNull(
            $user->id
        );

        $this->assertNull(
            $user->address->id
        );

        $this->assertNull(
            $user->address->user_id
        );

        $this->assertSame(
            0,
            $Users->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('Addresses')->find()->count()
        );
    }

    public function testBeforeRulesBelongsToHasOne(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'failBeforeRules'
            ]
        ]);

        $this->assertFalse(
            $Users->save($user)
        );

        $this->assertNull(
            $user->id
        );

        $this->assertNull(
            $user->address->id
        );

        $this->assertNull(
            $user->address->user_id
        );

        $this->assertSame(
            0,
            $Users->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('Addresses')->find()->count()
        );
    }

    public function testAfterRulesHasOne(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'failAfterRules'
            ]
        ]);

        $this->assertFalse(
            $Users->save($user)
        );

        $this->assertNull(
            $user->id
        );

        $this->assertNull(
            $user->address->id
        );

        $this->assertNull(
            $user->address->user_id
        );

        $this->assertSame(
            0,
            $Users->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('Addresses')->find()->count()
        );
    }

    public function testBeforeSaveManyHasOne(): void
    {
        $Users = ModelRegistry::use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'address' => [
                    'suburb' => 'Test 1'
                ]
            ],
            [
                'name' => 'Test 2',
                'address' => [
                    'suburb' => 'failBeforeSave'
                ]
            ]
        ]);

        $this->assertFalse(
            $Users->saveMany($users)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($user) => $user->id,
                $users
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($user) => $user->address->id,
                $users
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($user) => $user->address->user_id,
                $users
            )
        );

        $this->assertSame(
            0,
            $Users->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('Addresses')->find()->count()
        );
    }

    public function testAfterSaveManyHasOne(): void
    {
        $Users = ModelRegistry::use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'address' => [
                    'suburb' => 'Test 1'
                ]
            ],
            [
                'name' => 'Test 2',
                'address' => [
                    'suburb' => 'failAfterSave'
                ]
            ]
        ]);

        $this->assertFalse(
            $Users->saveMany($users)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($user) => $user->id,
                $users
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($user) => $user->address->id,
                $users
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($user) => $user->address->user_id,
                $users
            )
        );

        $this->assertSame(
            0,
            $Users->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('Addresses')->find()->count()
        );
    }

    public function testBeforeRulesManyHasOne(): void
    {
        $Users = ModelRegistry::use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'address' => [
                    'suburb' => 'Test 1'
                ]
            ],
            [
                'name' => 'Test 2',
                'address' => [
                    'suburb' => 'failBeforeRules'
                ]
            ]
        ]);

        $this->assertFalse(
            $Users->saveMany($users)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($user) => $user->id,
                $users
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($user) => $user->address->id,
                $users
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($user) => $user->address->user_id,
                $users
            )
        );

        $this->assertSame(
            0,
            $Users->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('Addresses')->find()->count()
        );
    }

    public function testAfterRulesManyHasOne(): void
    {
        $Users = ModelRegistry::use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'address' => [
                    'suburb' => 'Test 1'
                ]
            ],
            [
                'name' => 'Test 2',
                'address' => [
                    'suburb' => 'failAfterRules'
                ]
            ]
        ]);

        $this->assertFalse(
            $Users->saveMany($users)
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($user) => $user->id,
                $users
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($user) => $user->address->id,
                $users
            )
        );

        $this->assertSame(
            [null, null],
            array_map(
                fn($user) => $user->address->user_id,
                $users
            )
        );

        $this->assertSame(
            0,
            $Users->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('Addresses')->find()->count()
        );
    }

    public function testValidationHasOne(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => ''
            ]
        ]);

        $this->assertFalse(
            $Users->save($user)
        );

        $this->assertNull(
            $user->id
        );

        $this->assertNull(
            $user->address->id
        );

        $this->assertNull(
            $user->address->user_id
        );

        $this->assertSame(
            0,
            $Users->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('Addresses')->find()->count()
        );
    }

    public function testValidationNoCheckRulesHasOne(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => ''
            ]
        ]);

        $this->assertFalse(
            $Users->save($user, [
                'checkRules' => false
            ])
        );

        $this->assertNull(
            $user->id
        );

        $this->assertNull(
            $user->address->id
        );

        $this->assertNull(
            $user->address->user_id
        );

        $this->assertSame(
            0,
            $Users->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('Addresses')->find()->count()
        );
    }

    public function testRulesHasOne(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'failRules'
            ]
        ]);

        $this->assertFalse(
            $Users->save($user)
        );

        $this->assertNull(
            $user->id
        );

        $this->assertNull(
            $user->address->id
        );

        $this->assertNull(
            $user->address->user_id
        );

        $this->assertSame(
            0,
            $Users->find()->count()
        );

        $this->assertSame(
            0,
            ModelRegistry::use('Addresses')->find()->count()
        );
    }

    public function testRulesNoCheckRulesHasOne(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'failRules'
            ]
        ]);

        $this->assertTrue(
            $Users->save($user, [
                'checkRules' => false
            ])
        );

        $this->assertSame(
            1,
            $Users->find()->count()
        );

        $this->assertSame(
            1,
            ModelRegistry::use('Addresses')->find()->count()
        );
    }

}
