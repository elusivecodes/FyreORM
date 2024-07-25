<?php
declare(strict_types=1);

namespace Tests\Sqlite\Behaviors;

use Fyre\DateTime\DateTime;
use Fyre\ORM\ModelRegistry;
use PHPUnit\Framework\TestCase;
use Tests\Sqlite\SqliteConnectionTrait;

use function sleep;

final class TimestampTest extends TestCase
{
    use SqliteConnectionTrait;

    public function testTimestampsCreate(): void
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

    public function testTimestampsUpdate(): void
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
