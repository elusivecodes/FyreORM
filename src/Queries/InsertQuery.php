<?php
declare(strict_types=1);

namespace Fyre\ORM\Queries;

use Fyre\ORM\Model;
use Fyre\ORM\Queries\Traits\ModelTrait;

/**
 * InsertQuery
 */
class InsertQuery extends \Fyre\DB\Queries\InsertQuery
{

    use ModelTrait;

    /**
     * New InsertQuery constructor.
     * @param Model $model The Model.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;

        parent::__construct($this->model->getConnection());

        $this->into($this->model->getTable());
    }

}
