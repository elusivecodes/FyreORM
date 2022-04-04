<?php
declare(strict_types=1);

namespace Fyre\ORM\Traits;

use
    Fyre\ORM\Exceptions\OrmException,
    Fyre\ORM\Relationships\BelongsTo,
    Fyre\ORM\Relationships\HasMany,
    Fyre\ORM\Relationships\HasOne,
    Fyre\ORM\Relationships\ManyToMany,
    Fyre\ORM\Relationships\Relationship;

use function
    array_key_exists;

/**
 * ModelRelationshipsTrait
 */
trait ModelRelationshipsTrait
{

    protected array $relationships = [];

    /**
     * Add a Relationship.
     * @param Relationship $relationship The Relationship.
     * @return Model The Model.
     * @throws OrmException if relationship alias or property is already used.
     */
    public function addRelationship(Relationship $relationship): static
    {
        $name = $relationship->getName();

        if (array_key_exists($name, $this->relationships)) {
            throw OrmException::forRelationshipNotUnique($name);
        }

        $property = $relationship->getProperty();

        if ($this->getSchema()->hasColumn($property)) {
            throw OrmException::forRelationshipColumnName($property);
        }

        $this->relationships[$name] = $relationship;

        return $this;
    }

    /**
     * Create a "belongs to" relationship.
     * @param string $name The relationship name.
     * @param array $data The relationship data.
     * @return BelongsTo The relationship.
     */
    public function belongsTo(string $name, array $data = []): BelongsTo
    {
        $data['source'] = $this;

        $relationship = new BelongsTo($name, $data);

        $this->addRelationship($relationship);

        return $relationship;
    }

    /**
     * Get a Relationship.
     * @param string $name The relationship name.
     * @return Relationship|null The Relationship.
     */
    public function getRelationship(string $name): Relationship|null
    {
        return $this->relationships[$name] ?? null;
    }

    /**
     * Get all relationships.
     * @return array The relationships.
     */
    public function getRelationships(): array
    {
        return $this->relationships;
    }

    /**
     * Create a "has many" relationship.
     * @param string $name The relationship name.
     * @param array $data The relationship data.
     * @return HasMany The relationship.
     */
    public function hasMany(string $name, array $data = []): HasMany
    {
        $data['source'] = $this;

        $relationship = new HasMany($name, $data);

        $this->addRelationship($relationship);

        return $relationship;
    }

    /**
     * Create a "has one" relationship.
     * @param string $name The relationship name.
     * @param array $data The relationship data.
     * @return HasOne The relationship.
     */
    public function hasOne(string $name, array $data = []): HasOne
    {
        $data['source'] = $this;

        $relationship = new HasOne($name, $data);

        $this->addRelationship($relationship);

        return $relationship;
    }

    /**
     * Determine if a Relationship exists.
     * @param string $name The relationship name.
     * @return bool TRUE if the Relationship exists, otherwise FALSE.
     */
    public function hasRelationship(string $name): bool
    {
        return array_key_exists($name, $this->relationships);
    }

    /**
     * Create a "many to many" relationship.
     * @param string $name The relationship name.
     * @param array $data The relationship data.
     * @return ManyToMany The relationship.
     */
    public function manyToMany(string $name, array $data = []): ManyToMany
    {
        $data['source'] = $this;

        $relationship = new ManyToMany($name, $data);

        $this->addRelationship($relationship);

        return $relationship;
    }

    /**
     * Remove an existing Relationship.
     * @param string $name The relationship name.
     * @return Model The Model.
     */
    public function removeRelationship(string $name): static
    {
        unset($this->relationships[$name]);

        return $this;
    }

    /**
     * Delete entities children.
     * @param array $entities The entities.
     * @param array $options The options for deleting.
     * @return bool TRUE if the delete was successful, otherwise FALSE.
     */
    protected function deleteChildren(array $entities, array $options = []): bool
    {
        foreach ($this->relationships AS $relationship) {
            if (!$relationship->isOwningSide($this)) {
                continue;
            }

            if (!$relationship->unlinkAll($entities, $options)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Save entities children.
     * @param array $entities The entities.
     * @param array $options The options for saving.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    protected function saveChildren(array $entities, array $options = []): bool
    {
        $options['clean'] = false;

        foreach ($this->relationships AS $relationship) {
            if (!$relationship->isOwningSide($this)) {
                continue;
            }

            if (!$relationship->saveRelated($entities, $options)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Save entities parents.
     * @param array $entities The entities.
     * @param array $options The options for saving.
     * @return bool TRUE if the save was successful, otherwise FALSE.
     */
    protected function saveParents(array $entities, array $options = []): bool
    {
        $options['clean'] = false;

        foreach ($this->relationships AS $relationship) {
            if ($relationship->isOwningSide($this)) {
                continue;
            }

            if (!$relationship->saveRelated($entities, $options)) {
                return false;
            }
        }

        return true;
    }

}
