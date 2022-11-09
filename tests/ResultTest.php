<?php
declare(strict_types=1);

namespace Tests;

use
    Fyre\DB\Types\DateTimeType,
    Fyre\DB\Types\StringType,
    Fyre\ORM\ModelRegistry,
    Fyre\ORM\Result,
    PHPUnit\Framework\TestCase,
    Tests\Mock\Entity\Test;

final class ResultTest extends TestCase
{

    use
        ConnectionTrait;

    public function testResult(): void
    {
        $this->assertInstanceOf(
            Result::class,
            ModelRegistry::use('Test')->find()->getResult()
        );
    }

    public function testFetch(): void
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

        $test = $Test->find()
            ->getResult()
            ->fetch(1);

        $this->assertInstanceOf(
            Test::class,
            $test
        );

        $this->assertSame(
            'Test',
            $test->getSource()
        );

        $this->assertSame(
            2,
            $test->id
        );
    }

    public function testFirst(): void
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

        $test = $Test->find()
            ->getResult()
            ->first();

        $this->assertInstanceOf(
            Test::class,
            $test
        );

        $this->assertSame(
            'Test',
            $test->getSource()
        );

        $this->assertSame(
            1,
            $test->id
        );
    }

    public function testLast(): void
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

        $test = $Test->find()
            ->getResult()
            ->last();

        $this->assertInstanceOf(
            Test::class,
            $test
        );

        $this->assertSame(
            'Test',
            $test->getSource()
        );

        $this->assertSame(
            2,
            $test->id
        );
    }

    public function testColumnCount(): void
    {
        $this->assertSame(
            2,
            ModelRegistry::use('Test')
                ->find()
                ->getResult()
                ->columnCount()
        );
    }

    public function testColumns(): void
    {
        $this->assertSame(
            [
                'Test__id',
                'Test__name'
            ],
            ModelRegistry::use('Test')
                ->find()
                ->getResult()
                ->columns()
        );
    }

    public function testType(): void
    {
        $this->assertInstanceOf(
            StringType::class,
            ModelRegistry::use('Test')
                ->find([
                    'fields' => [
                        'name' => 'Test.name'
                    ]
                ])
                ->getResult()
                ->getType('name')
        );
    }


    public function testTypeVirtualField(): void
    {
        $this->assertInstanceOf(
            DateTimeType::class,
            ModelRegistry::use('Test')
                ->find([
                    'fields' => [
                        'virtual' => 'NOW()'
                    ]
                ])
                ->getResult()
                ->getType('virtual')
        );
    }

    public function testFree(): void
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

        $result = $Test->find()->getResult();
        $result->free();

        $this->assertSame(
            [],
            $result->all()
        );
    }

}
