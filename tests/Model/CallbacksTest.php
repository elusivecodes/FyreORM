<?php
declare(strict_types=1);

namespace Tests\Model;

use
    Fyre\ORM\ModelRegistry;

trait CallbacksTest
{

    public function testBeforeSave(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => 'failBeforeSave'
        ]);

        $this->assertFalse(
            $Test->save($test)
        );

        $this->assertSame(
            0,
            $Test->find()->count()
        );
    }

    public function testAfterSave(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => 'failAfterSave'
        ]);

        $this->assertFalse(
            $Test->save($test)
        );

        $this->assertSame(
            0,
            $Test->find()->count()
        );
    }

    public function testBeforeRules(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => 'failBeforeRules'
        ]);

        $this->assertFalse(
            $Test->save($test)
        );

        $this->assertSame(
            0,
            $Test->find()->count()
        );
    }

    public function testAfterRules(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => 'failAfterRules'
        ]);

        $this->assertFalse(
            $Test->save($test)
        );

        $this->assertSame(
            0,
            $Test->find()->count()
        );
    }

    public function testBeforeDelete(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => 'failBeforeDelete'
        ]);

        $this->assertTrue(
            $Test->save($test)
        );

        $this->assertFalse(
            $Test->delete($test)
        );

        $this->assertSame(
            1,
            $Test->find()->count()
        );
    }

    public function testAfterDelete(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => 'failAfterDelete'
        ]);

        $this->assertTrue(
            $Test->save($test)
        );

        $this->assertFalse(
            $Test->delete($test)
        );

        $this->assertSame(
            1,
            $Test->find()->count()
        );
    }

    public function testBeforeSaveMany(): void
    {
        $Test = ModelRegistry::use('Test');

        $tests = $Test->newEntities([
            [
                'name' => 'Test'
            ],
            [
                'name' => 'failBeforeSave'
            ]
        ]);

        $this->assertFalse(
            $Test->saveMany($tests)
        );

        $this->assertSame(
            0,
            $Test->find()->count()
        );
    }

    public function testAfterSaveMany(): void
    {
        $Test = ModelRegistry::use('Test');

        $tests = $Test->newEntities([
            [
                'name' => 'Test'
            ],
            [
                'name' => 'failAfterSave'
            ]
        ]);

        $this->assertFalse(
            $Test->saveMany($tests)
        );

        $this->assertSame(
            0,
            $Test->find()->count()
        );
    }

    public function testBeforeRulesMany(): void
    {
        $Test = ModelRegistry::use('Test');

        $tests = $Test->newEntities([
            [
                'name' => 'Test'
            ],
            [
                'name' => 'failBeforeRules'
            ]
        ]);

        $this->assertFalse(
            $Test->saveMany($tests)
        );

        $this->assertSame(
            0,
            $Test->find()->count()
        );
    }

    public function testAfterRulesMany(): void
    {
        $Test = ModelRegistry::use('Test');

        $tests = $Test->newEntities([
            [
                'name' => 'Test'
            ],
            [
                'name' => 'failAfterRules'
            ]
        ]);

        $this->assertFalse(
            $Test->saveMany($tests)
        );

        $this->assertSame(
            0,
            $Test->find()->count()
        );
    }

    public function testBeforeDeleteMany(): void
    {
        $Test = ModelRegistry::use('Test');

        $tests = $Test->newEntities([
            [
                'name' => 'Test'
            ],
            [
                'name' => 'failBeforeDelete'
            ]
        ]);

        $this->assertTrue(
            $Test->saveMany($tests)
        );

        $this->assertFalse(
            $Test->deleteMany($tests)
        );

        $this->assertSame(
            2,
            $Test->find()->count()
        );
    }

    public function testAfterDeleteMany(): void
    {
        $Test = ModelRegistry::use('Test');

        $tests = $Test->newEntities([
            [
                'name' => 'Test'
            ],
            [
                'name' => 'failAfterDelete'
            ]
        ]);

        $this->assertTrue(
            $Test->saveMany($tests)
        );

        $this->assertFalse(
            $Test->deleteMany($tests)
        );

        $this->assertSame(
            2,
            $Test->find()->count()
        );
    }

    public function testValidation(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => ''
        ]);

        $this->assertFalse(
            $Test->save($test)
        );

        $this->assertSame(
            0,
            $Test->find()->count()
        );
    }

    public function testValidationNoCheckRules(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => ''
        ]);

        $this->assertFalse(
            $Test->save($test, [
                'checkRules' => false
            ])
        );

        $this->assertSame(
            0,
            $Test->find()->count()
        );
    }

    public function testRules(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => 'failRules'
        ]);

        $this->assertFalse(
            $Test->save($test)
        );

        $this->assertSame(
            0,
            $Test->find()->count()
        );
    }

    public function testRulesNoCheckRules(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => 'failRules'
        ]);

        $this->assertTrue(
            $Test->save($test, [
                'checkRules' => false
            ])
        );

        $this->assertSame(
            1,
            $Test->find()->count()
        );
    }

}
