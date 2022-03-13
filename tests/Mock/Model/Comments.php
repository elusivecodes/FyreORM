<?php
declare(strict_types=1);

namespace Tests\Mock\Model;

use
    Fyre\ORM\Model;

class Comments extends Model
{

    protected string $testProperty = 'content';

    use CallbackTrait;

    public function __construct()
    {
        $this->belongsTo('Users');
        $this->belongsTo('Posts');
    }

}
