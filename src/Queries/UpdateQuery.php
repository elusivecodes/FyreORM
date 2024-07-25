<?php
declare(strict_types=1);

namespace Fyre\ORM\Queries;

use Fyre\ORM\Model;
use Fyre\ORM\Queries\Traits\ModelTrait;

/**
 * UpdateQuery
 */
class UpdateQuery extends \Fyre\DB\Queries\UpdateQuery
{
    use ModelTrait;

    /**
     * New Query constructor.
     *
     * @param Model $model The Model.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;

        parent::__construct($this->model->getConnection(), $this->model->getTable());
    }
}
