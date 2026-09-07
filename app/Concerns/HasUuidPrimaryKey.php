<?php

namespace App\Concerns;

use Illuminate\Support\Str;

/**
 * Gives a model a UUIDv7 string primary key in place of an auto-increment integer.
 *
 * The column is still called `id` and every foreign key column keeps its name too -
 * only the type changed, from bigint to char(36). That is deliberate: every
 * relationship, `exists:` rule and query builder call in the app keeps working
 * untouched, so the blast radius of the key change stays in the schema rather than
 * spreading through 108 controllers.
 *
 * v7 rather than v4 because the first 48 bits are a millisecond timestamp. Rows
 * therefore insert in near-ascending key order, which keeps InnoDB appending to the
 * right-hand edge of the clustered index instead of splitting pages all over it, and
 * it means `orderBy('id')` still means "oldest first" the way the old integer key did.
 */
trait HasUuidPrimaryKey
{
    public function initializeHasUuidPrimaryKey(): void
    {
        $this->keyType = 'string';
        $this->incrementing = false;
    }

    protected static function bootHasUuidPrimaryKey(): void
    {
        static::creating(function ($model) {
            $key = $model->getKeyName();

            if (empty($model->{$key})) {
                $model->{$key} = (string) Str::uuid7();
            }
        });
    }

    /**
     * Row ids are opaque here, so anything that hands one back to the database has to
     * be a well-formed uuid. MariaDB compares a malformed char(36) happily and simply
     * matches nothing, which turns a bad id into an empty result rather than an error.
     */
    public static function isValidKey(mixed $value): bool
    {
        return is_string($value) && Str::isUuid($value);
    }
}
