<?php
declare(strict_types=1);

namespace Tests\Behaviors;

use
    Fyre\DateTime\DateTimeInterface,
    Fyre\ORM\BehaviorRegistry,
    Fyre\ORM\ModelRegistry,
    PHPUnit\Framework\TestCase,
    Tests\ConnectionTrait;

use function
    sleep;

final class TimestampTest extends TestCase
{

    use
        ConnectionTrait;

    public function testTimestampsCreate()
    {
        $Timestamps = ModelRegistry::use('Timestamps');

        $Timestamps->addBehavior('Timestamp');

        $timestamp = $Timestamps->newEmptyEntity();

        $this->assertTrue(
            $Timestamps->save($timestamp)
        );

        $timestamp = $Timestamps->find()->first();

        $this->assertInstanceOf(
            DateTimeInterface::class,
            $timestamp->created
        );

        $this->assertInstanceOf(
            DateTimeInterface::class,
            $timestamp->modified
        );
    }

    public function testTimestampsUpdate()
    {
        $Timestamps = ModelRegistry::use('Timestamps');

        $Timestamps->addBehavior('Timestamp');

        $timestamp = $Timestamps->newEmptyEntity();

        $this->assertTrue(
            $Timestamps->save($timestamp)
        );

        $timestamp = $Timestamps->find()->first();

        $originalModified = $timestamp->modified->toIsoString();

        $timestamp->setDirty('created', true);

        sleep(1);

        $this->assertTrue(
            $Timestamps->save($timestamp)
        );

        $timestamp = $Timestamps->find()->first();

        $this->assertNotSame(
            $originalModified,
            $timestamp->modified->toIsoString()
        );
    }

}
