<?php
declare(strict_types=1);

namespace Fyre\ORM\Queries;

use Fyre\ORM\Model;
use Fyre\ORM\Queries\Traits\ModelTrait;
use Fyre\Utility\Traits\MacroTrait;

/**
 * InsertQuery
 */
class InsertQuery extends \Fyre\DB\Queries\InsertQuery
{
    use MacroTrait;
    use ModelTrait;

    /**
     * New InsertQuery constructor.
     *
     * @param Model $model The Model.
     */
    public function __construct(
        protected Model $model
    ) {
        parent::__construct($this->model->getConnection());

        $this->into($this->model->getTable());
    }
}
