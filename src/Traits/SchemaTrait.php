<?php
declare(strict_types=1);

namespace Fyre\ORM\Traits;

use Fyre\Schema\SchemaRegistry;
use Fyre\Schema\TableSchema;
use ReflectionClass;

use function array_intersect;
use function array_merge;
use function array_shift;
use function preg_replace;

/**
 * SchemaTrait
 */
trait SchemaTrait
{

    protected string $table;

    protected string|null $alias = null;

    protected array $primaryKey;

    protected string|null $autoIncrementKey = null;

    protected string|null $displayName = null;

    /**
     * Alias a field name.
     * @param string $field The field name.
     * @param string|null $alias The alias.
     * @return string The aliased field.
     */
    public function aliasField(string $field, string|null $alias = null): string
    {
        if (!$this->getSchema()->hasColumn($field)) {
            return $field;
        }

        $alias ??= $this->getAlias();

        return $alias.'.'.$field;
    }

    /**
     * Get the model alias.
     * @return string The model alias.
     */
    public function getAlias(): string
    {
        return $this->alias ??= preg_replace('/Model$/', '', (new ReflectionClass($this))->getShortName());
    }

    /**
     * Get the table auto increment column.
     * @return string|null The table auto increment column.
     */
    public function getAutoIncrementKey(): string|null
    {
        if (!$this->autoIncrementKey) {
            $schema = $this->getSchema();
    
            foreach ($this->getPrimaryKey() AS $key) {
                $column = $schema->column($key);
                $extra = $column['extra'];
    
                if ($extra !== 'auto_increment') {
                    continue;
                }

                $this->autoIncrementKey = $key;
                break;
            }
        }

        return $this->autoIncrementKey;
    }

    /**
     * Get the display name.
     * @return string The display name.
     */
    public function getDisplayName(): string
    {
        if (!$this->displayName) {
            $testColumns = array_merge(['name', 'title', 'label'], $this->getPrimaryKey());
            $columns = $this->getSchema()->columnNames();
            $matching = array_intersect($testColumns, $columns);

            $this->displayName = array_shift($matching);
        }

        return $this->displayName;
    }

    /**
     * Get the primary key(s).
     * @return array The primary key(s).
     */
    public function getPrimaryKey(): array
    {
        return $this->primaryKey ??= $this->getSchema()->primaryKey();
    }

    /**
     * Get the TableSchema.
     * @param string|null $type The connection type.
     * @return TableSchema The TableSchema.
     */
    public function getSchema(string|null $type = null): TableSchema
    {
        return SchemaRegistry::getSchema($this->getConnection($type))
            ->describe($this->getTable());
    }

    /**
     * Get the table name.
     * @return string The table name.
     */
    public function getTable(): string
    {
        return $this->table ??= static::tableize($this->getAlias());
    }

    /**
     * Set the model alias.
     * @param string $alias The model alias.
     * @return Model The Model.
     */
    public function setAlias(string $alias): static
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Set the display name.
     * @param string $displayName The display name.
     * @return Model The Model.
     */
    public function setDisplayName(string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    /**
     * Set the table name.
     * @param string $table The table name.
     * @return Model The Model.
     */
    public function setTable(string $table): static
    {
        $this->table = $table;

        return $this;
    }

}
