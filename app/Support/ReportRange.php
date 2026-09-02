<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/**
 * The window a report covers.
 *
 * The report screens used to take a bare ?period=30 and turn it into
 * now()->subDays(30) — a *timestamp*, not a day boundary. That silently
 * disagreed with the day-by-day chart loop underneath it: the loop drew 30
 * buckets ending today, while the totals above it also swept up the slice of
 * the 31st day back that fell after the current clock time. The headline
 * figure and the chart it sat on were adding up different sets of orders.
 *
 * Everything here is therefore snapped to whole days in the app timezone,
 * which is also what MySQL's DATE(created_at) buckets on (no connection
 * timezone is configured, so timestamps are stored as app-local strings).
 * The window is inclusive at both ends: "last 7 days" is today plus the six
 * before it, not eight partial days.
 *
 * Values arrive in the query string where anything can be typed, so the
 * constructor is private and every entry point goes through fromRequest(),
 * which clamps rather than trusts. ?period=99999999 used to turn the chart
 * loop into a hang.
 */
final class ReportRange
{
    /** The window lengths the preset selector offers, in days. */
    public const PRESETS = [7, 30, 90, 365];

    /** The length the selectors open on when nothing valid was asked for. */
    public const DEFAULT_PRESET = 30;

    /**
     * The longest custom window allowed, in days (~2 years).
     *
     * Every day in the range becomes a chart bucket and a loop iteration, so
     * this is what stops a hand-typed ?from=1970-01-01 from building a
     * 20,000-element collection on every page load.
     */
    public const MAX_DAYS = 731;

    private function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
        public readonly ?int $preset,
    ) {}

    /**
     * Build the window from ?from=&to= (custom) or ?period= (preset).
     *
     * A custom range wins when both dates parse. Anything malformed, reversed,
     * in the future or absurdly long is corrected instead of rejected — a
     * report screen should still render.
     */
    public static function fromRequest(Request $request): self
    {
        $from = self::parseDate($request->input('from'));
        $to = self::parseDate($request->input('to'));

        if ($from || $to) {
            return self::custom($from, $to);
        }

        return self::preset((int) $request->input('period', self::DEFAULT_PRESET));
    }

    /** A fixed-length window ending today. */
    public static function preset(int $days): self
    {
        $days = in_array($days, self::PRESETS, true) ? $days : self::DEFAULT_PRESET;

        $end = CarbonImmutable::today()->endOfDay();

        return new self($end->subDays($days - 1)->startOfDay(), $end, $days);
    }

    /**
     * An arbitrary window.
     *
     * Either end may be missing — picking only a start date means "from then
     * until today", which is what a half-filled form should do rather than
     * throwing the whole range away.
     */
    public static function custom(?CarbonImmutable $from, ?CarbonImmutable $to): self
    {
        $today = CarbonImmutable::today();

        $from ??= $today;
        $to ??= $today;

        // A range that reads backwards is a mis-click, not an empty report.
        if ($from->greaterThan($to)) {
            [$from, $to] = [$to, $from];
        }

        // Tomorrow has no data; let the window end today at the latest.
        if ($to->greaterThan($today)) {
            $to = $today;
        }

        if ($from->greaterThan($to)) {
            $from = $to;
        }

        // Keep the end the user chose and pull the start forward: they are
        // usually looking at a recent window through a badly typed start date.
        if (round($from->diffInDays($to)) + 1 > self::MAX_DAYS) {
            $from = $to->subDays(self::MAX_DAYS - 1);
        }

        return new self($from->startOfDay(), $to->endOfDay(), null);
    }

    /** Whole days covered, both ends inclusive. */
    public function days(): int
    {
        return (int) round($this->start->startOfDay()->diffInDays($this->end->startOfDay())) + 1;
    }

    /** True when this is one of the preset lengths rather than a typed range. */
    public function isPreset(): bool
    {
        return $this->preset !== null;
    }

    /**
     * The equally long window immediately before this one.
     *
     * Used for "vs previous period". It ends the day before this range starts,
     * so the two windows never share a day — the old whereBetween() spanning
     * subDays($period * 2) to subDays($period) counted the boundary day twice.
     */
    public function previous(): self
    {
        $end = $this->start->subDay()->endOfDay();

        return new self($end->subDays($this->days() - 1)->startOfDay(), $end, null);
    }

    /**
     * Every day in the window, oldest first.
     *
     * @return \Generator<CarbonImmutable>
     */
    public function eachDay(): \Generator
    {
        for ($day = $this->start->startOfDay(), $i = $this->days(); $i > 0; $i--, $day = $day->addDay()) {
            yield $day;
        }
    }

    /** Y-m-d, for date inputs and chart keys. */
    public function fromDate(): string
    {
        return $this->start->format('Y-m-d');
    }

    public function toDate(): string
    {
        return $this->end->format('Y-m-d');
    }

    /** Human wording for the window, e.g. "Last 30 days" or "1–14 Aug 2026". */
    public function label(): string
    {
        if ($this->isPreset()) {
            return $this->preset === 365 ? 'Last year' : "Last {$this->preset} days";
        }

        if ($this->start->isSameDay($this->end)) {
            return $this->start->format('j M Y');
        }

        $from = $this->start->isSameYear($this->end)
            ? $this->start->format($this->start->isSameMonth($this->end, true) ? 'j' : 'j M')
            : $this->start->format('j M Y');

        return "{$from} – {$this->end->format('j M Y')}";
    }

    /**
     * The query string that reproduces this window.
     *
     * Export links and pagination carry this so a downloaded CSV covers the
     * range on screen rather than silently reverting to the default.
     *
     * @return array<string, string|int>
     */
    public function queryParams(): array
    {
        return $this->isPreset()
            ? ['period' => $this->preset]
            : ['from' => $this->fromDate(), 'to' => $this->toDate()];
    }

    /**
     * Y-m-d only; anything else is treated as "not supplied".
     *
     * The round-trip comparison is the point: createFromFormat() happily rolls
     * 2026-13-45 over into 2027-02-14 rather than failing, which would hand the
     * report a window nobody asked for.
     */
    private static function parseDate(mixed $value): ?CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        try {
            $date = CarbonImmutable::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        return $date->format('Y-m-d') === $value ? $date : null;
    }
}
