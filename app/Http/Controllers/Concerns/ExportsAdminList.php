<?php

namespace App\Http\Controllers\Concerns;

use App\Services\ReportExportService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The three things every admin list export needs, in one place.
 *
 * They were not in one place. The CSV de-fanging helper below had already been
 * written twice - once at the bottom of AbandonedCartController and once, with
 * a different return value, at the bottom of NewsletterController - and four
 * more screens (orders, returns, customers, staff) were about to grow a third
 * through sixth copy. A security fix that lives in six private methods is a
 * security fix that eventually gets applied to five of them.
 *
 * Alongside it sit the two decisions those six screens would otherwise each
 * re-derive: what the file is called and whether it is a CSV or a spreadsheet,
 * and where a calendar range cuts the list.
 */
trait ExportsAdminList
{
    /**
     * The ?format values every admin export understands.
     *
     * Three, not two, and that is not an oversight. InventoryReportController
     * branches on `$format === 'excel'` while naming the file .xlsx, so a
     * screen that standardised on ?format=xlsx would silently hand back a CSV
     * there. Accepting both spellings is what lets the newer screens put the
     * name on the button without breaking the convention already in the file.
     */
    public const EXPORT_FORMATS = ['csv', 'xlsx', 'excel'];

    /**
     * The most rows an xlsx export will carry.
     *
     * ReportExportService::exportCsv() streams: rows go out through fputcsv as
     * the generator yields them and nothing is held. exportExcel() cannot -
     * PhpSpreadsheet builds the whole workbook in memory before the writer
     * emits its first byte, at roughly a kilobyte per cell. The orders export
     * is 35 columns wide, so an uncapped spreadsheet of a busy month is a
     * memory_limit fatal on a shared 2 vCPU box, and a fatal part-way through
     * streamDownload() is a truncated file delivered with a 200 - the worst
     * possible failure for something an admin is about to do accounting with.
     *
     * 2,000 rows x 35 columns is ~70,000 cells, which fits. The CSV path is
     * deliberately left uncapped: it is the one that genuinely streams, and it
     * is where the notice row sends anyone who hits this ceiling.
     */
    public const XLSX_MAX_ROWS = 2000;

    /**
     * Validation rules for a calendar window.
     *
     * `after_or_equal` mirrors the setCustomValidity() guard the
     * x-admin.date-range-filter component already ships, so a reversed window
     * picked in the UI is caught before it costs a round trip. On the server it
     * is the real answer for a same-named pair, and the four screens differ
     * only in what they do with the refusal: orders, customers and staff
     * validate through $request->validate() and bounce back with the reason,
     * while returns - which must render whatever is in the address bar - drops
     * the window and prints the message above the table. Either way the list
     * and its export refuse identically, which is the property that matters.
     *
     * The swap in applyDateWindow() below is NOT dead behind this rule; it
     * catches what a per-field rule cannot see. The orders screen carries two
     * pairs (from/to and the older date_from/date_to aliases), so a `from` set
     * against a `date_to` satisfies both rule sets while still reading
     * backwards, and that is the case the swap corrects.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function dateWindowRules(string $fromName = 'from', string $toName = 'to'): array
    {
        return [
            $fromName => ['nullable', 'date'],
            $toName => ['nullable', 'date', 'after_or_equal:'.$fromName],
        ];
    }

    /**
     * The rule for ?format, so all four screens accept the same three spellings.
     *
     * @return array<int, mixed>
     */
    protected function exportFormatRule(): array
    {
        return ['nullable', Rule::in(self::EXPORT_FORMATS)];
    }

