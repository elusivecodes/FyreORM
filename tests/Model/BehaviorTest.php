<?php
declare(strict_types=1);

namespace Tests\Model;

use
    Fyre\ORM\Behavior,
    Fyre\ORM\Exceptions\OrmException,
    Fyre\ORM\ModelRegistry;

trait BehaviorTest
{

    public function testAddBehavior(): void
    {
        $Test = ModelRegistry::use('Test');

        $Test->addBehavior('Mock');

        $this->assertTrue(
            $Test->hasBehavior('Mock')
        );
    }

    public function testAddBehaviorDuplicate(): void
    {
        $this->expectException(OrmException::class);

        $Test = ModelRegistry::use('Test');

        $Test->addBehavior('Mock');
        $Test->addBehavior('Mock');
    }

    public function testAddBehaviorInvalid(): void
    {
        $this->expectException(OrmException::class);

        $Test = ModelRegistry::use('Test');

        $Test->addBehavior('Invalid');
    }

    public function testHasBehaviorInvalid(): void
    {
        $Test = ModelRegistry::use('Test');

        $this->assertFalse(
            $Test->hasBehavior('Invalid')
        );
    }

    public function testGetBehavior(): void
    {
        $Test = ModelRegistry::use('Test');

        $Test->addBehavior('Mock');

        $this->assertInstanceOf(
            Behavior::class,
            $Test->getBehavior('Mock')
        );
    }

    public function testGetBehaviorInvalid(): void
    {
        $Test = ModelRegistry::use('Test');

        $this->assertNull(
            $Test->getBehavior('Invalid')
        );
    }

    public function testRemoveBehavior(): void
    {
        $Test = ModelRegistry::use('Test');

        $Test->addBehavior('Mock');
        $Test->removeBehavior('Mock');

        $this->assertFalse(
            $Test->hasBehavior('Mock')
        );
    }

    public function testRemoveBehaviorInvalid(): void
    {
        $this->expectException(OrmException::class);

        $Test = ModelRegistry::use('Test');

        $Test->removeBehavior('Invalid');
    }

}
