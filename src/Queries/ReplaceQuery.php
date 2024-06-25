<?php
declare(strict_types=1);

namespace Fyre\ORM\Queries;

use Fyre\ORM\Model;
use Fyre\ORM\Queries\Traits\ModelTrait;

/**
 * ReplaceQuery
 */
class ReplaceQuery extends \Fyre\DB\Queries\ReplaceQuery
{
    use ModelTrait;

    /**
     * New ReplaceQuery constructor.
     *
     * @param Model $model The Model.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;

        parent::__construct($this->model->getConnection());

        $this->into($this->model->getTable());
    }
}
