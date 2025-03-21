<?php
declare(strict_types=1);

namespace Fyre\ORM\Exceptions;

use RunTimeException;

/**
 * OrmException
 */
class OrmException extends RunTimeException
{
    public static function forAliasNotUnique(string $name): static
    {
        return new static('Model alias is already used: '.$name);
    }

    public static function forBehaviorExists(string $name): static
    {
        return new static('Model behavior already exists: '.$name);
    }

    public static function forInvalidBehavior(string $name): static
    {
        return new static('Model behavior does not exist: '.$name);
    }

    public static function forInvalidEntity(): static
    {
        return new static('All entities must be an instance of Entity.');
    }

    public static function forInvalidRelationship(string $name): static
    {
        return new static('Model relationship does not exist: '.$name);
    }

    public static function forInvalidSaveStrategy(string $saveStrategy): static
    {
        return new static('Invalid relationship save strategy: '.$saveStrategy);
    }

    public static function forInvalidStrategy(string $strategy): static
    {
        return new static('Invalid relationship strategy: '.$strategy);
    }

    public static function forInvalidStrategyContainCallback(string $strategy): static
    {
        return new static('Invalid relationship strategy for contain callback: '.$strategy);
    }

    public static function forJoinAliasNotUnique(string $name): static
    {
        return new static('Join alias is already used: '.$name);
    }

    public static function forMissingBehavior(string $name): static
    {
        return new static('Model behavior not loaded: '.$name);
    }

    public static function forRelationshipColumnName(string $property): static
    {
        return new static('Model relationship property matches table column: '.$property);
    }

    public static function forRelationshipNotUnique(string $name): static
    {
        return new static('Model relationship already exists: '.$name);
    }
}
