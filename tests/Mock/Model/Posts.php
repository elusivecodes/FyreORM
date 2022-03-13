<?php
declare(strict_types=1);

namespace Tests\Mock\Model;

use
    Fyre\ORM\Model;

class Posts extends Model
{

    protected string $testProperty = 'title';

    use CallbackTrait;

    public function __construct()
    {
        $this->belongsTo('Users');
        $this->hasMany('Comments');
        $this->manyToMany('Tags');
    }

}
