<?php
declare(strict_types=1);

namespace Tests\Postgres;

use Fyre\ORM\ModelRegistry;
use Fyre\ORM\Queries\SelectQuery;
use PHPUnit\Framework\TestCase;
use Tests\Mock\Entity\Item;

final class QueryTest extends TestCase
{
    use PostgresConnectionTrait;

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
            2,
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
