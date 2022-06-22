<?php
declare(strict_types=1);

namespace Fyre\ORM\Exceptions;

use
    RunTimeException;

/**
 * OrmException
 */
class OrmException extends RunTimeException
{

    public static function forBehaviorExists(string $name)
    {
        return new static('Model behavior already exists: '.$name);
    }

    public static function forInvalidBehavior(string $name)
    {
        return new static('Model behavior not exist: '.$name);
    }

    public static function forInvalidEntity()
    {
        throw new static('All entities must be an instance of Entity.');
    }

    public static function forInvalidFindProperty(string $property)
    {
        return new static('Model find property does not exist: '.$property);
    }

    public static function forInvalidStrategy(string $strategy)
    {
        throw new static('Invalid relationship strategy: '.$strategy);
    }

    public static function forInvalidRelationship(string $name)
    {
        return new static('Model relationship does not exist: '.$name);
    }

    public static function forMissingBehavior(string $name)
    {
        return new static('Model behavior not loaded: '.$name);
    }

    public static function forRelationshipColumnName(string $property)
    {
        return new static('Model relationship property matches table column: '.$property);
    }

    public static function forRelationshipNotUnique(string $name)
    {
        return new static('Model relationship already exists: '.$name);
    }

}
