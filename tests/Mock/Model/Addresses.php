<?php
declare(strict_types=1);

namespace Tests\Mock\Model;

use
    Fyre\ORM\Model;

class Addresses extends Model
{

    protected string $testProperty = 'suburb';

    use CallbackTrait;

    public function __construct()
    {
        $this->belongsTo('Users');
    }

}
