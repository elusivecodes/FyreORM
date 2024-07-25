<?php
declare(strict_types=1);

namespace Tests\Mysql\Model;

use Fyre\ORM\ModelRegistry;

use function array_map;

trait HasOneCallbacksTestTrait
{
    public function testHasOneAfterDelete(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'failAfterDelete',
            'address' => [
                'suburb' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $this->assertFalse(
            $Users->delete($user)
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

    public function testHasOneAfterDeleteMany(): void
    {
        $Users = ModelRegistry::use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'address' => [
                    'suburb' => 'Test 1',
                ],
            ],
            [
                'name' => 'failAfterDelete',
                'address' => [
                    'suburb' => 'Test 2',
                ],
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertFalse(
            $Users->deleteMany($users)
        );

        $this->assertSame(
            2,
            $Users->find()->count()
        );

        $this->assertSame(
            2,
            ModelRegistry::use('Addresses')->find()->count()
        );
    }

    public function testHasOneAfterRules(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'failAfterRules',
            'address' => [
                'suburb' => 'Test',
            ],
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

    public function testHasOneAfterRulesMany(): void
    {
        $Users = ModelRegistry::use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'address' => [
                    'suburb' => 'Test 1',
                ],
            ],
            [
                'name' => 'failAfterRules',
                'address' => [
                    'suburb' => 'Test 2',
                ],
            ],
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

    public function testHasOneAfterSave(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'failAfterSave',
            'address' => [
                'suburb' => 'Test',
            ],
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

    public function testHasOneAfterSaveMany(): void
    {
        $Users = ModelRegistry::use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'address' => [
                    'suburb' => 'Test 1',
                ],
            ],
            [
                'name' => 'failAfterSave',
                'address' => [
                    'suburb' => 'Test 2',
                ],
            ],
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

    public function testHasOneBeforeDelete(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'failBeforeDelete',
            'address' => [
                'suburb' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Users->save($user)
        );

        $this->assertFalse(
            $Users->delete($user)
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

    public function testHasOneBeforeDeleteMany(): void
    {
        $Users = ModelRegistry::use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'address' => [
                    'suburb' => 'Test 1',
                ],
            ],
            [
                'name' => 'failBeforeDelete',
                'address' => [
                    'suburb' => 'Test 2',
                ],
            ],
        ]);

        $this->assertTrue(
            $Users->saveMany($users)
        );

        $this->assertFalse(
            $Users->deleteMany($users)
        );

        $this->assertSame(
            2,
            $Users->find()->count()
        );

        $this->assertSame(
            2,
            ModelRegistry::use('Addresses')->find()->count()
        );
    }

    public function testHasOneBeforeRules(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'failBeforeRules',
            'address' => [
                'suburb' => 'Test',
            ],
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

    public function testHasOneBeforeRulesMany(): void
    {
        $Users = ModelRegistry::use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'address' => [
                    'suburb' => 'Test 1',
                ],
            ],
            [
                'name' => 'failBeforeRules',
                'address' => [
                    'suburb' => 'Test 2',
                ],
            ],
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

    public function testHasOneBeforeSave(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'failBeforeSave',
            'address' => [
                'suburb' => 'Test',
            ],
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

    public function testHasOneBeforeSaveMany(): void
    {
        $Users = ModelRegistry::use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'address' => [
                    'suburb' => 'Test 1',
                ],
            ],
            [
                'name' => 'failBeforeSave',
                'address' => [
                    'suburb' => 'Test 2',
                ],
            ],
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

    public function testHasOneRules(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'failRules',
            'address' => [
                'suburb' => 'Test',
            ],
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

    public function testHasOneRulesNoCheckRules(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => 'failRules',
            'address' => [
                'suburb' => 'Test',
            ],
        ]);

        $this->assertTrue(
            $Users->save($user, [
                'checkRules' => false,
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

    public function testHasOneValidation(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => '',
            'address' => [
                'suburb' => 'Test',
            ],
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

    public function testHasOneValidationNoCheckRules(): void
    {
        $Users = ModelRegistry::use('Users');

        $user = $Users->newEntity([
            'name' => '',
            'address' => [
                'suburb' => 'Test',
            ],
        ]);

        $this->assertFalse(
            $Users->save($user, [
                'checkRules' => false,
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
}
