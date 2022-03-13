<?php
declare(strict_types=1);

namespace Tests\Mock\Model;

use
    Fyre\ORM\Model;

class Users extends Model
{

    protected string $testProperty = 'name';

    use CallbackTrait;

    public function __construct()
    {
        $this->hasOne('Addresses');
        $this->hasMany('Comments');
        $this->hasMany('Posts', [
            'dependent' => true
        ]);
    }

}
