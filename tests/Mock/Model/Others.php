<?php
declare(strict_types=1);

namespace Tests\Mock\Model;

use
    Fyre\ORM\Model,
    Fyre\ORM\Query,
    Fyre\ORM\Result;

class Others extends Model
{

    public function afterFind(Result $result): Result
    {
        foreach ($result AS $item) {
            $item->test = 'Test';
        }

        return $result;
    }

    public function beforeFind(Query $query): Query
    {
        return $query->where([
            'value' => 1
        ]);
    }

}