    /**
     * Cut a list down to an inclusive calendar window.
     *
     * App\Support\ReportRange encodes this project's day-boundary rules and was
     * the first thing tried here, but it does not fit a paginated list screen
     * and forcing it would lose rows:
     *
     *  - custom(null, null) resolves to *today*. On a report that is a sensible
     *    default window; on a list screen "no dates picked" has to mean the
     *    whole table, not the last twelve hours of it.
     *  - it clamps the end of the window to today, because tomorrow has no data
     *    to chart. A list must never hide a row that exists, whatever its
     *    timestamp says.
     *  - it clamps the length to MAX_DAYS (731), because every day in a report
     *    range becomes a chart bucket and a loop iteration. Here a range is one
     *    WHERE clause, and quietly shortening it would mean an admin who asked
     *    for five years got two, with nothing on the screen or in the file to
     *    say so - the exact class of silent disagreement between list and
     *    export this whole feature exists to prevent.
     *
     * So the semantics are ReportRange's and the clamping is not: whole days in
     * the app timezone (which is what Eloquent writes and what MySQL's DATE()
     * buckets on, no connection timezone being configured), inclusive at both
     * ends, and a backwards range swapped rather than rejected - a range that
     * reads backwards is a mis-click, not a request for an empty table.
     *
     * $column is used verbatim, so pass it qualified ('orders.created_at')
     * whenever the builder carries a join or a whereHas that could make it
     * ambiguous.
     *
     * Unparseable input is treated as "not supplied" rather than thrown: the
     * rules above should mean it never arrives, and index() and export() both
     * come through here, so the page and the file agree either way.
     */
    protected function applyDateWindow(Builder $query, string $column, mixed $from, mixed $to): Builder
    {
        [$start, $end] = $this->windowBounds($from, $to);

        if ($start) {
            // Bare comparisons, never whereDate(): DATE(created_at) is a
            // function on the column, and the index the migration added for it
            // cannot be used through one.
            $query->where($column, '>=', $start->copy()->startOfDay());
        }

        if ($end) {
            $query->where($column, '<=', $end->copy()->endOfDay());
        }

        return $query;
    }

    /**
     * The window that is actually in force, under its canonical names.
     *
     * A screen hands this to its view so the export links can be built from it
     * rather than from the raw query string, and the difference is not
     * cosmetic. http_build_query() - which is what route() builds a link with -
     * drops a null, so a parameter that is present-but-empty vanishes from the
     * href. On the orders screen present-but-empty is meaningful: it beats the
     * older ?date_from alias and clears the window. Building the link from the
     * raw parameters therefore dropped the empty pair, let the alias win again
     * on the download, and handed back a file cut to a window the page had just
     * stopped using - fewer rows than the table it was taken from, with nothing
     * on screen or in the file to say so.
     *
     * The pair comes back already order-corrected, for the same reason: the
     * list swaps a backwards cross-pair window, so the link must carry the
     * swapped one or the export would be refused where the page rendered.
     *
     * Empty ends are left out entirely, so `+ $window` on an href adds only
     * what is really set.
     *
     * @return array<string, string>
     */
    protected function resolvedWindow(mixed $from, mixed $to): array
    {
        [$start, $end] = $this->windowBounds($from, $to);

        return array_filter([
            'from' => $start?->format('Y-m-d'),
            'to' => $end?->format('Y-m-d'),
        ], fn (?string $value) => $value !== null);
    }

    /**
     * The two ends of a window as Carbon dates, backwards pairs swapped.
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    private function windowBounds(mixed $from, mixed $to): array
    {
        $start = $this->parseWindowDate($from);
        $end = $this->parseWindowDate($to);

        if ($start && $end && $start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        return [$start, $end];
    }

    /**
     * De-fang one CSV field.
     *
     * A value opening with =, +, - or @ is a formula to Excel and Google
     * Sheets, so a customer who registers as `=HYPERLINK("http://evil","hi")`
     * gets that executed in the spreadsheet of whoever opens the export. A
     * leading tab makes it text and is invisible in the sheet.
     *
     * It matters on the xlsx path for the same reason:
     * ReportExportService::exportExcel() writes through setCellValue(), and
     * PhpSpreadsheet stores a string beginning "=" as a live formula.
     *
     * Quoting is deliberately not done here. Callers that hand rows to
     * ReportExportService get it from fputcsv, and doubling it would put
     * literal quotes in every cell; the one caller that still builds its CSV
     * string by hand quotes the result of this method itself.
     */
    protected function csvCell(?string $value): string
    {
        $value = (string) $value;

        if ($value !== '' && str_contains("=+-@\t\r", $value[0])) {
            return "\t".$value;
        }

        return $value;
    }

