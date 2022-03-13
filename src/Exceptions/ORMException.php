<?php
declare(strict_types=1);

namespace Fyre\ORM\Exceptions;

use
    RunTimeException;

/**
 * ORMException
 */
class ORMException extends RunTimeException
{

    public static function forInvalidEntity()
    {
        throw new static('All entities must be an instance of Entity.');
    }

    public static function forInvalidFindProperty(string $property)
    {
        return new static('Model find property does not exist: '.$property);
    }

    public static function forInvalidRelationship(string $name)
    {
        return new static('Model relationship does not exist: '.$name);
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