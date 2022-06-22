<?php
declare(strict_types=1);

namespace Tests\Model;

use
    Fyre\ORM\ModelRegistry;

trait CallbacksTest
{

    public function testBeforeFind(): void
    {
        $Others = ModelRegistry::use('Others');

        $others = $Others->newEntities([
            [
                'value' => 1
            ],
            [
                'value' => 2
            ]
        ]);

        $this->assertTrue(
            $Others->saveMany($others)
        );

        $this->assertSame(
            1,
            $Others->find()->count()
        );
    }

    public function testAfterFind(): void
    {
        $Others = ModelRegistry::use('Others');

        $other = $Others->newEntity([
            'value' => 1
        ]);

        $this->assertTrue(
            $Others->save($other)
        );

        $other = $Others->find()->first();

        $this->assertSame(
            'Test',
            $other->test
        );
    }

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

    public function testBeforeParse(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => '  Test  '
        ]);

        $this->assertSame(
            'Test',
            $test->name
        );
    }

    public function testAfterParse(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => 'afterParse'
        ]);

        $this->assertSame(
            1,
            $test->test
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

    public function testBeforeParseMany(): void
    {
        $Test = ModelRegistry::use('Test');

        $tests = $Test->newEntities([
            [
                'name' => '   Test 1   '   
            ],
            [
                'name' => '   Test 2   '
            ]
        ]);

        $this->assertSame(
            'Test 1',
            $tests[0]->name
        );

        $this->assertSame(
            'Test 2',
            $tests[1]->name
        );
    }

    public function testAfterParseMany(): void
    {
        $Test = ModelRegistry::use('Test');

        $tests = $Test->newEntities([
            [
                'name' => 'afterParse'   
            ],
            [
                'name' => 'afterParse'
            ]
        ]);

        $this->assertSame(
            1,
            $tests[0]->test
        );

        $this->assertSame(
            1,
            $tests[1]->test
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
