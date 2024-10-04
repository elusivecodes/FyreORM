<?php
declare(strict_types=1);

namespace Tests\Mysql;

use Fyre\Entity\Entity;
use Fyre\ORM\ModelRegistry;
use Fyre\ORM\Queries\SelectQuery;
use PHPUnit\Framework\TestCase;
use Tests\Mock\Entity\Item;

final class QueryTest extends TestCase
{
    use MysqlConnectionTrait;

    public function testBuffering(): void
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
            ->disableAutoFields()
            ->all();

        $items->toArray();

        $this->assertSame(
            [
                [
                    'id' => 1,
                ],
                [
                    'id' => 2,
                ],
            ],
            $items->map(fn(Entity $item): array => $item->toArray())->toArray()
        );
    }

    public function testBufferingDisabled(): void
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
            ->disableAutoFields()
            ->disableBuffering()
            ->all();

        $items->toArray();

        $this->assertSame(
            [],
            $items->toArray()
        );
    }

    public function testCount(): void
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

        $this->assertSame(
            2,
            $Items->find()
                ->count()
        );
    }

    public function testCountWithLimit(): void
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

        $this->assertSame(
            1,
            $Items->find()
                ->limit(1)
                ->count()
        );
    }

    public function testDirty(): void
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

        $query = $Items->find();

        $result1 = $query->first();

        $this->assertInstanceOf(
            Item::class,
            $result1
        );

        $this->assertSame(
            'Items',
            $result1->getSource()
        );

        $this->assertSame(
            'Test 1',
            $result1->name
        );

        $query->where([
            'name' => 'Test 2',
        ]);

        $result2 = $query->first();

        $this->assertInstanceOf(
            Item::class,
            $result2
        );

        $this->assertSame(
            'Items',
            $result2->getSource()
        );

        $this->assertSame(
            'Test 2',
            $result2->name
        );
    }

    public function testQuery(): void
    {
        $this->assertInstanceOf(
            SelectQuery::class,
            ModelRegistry::use('Items')->find()
        );
    }
}
