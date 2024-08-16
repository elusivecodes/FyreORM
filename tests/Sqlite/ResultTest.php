<?php
declare(strict_types=1);

namespace Tests\Sqlite;

use Fyre\DB\Types\StringType;
use Fyre\ORM\ModelRegistry;
use Fyre\ORM\Result;
use PHPUnit\Framework\TestCase;
use Tests\Mock\Entity\Item;

final class ResultTest extends TestCase
{
    use SqliteConnectionTrait;

    public function testClearBuffer(): void
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

        $result = $Items->find()
            ->getResult();

        $result->fetch(0);
        $result->clearBuffer();

        $this->assertNull(
            $result->fetch(0)
        );

        $this->assertNull(
            $result->fetch(1)
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
            $result->all()
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
            StringType::class,
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
            StringType::class,
            ModelRegistry::use('Items')
                ->find([
                    'fields' => [
                        'virtual' => 'CURRENT_TIMESTAMP',
                    ],
                ])
                ->getResult()
                ->getType('virtual')
        );
    }
}
