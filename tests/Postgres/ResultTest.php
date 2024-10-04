<?php
declare(strict_types=1);

namespace Tests\Postgres;

use Fyre\DB\Types\DateTimeType;
use Fyre\ORM\ModelRegistry;
use Fyre\ORM\Result;
use PHPUnit\Framework\TestCase;
use Tests\Mock\Entity\Item;

use function json_encode;

final class ResultTest extends TestCase
{
    use PostgresConnectionTrait;

    public function testCollection(): void
    {
        $Items = ModelRegistry::use('Items');

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
            ModelRegistry::use('Items')
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
            ModelRegistry::use('Items')
                ->find()
                ->getResult()
                ->columns()
        );
    }

    public function testFetch(): void
    {
        $Items = ModelRegistry::use('Items');

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
        $Items = ModelRegistry::use('Items');

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
        $Items = ModelRegistry::use('Items');

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
        $Items = ModelRegistry::use('Items');

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
        $Items = ModelRegistry::use('Items');

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

    public function testResult(): void
    {
        $this->assertInstanceOf(
            Result::class,
            ModelRegistry::use('Items')->find()->getResult()
        );
    }

    public function testType(): void
    {
        $this->assertInstanceOf(
            DateTimeType::class,
            ModelRegistry::use('Timestamps')
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
            ModelRegistry::use('Items')
                ->find([
                    'fields' => [
                        'virtual' => 'LOCALTIMESTAMP(0)',
                    ],
                ])
                ->getResult()
                ->getType('virtual')
        );
    }
}
