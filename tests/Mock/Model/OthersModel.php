<?php
declare(strict_types=1);

namespace Tests\Mock\Model;

use Fyre\ORM\Model;
use Fyre\ORM\Queries\SelectQuery;
use Fyre\ORM\Result;

class OthersModel extends Model
{

    public function afterFind(Result $result): Result
    {
        foreach ($result AS $item) {
            $item->test = 'Test';
        }

        return $result;
    }

    public function beforeFind(SelectQuery $query): SelectQuery
    {
        return $query->where([
            'value' => 1
        ]);
    }

}
