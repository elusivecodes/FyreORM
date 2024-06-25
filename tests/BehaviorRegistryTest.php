<?php
declare(strict_types=1);

namespace Tests;

use Fyre\ORM\Behavior;
use Fyre\ORM\BehaviorRegistry;
use Fyre\ORM\Exceptions\OrmException;
use Fyre\ORM\ModelRegistry;
use PHPUnit\Framework\TestCase;

final class BehaviorRegistryTest extends TestCase
{
    public function testFind(): void
    {
        $this->assertSame(
            '\Tests\Mock\Behaviors\TestBehavior',
            BehaviorRegistry::find('Test')
        );
    }

    public function testFindInvalid(): void
    {
        $this->assertNull(
            BehaviorRegistry::find('Invalid')
        );
    }

    public function testGetNamespaces(): void
    {
        $this->assertSame(
            [
                '\Tests\Mock\Behaviors\\',
            ],
            BehaviorRegistry::getNamespaces()
        );
    }

    public function testHasNamespace(): void
    {
        $this->assertTrue(
            BehaviorRegistry::hasNamespace('Tests\Mock\Behaviors')
        );
    }

    public function testHasNamespaceInvalid(): void
    {
        $this->assertFalse(
            BehaviorRegistry::hasNamespace('Tests\Invalid\Behaviors')
        );
    }

    public function testLoad(): void
    {
        $Item = ModelRegistry::use('Item');

        $this->assertInstanceOf(
            Behavior::class,
            BehaviorRegistry::load('Test', $Item)
        );
    }

    public function testLoadInvalid(): void
    {
        $this->expectException(OrmException::class);

        $Items = ModelRegistry::use('Items');

        BehaviorRegistry::load('Invalid', $Items);
    }

    public function testNamespaceNoLeadingSlash(): void
    {
        BehaviorRegistry::clear();
        BehaviorRegistry::addNamespace('Tests\Mock\Behaviors');

        $Items = ModelRegistry::use('Items');

        $this->assertInstanceOf(
            Behavior::class,
            BehaviorRegistry::load('Test', $Items)
        );
    }

    public function testNamespaceTrailingSlash(): void
    {
        BehaviorRegistry::clear();
        BehaviorRegistry::addNamespace('\Tests\Mock\Behaviors\\');

        $Items = ModelRegistry::use('Items');

        $this->assertInstanceOf(
            Behavior::class,
            BehaviorRegistry::load('Test', $Items)
        );
    }

    public function testRemoveNamespace(): void
    {
        $this->assertTrue(
            BehaviorRegistry::removeNamespace('Tests\Mock\Behaviors')
        );

        $this->assertFalse(
            BehaviorRegistry::hasNamespace('Tests\Mock\Behaviors')
        );
    }

    public function testRemoveNamespaceInvalid(): void
    {
        $this->assertFalse(
            BehaviorRegistry::removeNamespace('Tests\Invalid\Behaviors')
        );
    }

    public static function setUpBeforeClass(): void
    {
        BehaviorRegistry::clear();
        BehaviorRegistry::addNamespace('Tests\Mock\Behaviors');
    }
}
