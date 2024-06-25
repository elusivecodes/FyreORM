<?php
declare(strict_types=1);

namespace Fyre\ORM\Traits;

/**
 * ParserTrait
 */
trait ParserTrait
{
    /**
     * Parse data from user.
     *
     * @param array $data The data.
     * @return array The user values.
     */
    public function parseSchema(array $data): array
    {
        $schema = $this->getSchema();

        foreach ($data as $field => $value) {
            if (!$schema->hasColumn($field)) {
                continue;
            }

            $data[$field] = $schema
                ->getType($field)
                ->parse($value);
        }

        return $data;
    }

    /**
     * Convert data to database.
     *
     * @param array $data The data.
     * @return array The database values.
     */
    public function toDatabaseSchema(array $data): array
    {
        $schema = $this->getSchema();

        foreach ($data as $field => $value) {
            $data[$field] = $schema
                ->getType($field)
                ->toDatabase($value);
        }

        return $data;
    }
}
