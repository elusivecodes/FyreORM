<?php
declare(strict_types=1);

namespace Tests\Mysql;

use Fyre\DB\Types\DateTimeType;
use Fyre\ORM\Result;
use Fyre\Utility\Traits\MacroTrait;
use PHPUnit\Framework\TestCase;
use Tests\Mock\Entity\Item;

use function class_uses;
use function json_encode;

final class ResultTest extends TestCase
{
    use MysqlConnectionTrait;

    public function testCollection(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $items = $Items->newEntities([
            [
                'name' => 'Test 1',
            ],
            [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Items->saveMany($items)
        );

        $items = $Items->find()
            ->getResult();

        $this->assertSame(
            [
                1 => 'Test 1',
                2 => 'Test 2',
            ],
            $items->combine('id', 'name')->toArray()
        );
    }

    public function testColumnCount(): void
    {
        $this->assertSame(
            2,
            $this->modelRegistry->use('Items')
                ->find()
                ->getResult()
                ->columnCount()
        );
    }

    public function testColumns(): void
    {
        $this->assertSame(
            [
                'Items__id',
                'Items__name',
            ],
            $this->modelRegistry->use('Items')
                ->find()
                ->getResult()
                ->columns()
        );
    }

    public function testFetch(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $items = $Items->newEntities([
            [
                'name' => 'Test 1',
            ],
            [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Items->saveMany($items)
        );

        $item = $Items->find()
            ->getResult()
            ->fetch(1);

        $this->assertInstanceOf(
            Item::class,
            $item
        );

        $this->assertSame(
            'Items',
            $item->getSource()
        );

        $this->assertSame(
            2,
            $item->id
        );
    }

    public function testFirst(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $items = $Items->newEntities([
            [
                'name' => 'Test 1',
            ],
            [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Items->saveMany($items)
        );

        $item = $Items->find()
            ->getResult()
            ->first();

        $this->assertInstanceOf(
            Item::class,
            $item
        );

        $this->assertSame(
            'Items',
            $item->getSource()
        );

        $this->assertSame(
            1,
            $item->id
        );
    }

    public function testFree(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $items = $Items->newEntities([
            [
                'name' => 'Test 1',
            ],
            [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Items->saveMany($items)
        );

        $result = $Items->find()->getResult();
        $result->free();

        $this->assertSame(
            [],
            $result->toArray()
        );
    }

    public function testJson(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $items = $Items->newEntities([
            [
                'name' => 'Test 1',
            ],
            [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Items->saveMany($items)
        );

        $items = $Items->find()
            ->getResult();

        $this->assertSame(
            '[{"id":1,"name":"Test 1"},{"id":2,"name":"Test 2"}]',
            json_encode($items)
        );
    }

    public function testLast(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $items = $Items->newEntities([
            [
                'name' => 'Test 1',
            ],
            [
                'name' => 'Test 2',
            ],
        ]);

        $this->assertTrue(
            $Items->saveMany($items)
        );

        $item = $Items->find()
            ->getResult()
            ->last();

        $this->assertInstanceOf(
            Item::class,
            $item
        );

        $this->assertSame(
            'Items',
            $item->getSource()
        );

        $this->assertSame(
            2,
            $item->id
        );
    }

    public function testMacroable(): void
    {
        $this->assertContains(
            MacroTrait::class,
            class_uses(Result::class)
        );
    }

    public function testResult(): void
    {
        $this->assertInstanceOf(
            Result::class,
            $this->modelRegistry->use('Items')->find()->getResult()
        );
    }

    public function testType(): void
    {
        $this->assertInstanceOf(
            DateTimeType::class,
            $this->modelRegistry->use('Timestamps')
                ->find([
                    'fields' => [
                        'created' => 'Timestamps.created',
                    ],
                ])
                ->getResult()
                ->getType('created')
        );
    }

    public function testTypeVirtualField(): void
    {
        $this->assertInstanceOf(
            DateTimeType::class,
            $this->modelRegistry->use('Items')
                ->find([
                    'fields' => [
                        'virtual' => 'NOW()',
                    ],
                ])
                ->getResult()
                ->getType('virtual')
        );
    }
}
