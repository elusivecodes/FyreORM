<?php
declare(strict_types=1);

namespace Tests\Mock\Model;

use
    Fyre\ORM\Model;

class Test extends Model
{

    protected string $testProperty = 'name';

    use CallbackTrait;

}