    /**
     * Turn a header row, a row source and a format into the right download.
     *
     * The filename convention is the one AbandonedCartController set:
     * "<basename>-<Y-m-d-His>.<ext>". The seconds are load-bearing - an admin
     * comparing this morning's export against this afternoon's needs two files
     * in the downloads folder, not one that overwrote the other.
     *
     * $rows is expected to be a lazy source (->lazy()->map(...)). On the CSV
     * path it is never materialised. On the xlsx path it is, up to
     * XLSX_MAX_ROWS, and a truncated sheet is told so in its own last row.
     *
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    protected function streamExport(
        array $headers,
        iterable $rows,
        string $basename,
        string $sheetTitle = 'Report',
        ?string $format = null,
        ?ReportExportService $exporter = null,
    ): StreamedResponse {
        // Injected by the export() actions that already type-hint the service;
        // resolved here only so a controller with no other use for it can leave
        // the argument off.
        $exporter ??= app(ReportExportService::class);

        if (! $this->wantsSpreadsheet($format)) {
            return $exporter->exportCsv($headers, $rows, $this->exportFilename($basename, 'csv'));
        }

        $capped = [];
        $truncated = false;

        foreach ($rows as $row) {
            if (count($capped) >= self::XLSX_MAX_ROWS) {
                // Stop pulling from the generator. The point of the cap is not
                // to trim the file, it is to never hold more than this at once.
                $truncated = true;
                break;
            }

            $capped[] = $row;
        }

        if ($truncated) {
            // In the file, not only in a log. A spreadsheet that quietly stops
            // short is one somebody will add up and act on.
            $capped[] = array_pad([$this->xlsxRowCapNotice()], count($headers), '');
        }

        return $exporter->exportExcel(
            $headers,
            $capped,
            $this->exportFilename($basename, 'xlsx'),
            $sheetTitle,
        );
    }

    /**
     * The wording a truncated xlsx carries.
     *
     * Public and static so a screen can print the same sentence beside its
     * Export Excel button: an admin should learn about the ceiling before they
     * download 2,000 of 9,000 orders, not after.
     */
    public static function xlsxRowCapNotice(): string
    {
        return 'Only the first '.number_format(self::XLSX_MAX_ROWS).' rows are in this spreadsheet. '
            .'Narrow the date range, or use Export CSV, which has no row limit.';
    }

    /**
     * Whether ?format asked for a spreadsheet.
     *
     * Anything that is not one of the two spreadsheet spellings is a CSV,
     * the empty string and a missing parameter included, so a plain
     * "Export CSV" link needs no ?format on it at all.
     */
    protected function wantsSpreadsheet(?string $format): bool
    {
        return in_array(strtolower(trim((string) $format)), ['xlsx', 'excel'], true);
    }

    /** "<basename>-<Y-m-d-His>.<ext>", the convention the carts export set. */
    protected function exportFilename(string $basename, string $extension): string
    {
        return $basename.'-'.now()->format('Y-m-d-His').'.'.$extension;
    }

    /**
     * One end of a window, or null.
     *
     * Carbon::parse() rather than ReportRange's strict Y-m-d round trip: these
     * values arrive validated with `date`, and a controller may be resolving an
     * alias (?date_from) that a hand-typed URL wrote in some other shape. An
     * empty string is "not supplied", which is what an untouched
     * <input type="date"> submits.
     */
    private function parseWindowDate(mixed $value): ?Carbon
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
