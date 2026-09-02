<?php

namespace App\Rules;

use Closure;
use DateTimeInterface;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;

/**
 * Rejects a schedule that is set in the past.
 *
 * Consumed through {@see ValidationRules::scheduleStart()} and
 * {@see ValidationRules::scheduleEnd()}; the `date` rule that travels with them
 * owns the format, so an unparseable value passes straight through here rather
 * than collecting a second, confusing message.
 *
 * TWO DETAILS THAT LOOK LIKE BUGS AND ARE NOT:
 *
 * The floor is the current MINUTE, not the current second. The forms behind
 * this are `datetime-local` inputs, which have minute granularity: an admin who
 * picks 10:56 and submits at 10:56:30 has not chosen a past time, and being
 * told so thirty seconds after clicking the field would be nonsense.
 *
 * A value that matches `$unchanged` is always allowed. Editing the name of a
 * coupon that started last week must not force its start date forward - only a
 * schedule the admin is actually CHANGING has to be in the future. Pass the
 * stored value on an edit form and leave it null on a create form.
 */
class NotPastDateTime implements ValidationRule
{
    public function __construct(private DateTimeInterface|string|null $unchanged = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value)) {
            return;
        }

        $given = $this->parse($value);

        if ($given === null) {
            return;
        }

        $stored = $this->unchanged === null ? null : $this->parse($this->unchanged);

        // Compared to the minute for the same reason the floor is: the form
        // round-trips 'Y-m-d\TH:i', so a stored 10:56:07 comes back as 10:56.
        if ($stored !== null && $given->equalTo($stored->startOfMinute())) {
            return;
        }

        if ($given->lessThan(Carbon::now()->startOfMinute())) {
            $fail('The :attribute cannot be set in the past.');
        }
    }

    /** Parse to a fresh Carbon, floored to the minute; null if it is not a date. */
    private function parse(DateTimeInterface|string $value): ?Carbon
    {
        try {
            // Carbon::parse() on a DateTimeInterface returns a NEW instance, so
            // startOfMinute() below cannot mutate a model's cast attribute.
            return Carbon::parse($value)->startOfMinute();
        } catch (Exception) {
            return null;
        }
    }
}
