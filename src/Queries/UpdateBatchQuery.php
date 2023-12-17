<?php
declare(strict_types=1);

namespace Fyre\ORM\Queries;

use Fyre\ORM\Model;
use Fyre\ORM\Queries\Traits\ModelTrait;

use function array_combine;
use function array_keys;
use function array_map;

/**
 * UpdateBatchQuery
 */
class UpdateBatchQuery extends \Fyre\DB\Queries\UpdateBatchQuery
{

    protected string $alias;

    use ModelTrait;

    /**
     * New UpdateBatchQuery constructor.
     * @param Model $model The Model.
     * @param array $options The UpdateBatchQuery options.
     */
    public function __construct(Model $model, array $options = [])
    {
        $this->model = $model;
        $this->alias = $options['alias'] ?? $this->model->getAlias();

        parent::__construct($this->model->getConnection(), [
            $this->alias => $this->model->getTable()
        ]);
    }

    /**
     * Get the alias.
     * @return string The alias.
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * Set the UPDATE batch data.
     * @param array $data The data.
     * @param string|array $keys The key to use for updating.
     * @param bool $overwrite Whether to overwrite the existing data.
     * @return UpdateBatchQuery The UpdateBatchQuery.
     */
    public function set(array $data, string|array $updateKeys, bool $overwrite = false): static
    {
        $data = array_map(
            function(array $values): array {
                $fields = array_map(
                    fn(string $field): string => $this->model->aliasField($field, $this->alias),
                    array_keys($values)
                );

                return array_combine($fields, $values);
            },
            $data
        );

        $updateKeys = array_map(
            fn(string $field): string => $this->model->aliasField($field, $this->alias),
            (array) $updateKeys
        );

        return parent::set($data, $updateKeys, $overwrite);
    }

}
