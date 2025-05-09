<?php
declare(strict_types=1);

namespace Tests\Sqlite\Model;

use Fyre\ORM\Behavior;
use Fyre\ORM\Exceptions\OrmException;

trait BehaviorTestTrait
{
    public function testAddBehavior(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $Items->addBehavior('Mock');

        $this->assertTrue(
            $Items->hasBehavior('Mock')
        );
    }

    public function testAddBehaviorDuplicate(): void
    {
        $this->expectException(OrmException::class);

        $Items = $this->modelRegistry->use('Items');

        $Items->addBehavior('Mock');
        $Items->addBehavior('Mock');
    }

    public function testAddBehaviorInvalid(): void
    {
        $this->expectException(OrmException::class);

        $Items = $this->modelRegistry->use('Items');

        $Items->addBehavior('Invalid');
    }

    public function testGetBehavior(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $Items->addBehavior('Mock');

        $this->assertInstanceOf(
            Behavior::class,
            $Items->getBehavior('Mock')
        );
    }

    public function testGetBehaviorInvalid(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $this->assertNull(
            $Items->getBehavior('Invalid')
        );
    }

    public function testHasBehaviorInvalid(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $this->assertFalse(
            $Items->hasBehavior('Invalid')
        );
    }

    public function testRemoveBehavior(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $Items->addBehavior('Mock');
        $Items->removeBehavior('Mock');

        $this->assertFalse(
            $Items->hasBehavior('Mock')
        );
    }

    public function testRemoveBehaviorInvalid(): void
    {
        $this->expectException(OrmException::class);

        $Items = $this->modelRegistry->use('Items');

        $Items->removeBehavior('Invalid');
    }
}
