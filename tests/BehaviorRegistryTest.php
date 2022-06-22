<?php
declare(strict_types=1);

namespace Tests;

use
    Fyre\ORM\Behavior,
    Fyre\ORM\BehaviorRegistry,
    Fyre\ORM\Exceptions\OrmException,
    Fyre\ORM\ModelRegistry,
    PHPUnit\Framework\TestCase;

final class BehaviorRegistryTest extends TestCase
{

    public function testFind(): void
    {
        $this->assertSame(
            '\Tests\Mock\Behaviors\Test',
            BehaviorRegistry::find('Test')
        );
    }

    public function testFindInvalid(): void
    {
        $this->assertNull(
            BehaviorRegistry::find('Invalid')
        );
    }

    public function testLoad(): void
    {
        $Test = ModelRegistry::use('Test');

        $this->assertInstanceOf(
            Behavior::class,
            BehaviorRegistry::load('Test', $Test)
        );
    }

    public function testLoadInvalid(): void
    {
        $this->expectException(OrmException::class);

        $Test = ModelRegistry::use('Test');

        BehaviorRegistry::load('Invalid', $Test);
    }

    public function testNamespaceNoLeadingSlash(): void
    {
        BehaviorRegistry::clear();
        BehaviorRegistry::addNamespace('Tests\Mock\Behaviors');

        $Test = ModelRegistry::use('Test');

        $this->assertInstanceOf(
            Behavior::class,
            BehaviorRegistry::load('Test', $Test)
        );
    }

    public function testNamespaceTrailingSlash(): void
    {
        BehaviorRegistry::clear();
        BehaviorRegistry::addNamespace('\Tests\Mock\Behaviors\\');

        $Test = ModelRegistry::use('Test');

        $this->assertInstanceOf(
            Behavior::class,
            BehaviorRegistry::load('Test', $Test)
        );
    }

    public static function setUpBeforeClass(): void
    {
        BehaviorRegistry::clear();
        BehaviorRegistry::addNamespace('Tests\Mock\Behaviors');
    }

}
