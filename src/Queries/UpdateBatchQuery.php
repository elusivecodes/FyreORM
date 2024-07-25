<?php
declare(strict_types=1);

namespace Fyre\ORM\Queries;

use Fyre\ORM\Model;
use Fyre\ORM\Queries\Traits\ModelTrait;

/**
 * UpdateBatchQuery
 */
class UpdateBatchQuery extends \Fyre\DB\Queries\UpdateBatchQuery
{
    use ModelTrait;

    /**
     * New UpdateBatchQuery constructor.
     *
     * @param Model $model The Model.
     * @param array $options The UpdateBatchQuery options.
     */
    public function __construct(Model $model)
    {
        $this->model = $model;

        parent::__construct($this->model->getConnection(), $this->model->getTable());
    }
}
