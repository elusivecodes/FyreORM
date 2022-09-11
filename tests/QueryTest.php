<?php
declare(strict_types=1);

namespace Tests;

use
    Fyre\ORM\ModelRegistry,
    Fyre\ORM\Query,
    PHPUnit\Framework\TestCase,
    Tests\Mock\Entity\Test;

final class QueryTest extends TestCase
{

    use
        ConnectionTrait;

    public function testQuery(): void
    {
        $this->assertInstanceOf(
            Query::class,
            ModelRegistry::use('Test')->find()
        );
    }

    public function testCount(): void
    {
        $Test = ModelRegistry::use('Test');

        $tests = $Test->newEntities([
            [
                'name' => 'Test 1'
            ],
            [
                'name' => 'Test 2'
            ]
        ]);

        $this->assertTrue(
            $Test->saveMany($tests)
        );

        $this->assertSame(
            2,
            $Test->find()
                ->count()
        );
    }

    public function testCountWithLimit(): void
    {
        $Test = ModelRegistry::use('Test');

        $tests = $Test->newEntities([
            [
                'name' => 'Test 1'
            ],
            [
                'name' => 'Test 2'
            ]
        ]);

        $this->assertTrue(
            $Test->saveMany($tests)
        );

        $this->assertSame(
            2,
            $Test->find()
                ->limit(1)
                ->count()
        );
    }

    public function testDirty(): void
    {
        $Test = ModelRegistry::use('Test');

        $tests = $Test->newEntities([
            [
                'name' => 'Test 1'
            ],
            [
                'name' => 'Test 2'
            ]
        ]);

        $this->assertTrue(
            $Test->saveMany($tests)
        );

        $query = $Test->find();

        $result1 = $query->first();

        $this->assertInstanceOf(
            Test::class,
            $result1
        );

        $this->assertSame(
            'Test 1',
            $result1->name
        );

        $query->where([
            'name' => 'Test 2'
        ]);

        $result2 = $query->first();

        $this->assertInstanceOf(
            Test::class,
            $result2
        );

        $this->assertSame(
            'Test 2',
            $result2->name
        );
    }

}
