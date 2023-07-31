<?php
declare(strict_types=1);

namespace Tests\Behaviors;

use Fyre\DateTime\DateTime;
use Fyre\ORM\ModelRegistry;
use PHPUnit\Framework\TestCase;
use Tests\ConnectionTrait;

use function sleep;

final class TimestampTest extends TestCase
{

    use ConnectionTrait;

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
            DateTime::class,
            $timestamp->created
        );

        $this->assertInstanceOf(
            DateTime::class,
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
