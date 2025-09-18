<?php
declare(strict_types=1);

namespace Tests;

use Fyre\Config\Config;
use Fyre\Container\Container;
use Fyre\DB\ConnectionManager;
use Fyre\DB\TypeParser;
use Fyre\Entity\EntityLocator;
use Fyre\Event\EventManager;
use Fyre\ORM\Behavior;
use Fyre\ORM\BehaviorRegistry;
use Fyre\ORM\ModelRegistry;
use Fyre\Schema\SchemaRegistry;
use Fyre\Utility\Inflector;
use Fyre\Utility\Traits\MacroTrait;
use PHPUnit\Framework\TestCase;

use function class_uses;

final class BehaviorTest extends TestCase
{
    protected BehaviorRegistry $behaviorRegistry;

    protected ModelRegistry $modelRegistry;

    public function testGetConfig(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $Items->addBehavior('Mock', [
            'value' => 1,
        ]);

        $this->assertSame(
            [
                'value' => 1,
            ],
            $Items->getBehavior('Mock')->getConfig()
        );
    }

    public function testGetModel(): void
    {
        $Items = $this->modelRegistry->use('Items');

        $Items->addBehavior('Mock');

        $this->assertSame(
            $Items,
            $Items->getBehavior('Mock')->getModel()
        );
    }

    public function testMacroable(): void
    {
        $this->assertContains(
            MacroTrait::class,
            class_uses(Behavior::class)
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
        $container->singleton(EventManager::class);

        $container->use(Config::class)->set('App.locale', 'en');

        $this->modelRegistry = $container->use(ModelRegistry::class);
        $this->behaviorRegistry = $container->use(BehaviorRegistry::class);

        $this->behaviorRegistry->addNamespace('Tests\Mock\Behaviors');
        $this->modelRegistry->addNamespace('Tests\Mock\Model');
    }
}
