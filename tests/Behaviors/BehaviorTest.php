<?php
declare(strict_types=1);

namespace Tests\Behaviors;

use
    Fyre\ORM\BehaviorRegistry,
    Fyre\ORM\ModelRegistry,
    PHPUnit\Framework\TestCase;

final class BehaviorTest extends TestCase
{

    public function testGetConfig(): void
    {
        $Test = ModelRegistry::use('Test');

        $Test->addBehavior('Mock', [
            'value' => 1
        ]);

        $this->assertSame(
            [
                'value' => 1
            ],
            $Test->getBehavior('Mock')->getConfig()
        );
    }

    public function testGetModel(): void
    {
        $Test = ModelRegistry::use('Test');

        $Test->addBehavior('Mock');

        $this->assertSame(
            $Test,
            $Test->getBehavior('Mock')->getModel()
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
