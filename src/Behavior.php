<?php
declare(strict_types=1);

namespace Fyre\ORM;

use function array_replace;

/**
 * Behavior
 */
abstract class Behavior
{

    protected static array $defaults = [];

    protected Model $model;

    protected array $config;

    /**
     * New Behavior constructor.
     * @param Model $model The Model.
     * @param array $options The behavior options.
     */
    public function __construct(Model $model, array $options = [])
    {
        $this->model = $model;

        $this->config = array_replace(static::$defaults, $options);
    }

    /**
     * Get the config.
     * @return array The behavior config.
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Get the Model.
     * @return Model The Model.
     */
    public function getModel(): Model
    {
        return $this->model;
    }

}
