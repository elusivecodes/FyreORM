<?php
declare(strict_types=1);

namespace Tests\Behaviors;

use Fyre\ORM\BehaviorRegistry;
use Fyre\ORM\ModelRegistry;
use PHPUnit\Framework\TestCase;

final class BehaviorTest extends TestCase
{
    public function testGetConfig(): void
    {
        $Items = ModelRegistry::use('Items');

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
        $Items = ModelRegistry::use('Items');

        $Items->addBehavior('Mock');

        $this->assertSame(
            $Items,
            $Items->getBehavior('Mock')->getModel()
        );
    }

    protected function setUp(): void
    {
        BehaviorRegistry::clear();
        BehaviorRegistry::addNamespace('Tests\Mock\Behaviors');

        ModelRegistry::clear();
        ModelRegistry::addNamespace('Tests\Mock\Model');
    }
}
