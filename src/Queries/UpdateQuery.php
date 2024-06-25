<?php
declare(strict_types=1);

namespace Fyre\ORM\Queries;

use Fyre\ORM\Model;
use Fyre\ORM\Queries\Traits\ModelTrait;

use function array_combine;
use function array_keys;
use function array_map;

/**
 * UpdateQuery
 */
class UpdateQuery extends \Fyre\DB\Queries\UpdateQuery
{
    use ModelTrait;

    protected string $alias;

    /**
     * New Query constructor.
     *
     * @param Model $model The Model.
     * @param array $options The Query options.
     */
    public function __construct(Model $model, array $options = [])
    {
        $this->model = $model;
        $this->alias = $options['alias'] ?? $this->model->getAlias();

        parent::__construct($this->model->getConnection(), [
            $this->alias => $this->model->getTable(),
        ]);
    }

    /**
     * Get the alias.
     *
     * @return string The alias.
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * Set the UPDATE data.
     *
     * @param array $data The data.
     * @param bool $overwrite Whether to overwrite the existing data.
     * @return UpdateQuery The UpdateQuery.
     */
    public function set(array $data, bool $overwrite = false): static
    {
        $fields = array_map(
            fn(string $field): string => $this->model->aliasField($field, $this->alias),
            array_keys($data)
        );

        $data = array_combine($fields, $data);

        return parent::set($data, $overwrite);
    }
}
