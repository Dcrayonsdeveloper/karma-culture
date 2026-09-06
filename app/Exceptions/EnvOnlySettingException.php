<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when something tries to store a credential in the `settings` table.
 *
 * A distinct type rather than a bare RuntimeException so the admin screens can
 * turn it into a message an operator can act on ("set it in .env"), while
 * anything that hits it outside a request - a seeder, a console command - still
 * fails loudly instead of quietly writing a live secret to the database.
 */
class EnvOnlySettingException extends RuntimeException {}
