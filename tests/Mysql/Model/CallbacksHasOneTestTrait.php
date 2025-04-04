<?php
declare(strict_types=1);

namespace Tests\Mysql\Model;

use function array_map;

trait CallbacksHasOneTestTrait
{
    public function testAfterParseHasOne(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'afterParse',
            ],
        ]);

        $this->assertSame(
            1,
            $user->address->test
        );
    }

    public function testAfterParseHasOneMany(): void
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
                    'suburb' => 'afterParse',
                ],
            ],
        ]);

        $this->assertSame(
            1,
            $users[1]->address->test
        );
    }

    public function testAfterRulesHasOne(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'failAfterRules',
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
            $this->modelRegistry->use('Addresses')->find()->count()
        );
    }

    public function testAfterRulesManyHasOne(): void
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
                    'suburb' => 'failAfterRules',
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
            $this->modelRegistry->use('Addresses')->find()->count()
        );
    }

    public function testAfterSaveHasOne(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'failAfterSave',
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
            $this->modelRegistry->use('Addresses')->find()->count()
        );
    }

    public function testAfterSaveManyHasOne(): void
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
                    'suburb' => 'failAfterSave',
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
            $this->modelRegistry->use('Addresses')->find()->count()
        );
    }

    public function testBeforeParseHasOne(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => '  Test  ',
            ],
        ]);

        $this->assertSame(
            'Test',
            $user->address->suburb
        );
    }

    public function testBeforeParseHasOneMany(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $users = $Users->newEntities([
            [
                'name' => 'Test 1',
                'address' => [
                    'suburb' => '  Test 1  ',
                ],
            ],
            [
                'name' => 'Test 2',
                'address' => [
                    'suburb' => '  Test 2  ',
                ],
            ],
        ]);

        $this->assertSame(
            'Test 1',
            $users[0]->address->suburb
        );

        $this->assertSame(
            'Test 2',
            $users[1]->address->suburb
        );
    }

    public function testBeforeRulesHasOne(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'failBeforeRules',
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
            $this->modelRegistry->use('Addresses')->find()->count()
        );
    }

    public function testBeforeRulesManyHasOne(): void
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
                    'suburb' => 'failBeforeRules',
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
            $this->modelRegistry->use('Addresses')->find()->count()
        );
    }

    public function testBeforeSaveHasOne(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'failBeforeSave',
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
            $this->modelRegistry->use('Addresses')->find()->count()
        );
    }

    public function testBeforeSaveManyHasOne(): void
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
                    'suburb' => 'failBeforeSave',
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
            $this->modelRegistry->use('Addresses')->find()->count()
        );
    }

    public function testRulesHasOne(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'failRules',
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
            $this->modelRegistry->use('Addresses')->find()->count()
        );
    }

    public function testRulesNoCheckRulesHasOne(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => 'failRules',
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
            $this->modelRegistry->use('Addresses')->find()->count()
        );
    }

    public function testValidationHasOne(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => '',
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
            $this->modelRegistry->use('Addresses')->find()->count()
        );
    }

    public function testValidationNoCheckRulesHasOne(): void
    {
        $Users = $this->modelRegistry->use('Users');

        $user = $Users->newEntity([
            'name' => 'Test',
            'address' => [
                'suburb' => '',
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
            $this->modelRegistry->use('Addresses')->find()->count()
        );
    }
}
