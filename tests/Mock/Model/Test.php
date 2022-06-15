<?php
declare(strict_types=1);

namespace Tests\Mock\Model;

use
    Fyre\ORM\Model,
    Fyre\ORM\Query,
    Fyre\ORM\Result;

class Test extends Model
{

    protected string $testProperty = 'name';

    use CallbackTrait;

    public function afterFind(Result $result): Result
    {
        return $result;
    }

    public function beforeFind(Query $query): Query
    {
        return $query;
    }

}
