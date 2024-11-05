<?php
declare(strict_types=1);

namespace Tests\Mysql\Model;

trait CallbacksTestTrait
{
    public function testAfterDelete(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $item = $Items->newEntity([
            'name' => 'failAfterDelete',
        ]);

        $this->assertTrue(
            $Items->save($item)
        );

        $this->assertFalse(
            $Items->delete($item)
        );

        $this->assertSame(
            1,
            $Items->find()->count()
        );
    }

    public function testAfterDeleteMany(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $items = $Items->newEntities([
            [
                'name' => 'Test',
            ],
            [
                'name' => 'failAfterDelete',
            ],
        ]);

        $this->assertTrue(
            $Items->saveMany($items)
        );

        $this->assertFalse(
            $Items->deleteMany($items)
        );

        $this->assertSame(
            2,
            $Items->find()->count()
        );
    }

    public function testAfterFind(): void
    {
        $Others = $this->modelRegistry->use('Others');

        $other = $Others->newEntity([
            'value' => 1,
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

    public function testAfterParse(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $item = $Items->newEntity([
            'name' => 'afterParse',
        ]);

        $this->assertSame(
            1,
            $item->test
        );
    }

    public function testAfterParseMany(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $items = $Items->newEntities([
            [
                'name' => 'afterParse',
            ],
            [
                'name' => 'afterParse',
            ],
        ]);

        $this->assertSame(
            1,
            $items[0]->test
        );

        $this->assertSame(
            1,
            $items[1]->test
        );
    }

    public function testAfterRules(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $item = $Items->newEntity([
            'name' => 'failAfterRules',
        ]);

        $this->assertFalse(
            $Items->save($item)
        );

        $this->assertSame(
            0,
            $Items->find()->count()
        );
    }

    public function testAfterRulesMany(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $items = $Items->newEntities([
            [
                'name' => 'Test',
            ],
            [
                'name' => 'failAfterRules',
            ],
        ]);

        $this->assertFalse(
            $Items->saveMany($items)
        );

        $this->assertSame(
            0,
            $Items->find()->count()
        );
    }

    public function testAfterSave(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $item = $Items->newEntity([
            'name' => 'failAfterSave',
        ]);

        $this->assertFalse(
            $Items->save($item)
        );

        $this->assertSame(
            0,
            $Items->find()->count()
        );
    }

    public function testAfterSaveMany(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $items = $Items->newEntities([
            [
                'name' => 'Test',
            ],
            [
                'name' => 'failAfterSave',
            ],
        ]);

        $this->assertFalse(
            $Items->saveMany($items)
        );

        $this->assertSame(
            0,
            $Items->find()->count()
        );
    }

    public function testBeforeDelete(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $item = $Items->newEntity([
            'name' => 'failBeforeDelete',
        ]);

        $this->assertTrue(
            $Items->save($item)
        );

        $this->assertFalse(
            $Items->delete($item)
        );

        $this->assertSame(
            1,
            $Items->find()->count()
        );
    }

    public function testBeforeDeleteMany(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $items = $Items->newEntities([
            [
                'name' => 'Test',
            ],
            [
                'name' => 'failBeforeDelete',
            ],
        ]);

        $this->assertTrue(
            $Items->saveMany($items)
        );

        $this->assertFalse(
            $Items->deleteMany($items)
        );

        $this->assertSame(
            2,
            $Items->find()->count()
        );
    }

    public function testBeforeFind(): void
    {
        $Others = $this->modelRegistry->use('Others');

        $others = $Others->newEntities([
            [
                'value' => 1,
            ],
            [
                'value' => 2,
            ],
        ]);

        $this->assertTrue(
            $Others->saveMany($others)
        );

        $this->assertSame(
            1,
            $Others->find()->count()
        );
    }

    public function testBeforeParse(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $item = $Items->newEntity([
            'name' => '  Test  ',
        ]);

        $this->assertSame(
            'Test',
            $item->name
        );
    }

    public function testBeforeParseMany(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $items = $Items->newEntities([
            [
                'name' => '   Test 1   ',
            ],
            [
                'name' => '   Test 2   ',
            ],
        ]);

        $this->assertSame(
            'Test 1',
            $items[0]->name
        );

        $this->assertSame(
            'Test 2',
            $items[1]->name
        );
    }

    public function testBeforeRules(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $item = $Items->newEntity([
            'name' => 'failBeforeRules',
        ]);

        $this->assertFalse(
            $Items->save($item)
        );

        $this->assertSame(
            0,
            $Items->find()->count()
        );
    }

    public function testBeforeRulesMany(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $items = $Items->newEntities([
            [
                'name' => 'Test',
            ],
            [
                'name' => 'failBeforeRules',
            ],
        ]);

        $this->assertFalse(
            $Items->saveMany($items)
        );

        $this->assertSame(
            0,
            $Items->find()->count()
        );
    }

    public function testBeforeSave(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $item = $Items->newEntity([
            'name' => 'failBeforeSave',
        ]);

        $this->assertFalse(
            $Items->save($item)
        );

        $this->assertSame(
            0,
            $Items->find()->count()
        );
    }

    public function testBeforeSaveMany(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $items = $Items->newEntities([
            [
                'name' => 'Test',
            ],
            [
                'name' => 'failBeforeSave',
            ],
        ]);

        $this->assertFalse(
            $Items->saveMany($items)
        );

        $this->assertSame(
            0,
            $Items->find()->count()
        );
    }

    public function testRules(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $item = $Items->newEntity([
            'name' => 'failRules',
        ]);

        $this->assertFalse(
            $Items->save($item)
        );

        $this->assertSame(
            0,
            $Items->find()->count()
        );
    }

    public function testRulesNoCheckRules(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $item = $Items->newEntity([
            'name' => 'failRules',
        ]);

        $this->assertTrue(
            $Items->save($item, [
                'checkRules' => false,
            ])
        );

        $this->assertSame(
            1,
            $Items->find()->count()
        );
    }

    public function testValidation(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $item = $Items->newEntity([
            'name' => '',
        ]);

        $this->assertFalse(
            $Items->save($item)
        );

        $this->assertSame(
            0,
            $Items->find()->count()
        );
    }

    public function testValidationNoCheckRules(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $item = $Items->newEntity([
            'name' => '',
        ]);

        $this->assertFalse(
            $Items->save($item, [
                'checkRules' => false,
            ])
        );

        $this->assertSame(
            0,
            $Items->find()->count()
        );
    }
}
