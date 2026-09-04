<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The signup that lost a race.
 *
 * Raised where the duplicate is DISCOVERED - inside the account-creation
 * transaction, at the statement that failed - rather than after it, and it
 * carries the answer out with it. That ordering is the whole reason this class
 * exists: MySQL rolls back only the failing statement on a duplicate key, so at
 * that moment the row that beat us is still there to be read and named. Once
 * the transaction has unwound, working out which column collided means a fresh
 * read on a fresh snapshot, and whether that read sees the winner depends on
 * the isolation level rather than on anything this application controls.
 *
 * @see \App\Http\Controllers\Auth\RegisterController
 */
class DuplicateAccountException extends RuntimeException
{
    /**
     * @param  array<string, array<int, string>>  $errors  field => messages, ready for a validation envelope
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct('An account already exists for one of the details supplied.');
    }

    /** The sentence to lead with - the first field's first message. */
    public function firstMessage(): string
    {
        $first = $this->errors[array_key_first($this->errors)] ?? [];

        return $first[0] ?? 'We could not create your account just now. Please try again.';
    }
}
