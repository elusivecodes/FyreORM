<?php
declare(strict_types=1);

namespace Tests\Mock\Model;

use Fyre\Event\Event;
use Fyre\ORM\Model;
use Fyre\ORM\Queries\SelectQuery;
use Fyre\ORM\Result;

class OthersModel extends Model
{
    public function afterFind(Event $event, Result $result): Result
    {
        foreach ($result as $item) {
            $item->test = 'Test';
        }

        return $result;
    }

    public function beforeFind(Event $event, SelectQuery $query): SelectQuery
    {
        return $query->where([
            'value' => 1,
        ]);
    }
}
