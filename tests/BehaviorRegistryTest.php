<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Config\Config;
use Fyre\Container\Container;
use Fyre\DB\ConnectionManager;
use Fyre\DB\TypeParser;
use Fyre\Entity\EntityLocator;
use Fyre\ORM\Behavior;
use Fyre\ORM\BehaviorRegistry;
use Fyre\ORM\Exceptions\OrmException;
use Fyre\ORM\ModelRegistry;
use Fyre\Schema\SchemaRegistry;
use Fyre\Utility\Inflector;
use PHPUnit\Framework\TestCase;

final class BehaviorRegistryTest extends TestCase
{
    protected BehaviorRegistry $behaviorRegistry;

    protected ModelRegistry $modelRegistry;

    public function testFind(): void
    {
        $this->assertSame(
            'Tests\Mock\Behaviors\TestBehavior',
            $this->behaviorRegistry->find('Test')
        );
    }

    public function testFindInvalid(): void
    {
        $this->assertNull(
            $this->behaviorRegistry->find('Invalid')
        );
    }

    public function testGetNamespaces(): void
    {
        $this->assertSame(
            [
                'Tests\Mock\Behaviors\\',
            ],
            $this->behaviorRegistry->getNamespaces()
        );
    }

    public function testHasNamespace(): void
    {
        $this->assertTrue(
            $this->behaviorRegistry->hasNamespace('Tests\Mock\Behaviors')
        );
    }

    public function testHasNamespaceInvalid(): void
    {
        $this->assertFalse(
            $this->behaviorRegistry->hasNamespace('Tests\Invalid\Behaviors')
        );
    }

    public function testLoad(): void
    {
        $Item = $this->modelRegistry->use('Item');

        $this->assertInstanceOf(
            Behavior::class,
            $this->behaviorRegistry->build('Test', $Item)
        );
    }

    public function testLoadInvalid(): void
    {
        $this->expectException(OrmException::class);

        $Items = $this->modelRegistry->use('Items');

        $this->behaviorRegistry->build('Invalid', $Items);
    }

    public function testNamespaceNoLeadingSlash(): void
    {
        $this->behaviorRegistry->clear();
        $this->behaviorRegistry->addNamespace('Tests\Mock\Behaviors');

        $Items = $this->modelRegistry->use('Items');

        $this->assertInstanceOf(
            Behavior::class,
            $this->behaviorRegistry->build('Test', $Items)
        );
    }

    public function testNamespaceTrailingSlash(): void
    {
        $this->behaviorRegistry->clear();
        $this->behaviorRegistry->addNamespace('Tests\Mock\Behaviors\\');

        $Items = $this->modelRegistry->use('Items');

        $this->assertInstanceOf(
            Behavior::class,
            $this->behaviorRegistry->build('Test', $Items)
        );
    }

    public function testRemoveNamespace(): void
    {
        $this->assertTrue(
            $this->behaviorRegistry->removeNamespace('Tests\Mock\Behaviors')
        );

        $this->assertFalse(
            $this->behaviorRegistry->hasNamespace('Tests\Mock\Behaviors')
        );
    }

    public function testRemoveNamespaceInvalid(): void
    {
        $this->assertFalse(
            $this->behaviorRegistry->removeNamespace('Tests\Invalid\Behaviors')
        );
    }

    protected function setUp(): void
    {
        $container = new Container();
        $container->singleton(TypeParser::class);
        $container->singleton(Config::class);
        $container->singleton(Inflector::class);
        $container->singleton(ConnectionManager::class);
        $container->singleton(SchemaRegistry::class);
        $container->singleton(ModelRegistry::class);
        $container->singleton(BehaviorRegistry::class);
        $container->singleton(EntityLocator::class);

        $container->use(Config::class)->set('App.locale', 'en');

        $this->modelRegistry = $container->use(ModelRegistry::class);
        $this->behaviorRegistry = $container->use(BehaviorRegistry::class);

        $this->behaviorRegistry->addNamespace('Tests\Mock\Behaviors');
        $this->modelRegistry->addNamespace('Tests\Mock\Model');
    }
}
