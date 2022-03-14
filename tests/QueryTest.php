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
    
    public function testClearResult(): void
    {
        $Test = ModelRegistry::use('Test');

        $test = $Test->newEntity([
            'name' => 'Test'
        ]);

        $this->assertTrue(
            $Test->save($test)
        );

        $query = $Test->find();

        $this->assertInstanceOf(
            Test::class,
            $query->first()
        );

        $query->where([
            'name' => 'Test 2'
        ]);

        $this->assertInstanceOf(
            Test::class,
            $query->first()
        );

        $query->clearResult();

        $this->assertNull(
            $query->first()
        );
    }

}
