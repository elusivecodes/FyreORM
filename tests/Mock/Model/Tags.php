<?php
declare(strict_types=1);

namespace Tests\Mock\Model;

use
    Fyre\ORM\Model;

class Tags extends Model
{

    protected string $testProperty = 'tag';

    use CallbackTrait;

    public function __construct()
    {
        $this->manyToMany('Posts');
    }

}
