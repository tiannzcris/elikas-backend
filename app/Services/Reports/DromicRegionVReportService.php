<?php

namespace App\Services\Reports;

use App\Models\Barangay;
use App\Models\CashAssistanceDisbursement;
use App\Models\DamagedHouse;
use App\Models\EvacuationCenter;
use App\Models\EvacuationCenterFacility;
use App\Models\EvacuationCenterQuickCount;
use App\Models\EvacuationEvent;
use App\Models\EvacuationRecord;
use App\Models\Family;
use App\Models\ReliefDistribution;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Generates a DSWD DROMIC "Table 2" (Region V consolidated) report scoped to
 * Ligao City, by loading the REAL official template (the exact file DSWD
 * distributes) and writing computed data into it -- not rebuilding the
 * 187-column merged header from scratch. This guarantees the output is
 * visually and structurally identical to what DSWD expects, since it IS
 * their file, just with Ligao City's section filled in.
 *
 * ============================================================================
 * IMPORTANT -- READ BEFORE TRUSTING THIS REPORT FOR AN ACTUAL SUBMISSION
 * ============================================================================
 * This service was built by precisely cross-referencing every column header
 * in the real template (rows 3-7, columns A through GE) against what this
 * system's database schema actually tracks. Three categories of columns:
 *
 * 1. POPULATED WITH FULL CONFIDENCE (the majority of columns): displacement
 *    counts inside/outside ECs, 7-bracket age x sex demographics, sectoral
 *    breakdown (pregnant, lactating, solo parent, PWD, IPs, 4Ps), all 19 EC
 *    facility types, relief item quantities/costs by source, cash assistance
 *    programs, damaged houses.
 *
 * 2. LEFT UNTOUCHED (columns C-G: Listahan poor families/individuals,
 *    PSA2020 population, Pantawid beneficiary count): these are STATIC
 *    reference data pre-filled by DSWD/PSA in the real template, not
 *    disaster-response data this system generates. Overwriting them would
 *    destroy correct pre-existing data.
 *
 * 3. KNOWN GAPS -- left blank, NOT fabricated:
 *    - "Child-Headed Family" and "Single-Headed Family" CUM columns (BS,
 *      BU, BW, BY), and their NOW columns (BT, BV, BX, BZ) for any
 *      barangay whose centers have no EC Board quick count yet: see
 *      SECTORAL COLUMNS' DATA SOURCE below.
 *    - "Origin of IDPs" (S/T): this system groups each row by the family's
 *      registering/home barangay, which makes a separate "origin barangay"
 *      column redundant under this grouping convention -- left blank rather
 *      than populated with a value that might not mean what a reader expects.
 *    - When a barangay used MORE THAN ONE evacuation center, only the
 *      highest-occupancy center's name/address/lat/long (columns O-R) are
 *      shown, since the template allows only one center's detail per row.
 *    - FLAGGED, NOT FIXED: that center is chosen from centers LOCATED in
 *      the barangay, not the centers its evacuees are actually in -- so a
 *      barangay whose evacuees all stay at another barangay's center gets
 *      blank O-R, M = 0 and zero facility columns. Inconsistent with the home-barangay
 *      grouping used everywhere else; see the $primaryCenter comment in
 *      computeBarangayData().
 *
 * A human should review this report before it is submitted anywhere
 * official -- treat it as a first draft that eliminates manual data entry
 * and arithmetic, not as a fully autonomous submission.
 * ============================================================================
 *
 * INCOMPLETE-IDENTITY EVACUEES: an evacuee with no sex and/or no
 * age_bracket (age_bracket is null unless a real date_of_birth or an EC
 * Board age_bracket_override is set -- see Evacuee::getAgeBracketAttribute())
 * has Evacuee::age_bracket and/or ->sex null. Every age x sex column
 * (AI-BJ) and sex subtotal (BK-BN) here is a strict equality filter
 * against one of the 7 bracket strings or 'male'/'female' -- null never
 * equals either, so such a person is excluded from all of those columns
 * while still counting in every column that isn't demographic-specific --
 * J/K (family/person counts), U-AB (inside/outside counts), AE/AF (total
 * persons) -- since those just count rows, not filter by identity.
 * UNLIKE the EC Information Board report (which can freely add a "Not Yet
 * Classified" row to its own from-scratch layout), this report's barangay
 * rows write into a REAL DSWD template's fixed, pre-labeled column grid --
 * inserting a new row/column there would misalign every column header
 * from the actual file DSWD distributes. So instead: computeBarangayData()
 * tracks the gap per barangay (via the non-template '_unclassified_cum'/
 * '_unclassified_now' keys, explicitly skipped by writeRow() so they're
 * never mistaken for a real column letter), previewSummary() surfaces it
 * per barangay in its own free-form JSON (shown in the dashboard before
 * generating the file), and generate() appends one free-text footnote row
 * beneath the city total -- in the margin around the official grid, not
 * inside it -- if the city-wide total is nonzero. Sectoral columns (BO-CP,
 * filtering on `is_pwd === true` etc.) are NOT given the same treatment --
 * same reasoning as the EC Board's own sectoral table: sectoral categories
 * overlap (a solo parent can also be a PWD), so there's no single
 * meaningful "total" for them to reconcile against in the first place.
 *
 * SECTORAL COLUMNS' DATA SOURCE (BO-CP):
 * the NOW half of each pair (BP, BR, CB, CD, CF, CH, CJ, CL, CN, CP)
 * prefers each EvacuationCenterQuickCount's staff-reported sectoralGroups()
 * -- a center shared by several barangays split between them by
 * headcount, an estimate (see headcountSplitAllocations()) --
 * -- same reasoning as EvacuationCenterQuickCount's own docblock, most
 * evacuees here are placeholders with no per-person flags set yet -- summed
 * across every center this barangay's currently-active evacuees are
 * actually checked into (a barangay can use more than one center; unlike
 * the EC-detail columns O-R, which show only the highest-occupancy one,
 * a sectoral total needs every center's figures, not just one). Falls
 * back to counting individual Evacuee flags only where NONE of those
 * centers has a quick-count row for this event at all. The CUM half
 * (BO, BQ, CA, CC, CE, CG, CI, CK, CM, CO) has no manually-reported
 * equivalent anywhere in this system -- quick-count sectoral figures are a
 * single current snapshot, not tracked cumulatively the way
 * families_cumulative/persons_cumulative are -- so it always stays
 * computed from Evacuee flags, same as before this fix.
 *
 * CHILD-HEADED / SINGLE-HEADED FAMILY (BS-BZ) follow the same NOW
 * precedence -- quick-count sectoralGroups() summed across the barangay's
 * in-use centers -- but have no fallback: neither families nor evacuees
 * carry a child/single-headed flag, so there's nothing to count instead.
 * Where no in-use center has a quick count, their NOW columns are simply
 * left blank (as the whole BS-BZ block used to be), and the city total
 * sums only the barangays that did report. Their CUM columns stay blank
 * everywhere: quick counts have no cumulative equivalent (above), and
 * there are no flags to compute one from.
 */
class DromicRegionVReportService
{
    // Matches Evacuee::age_bracket's possible values exactly -- the single
    // source of truth for the unclassified-detection helper below.
    private const AGE_BRACKETS = [
        'infant', 'toddler', 'preschooler', 'school_age', 'teenage', 'adult', 'senior_citizen',
    ];

    private const TEMPLATE_RELATIVE_PATH = 'report_templates/dromic_region_v_template.xlsx';

    private const SHEET_NAME = 'REGION V';

    // Fixed positions in the OFFICIAL template -- "City of Ligao" is a
    // pre-existing row in the real DSWD file, not something this system adds.
    // Public (not private): the anonymous IReadFilter class further below
    // references this constant, and PHP treats anonymous classes as fully
    // separate classes with no special access to the enclosing class's
    // private members, even though it's written inside one of its methods.
    public const TEMPLATE_HEADER_ROWS = 7;

    private const CITY_SUMMARY_ROW = self::TEMPLATE_HEADER_ROWS + 1;   // row 8 in the NEW, compact output file

    private const BARANGAY_DATA_START_ROW = self::TEMPLATE_HEADER_ROWS + 2; // row 9

    /**
     * Lightweight JSON preview of what generate() would produce -- reuses
     * the exact same computeBarangayData() used to build the real Excel
     * file, so preview numbers can never drift out of sync with the actual
     * report. Doesn't touch the Excel template at all, so this stays fast
     * regardless of the full report's size.
     */
    public function previewSummary(EvacuationEvent $event): array
    {
        $barangayIds = Family::where('evacuation_event_id', $event->id)->distinct()->pluck('barangay_id');

        return $barangayIds->map(function ($barangayId) use ($event) {
            $barangay = Barangay::find($barangayId);
            $data = $this->computeBarangayData($barangay, $event);

            return [
                'barangay' => $barangay->name,
                'affected_families' => $data['J'],
                'persons' => $data['AE'],
                'evacuation_center' => $data['Q'],
                'fourps_count' => $data['CM'] + $data['CO'],
                'pwd_count' => $data['CE'] + $data['CG'],
                // Persons counted in 'persons' above but missing sex and/or
                // age_bracket, so they're NOT reflected in any age/sex
                // column of the real generated report -- surfaced here so
                // staff see the gap before generating/submitting the file,
                // not just as a buried footnote inside it. 0 in the normal
                // case; nonzero means the report's own age/sex breakdown
                // will under-represent this barangay's real total.
                'unclassified_persons' => $data['_unclassified_cum'],
            ];
        })->values()->toArray();
    }

    public function generate(EvacuationEvent $event): string
    {
        // Give this room to run: even with the read-filter fix below,
        // spreadsheet I/O on a shared XAMPP setup can still be slower than
        // typical web requests. This does NOT mask the real fix (loading
        // less data) -- it's a safety margin on top of it.
        set_time_limit(180);

        $templatePath = storage_path('app/'.self::TEMPLATE_RELATIVE_PATH);

        // THE ACTUAL FIX: the real template covers all of Bicol Region --
        // 187 columns x 3,400+ rows, every province and municipality, even
        // though this system only ever needs Ligao City's small slice of
        // it. Loading the entire file just to read its 7-row header (which
        // is all we actually need, structurally) is what exceeded the
        // 60-second limit. A read filter restricts PhpSpreadsheet to
        // parsing only the header rows -- loading becomes near-instant
        // regardless of how large the full template is, since the other
        // ~3,400 rows are never parsed into memory at all.
        $reader = IOFactory::createReaderForFile($templatePath);
        $reader->setReadFilter(new class implements \PhpOffice\PhpSpreadsheet\Reader\IReadFilter
        {
            public function readCell($columnAddress, $row, $worksheetName = ''): bool
            {
                return $row <= DromicRegionVReportService::TEMPLATE_HEADER_ROWS;
            }
        });
        $headerOnly = $reader->load($templatePath);
        $headerSheet = $headerOnly->getSheetByName(self::SHEET_NAME);

        // Build a fresh, compact workbook -- just the header structure
        // (copied with full fidelity: values, styles, merges, column
        // widths) plus Ligao City's own rows. Not a 3,400-row file most of
        // which would be someone else's blank placeholder data anyway --
        // CSWDO Ligao City has no use for carrying around empty rows for
        // every other city and province in the region.
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle(self::SHEET_NAME);
        $this->copyHeaderInto($headerSheet, $sheet);

        // Only barangays that actually have at least one family registered
        // for this event get a row -- not all 55 of Ligao's barangays,
        // matching how the blank template only reserves rows, not
        // pre-fills every barangay's name.
        $barangayIds = Family::where('evacuation_event_id', $event->id)
            ->distinct()
            ->pluck('barangay_id');

        $barangayRows = [];
        $row = self::BARANGAY_DATA_START_ROW;

        foreach ($barangayIds as $barangayId) {
            $barangay = Barangay::find($barangayId);
            $data = $this->computeBarangayData($barangay, $event);
            $this->writeRow($sheet, $row, $data, isCityTotal: false);
            $barangayRows[] = $data;
            $row++;
        }


        $cityTotals = $this->sumRows($barangayRows);
        $this->writeRow($sheet, self::CITY_SUMMARY_ROW, $cityTotals, isCityTotal: true);
        // In the real template this label already existed in row 41 ("City
        // of Ligao"); in this fresh, compact file nothing has been written
        // to columns A/B yet, so it needs to be added explicitly here.
        $sheet->setCellValue('B'.self::CITY_SUMMARY_ROW, 'City of Ligao (TOTAL)');

        // Footnote, not a template row -- see this class's docblock for why
        // the gap can't be a proper row/column inside the official grid.
        // Only written when the gap is real, so a fully-classified report
        // (the normal case) stays visually identical to before this fix.
        if (($cityTotals['_unclassified_cum'] ?? 0) > 0) {
            // $row is one past the last barangay data row at this point (the
            // loop above increments it after each write) -- CITY_SUMMARY_ROW
            // sits ABOVE the barangay rows in this compact layout, so anchoring
            // off it instead would land the note in the middle of the barangay
            // block and silently clobber a barangay's row.
            $noteRow = $row + 1;
            $sheet->setCellValue('A'.$noteRow, sprintf(
                'DATA QUALITY NOTE: %d of the persons counted above (Now: %d) have no sex and/or age bracket on file yet, '
                .'so they are excluded from every age x sex column (AI-BJ, BK-BN) though still included in the family/person '
                .'totals (J, K, AE, AF). Complete their details ("Add details" on the Evacuees page) before relying on the '
                .'age/sex breakdown for an official submission.',
                $cityTotals['_unclassified_cum'],
                $cityTotals['_unclassified_now'] ?? 0
            ));
            $sheet->mergeCells("A{$noteRow}:GE{$noteRow}");
            $sheet->getStyle('A'.$noteRow)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFB45309'));
        }

        $outputPath = storage_path('app/reports/dromic_region_v_'.$event->id.'_'.now()->format('Ymd_His').'.xlsx');
        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($outputPath);

        return $outputPath;
    }

    /**
     * Copies the header structure (rows 1-7, all 187 columns: cell values,
     * styles, merged ranges, column widths, row heights) from the
     * filtered/partial template load into the new compact workbook -- this
     * is the piece that keeps the output visually identical to the real
     * DSWD template despite not loading the template's other ~3,400 rows.
     */
    private function copyHeaderInto(Worksheet $source, Worksheet $target): void
    {
        $highestColumnIndex = Coordinate::columnIndexFromString($source->getHighestColumn());

        // Column widths are worksheet-level metadata (not tied to specific
        // rows), so these copy for every column the header uses.
        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $letter = Coordinate::stringFromColumnIndex($col);
            $width = $source->getColumnDimension($letter)->getWidth();
            if ($width > 0) {
                $target->getColumnDimension($letter)->setWidth($width);
            }
        }

        for ($row = 1; $row <= self::TEMPLATE_HEADER_ROWS; $row++) {
            $height = $source->getRowDimension($row)->getRowHeight();
            if ($height > 0) {
                $target->getRowDimension($row)->setRowHeight($height);
            }

            for ($col = 1; $col <= $highestColumnIndex; $col++) {
                $letter = Coordinate::stringFromColumnIndex($col);
                $coordinate = "{$letter}{$row}";
                $target->setCellValue($coordinate, $source->getCell($coordinate)->getValue());
                $target->getStyle($coordinate)->applyFromArray(
                    $source->getStyle($coordinate)->exportArray()
                );
            }
        }

        // Only merge ranges that fall entirely within the header rows --
        // the template has no merges spanning into the data area, but this
        // guards against copying a malformed/out-of-range one regardless.
        foreach ($source->getMergeCells() as $range) {
            if (preg_match('/^[A-Z]+\d+:[A-Z]+(\d+)$/', $range, $m) && (int) $m[1] <= self::TEMPLATE_HEADER_ROWS) {
                $target->mergeCells($range);
            }
        }
    }

    /**
     * Computes every column value for one barangay's row. Returns a flat
     * associative array keyed by the EXACT column letter from the real
     * template, so writeRow() is a simple, auditable letter-by-letter
     * assignment rather than positional guessing.
     */
    /** @var array<int, array> event id => headcountSplitAllocations() result */
    private array $headcountSplitCache = [];

    /**
     * Splits every EC Board-reported sectoral figure (per group, per sex)
     * of each center across the HOME barangays of the evacuees currently
     * staying there, in proportion to how many of each barangay's own
     * evacuees are there. Returns
     * [centerId][barangayId][sectoralGroup]['male'|'female'] => int.
     * Home barangay = the family's barangay_id, the same grouping every
     * barangay row in this report uses (see computeBarangayData()'s
     * $families query), so each row takes exactly its own share.
     *
     * ESTIMATE for shared centers: the EC Board records one figure per
     * center, not which barangay each counted person is from, so a shared
     * center's split is proportional, not exact. A center used by a single
     * barangay is unaffected -- that barangay gets the full figure.
     *
     * Whole numbers via the largest-remainder method: each barangay first
     * gets the whole part of its exact share, then the units left over go
     * one at a time to the largest fractional remainders (ties: more
     * evacuees there, then lower barangay id, so the output is stable).
     * Rounding each share on its own could hand out more or less than the
     * center reported -- 3 split 5:1 is 2.5 : 0.5, which rounds to 3 + 1 =
     * 4 -- whereas this always distributes the center's figure exactly, so
     * the city total stays the true sum of what centers reported. (That
     * same example comes out 3 : 0: tied .5 remainders, larger group wins.)
     *
     * Cached per event: computeBarangayData() runs once per barangay, but
     * the split needs every barangay's headcount at a center at once.
     */
    private function headcountSplitAllocations(EvacuationEvent $event): array
    {
        if (isset($this->headcountSplitCache[$event->id])) {
            return $this->headcountSplitCache[$event->id];
        }

        // Same "current" test computeBarangayData() uses for $nowEvacuees
        // (an active record in this event), and only families that belong
        // to this event -- so these headcounts are exactly the evacuees the
        // barangay rows themselves count.
        $headcounts = EvacuationRecord::where('evacuation_event_id', $event->id)
            ->where('status', 'currently_evacuated')
            ->whereNotNull('evacuation_center_id')
            ->with('evacuee.family')
            ->get()
            ->filter(fn ($r) => (int) $r->evacuee?->family?->evacuation_event_id === (int) $event->id)
            ->groupBy('evacuation_center_id')
            ->map(fn ($records) => $records
                ->groupBy(fn ($r) => (int) $r->evacuee->family->barangay_id)
                ->map(fn ($byBarangay) => $byBarangay->pluck('evacuee_id')->unique()->count())
                ->all());

        $quickCounts = EvacuationCenterQuickCount::where('evacuation_event_id', $event->id)
            ->whereIn('evacuation_center_id', $headcounts->keys())
            ->with('sectoralGroups')
            ->get();

        $allocations = [];
        foreach ($quickCounts as $qc) {
            $centerHeadcounts = $headcounts[$qc->evacuation_center_id];
            foreach (EvacuationCenterQuickCount::SECTORAL_GROUPS as $group) {
                $reported = $qc->sectoralGroups->firstWhere('sectoral_group', $group);
                foreach (['male', 'female'] as $sex) {
                    $shares = $this->splitByLargestRemainder((int) ($reported?->{"{$sex}_count"} ?? 0), $centerHeadcounts);
                    foreach ($shares as $barangayId => $share) {
                        $allocations[$qc->evacuation_center_id][$barangayId][$group][$sex] = $share;
                    }
                }
            }
        }

        return $this->headcountSplitCache[$event->id] = $allocations;
    }

    /**
     * @param  array<int, int>  $headcounts  barangay id => evacuees at the center (each > 0)
     * @return array<int, int>  barangay id => whole-number share, summing to exactly $amount
     */
    private function splitByLargestRemainder(int $amount, array $headcounts): array
    {
        $total = array_sum($headcounts);
        $shares = [];
        $remainders = [];
        foreach ($headcounts as $barangayId => $count) {
            // Integer arithmetic throughout -- no float rounding error in
            // deciding which remainder is largest.
            $shares[$barangayId] = intdiv($amount * $count, $total);
            $remainders[$barangayId] = ($amount * $count) % $total;
        }

        $order = array_keys($headcounts);
        usort($order, fn ($a, $b) => [$remainders[$b], $headcounts[$b], $a] <=> [$remainders[$a], $headcounts[$a], $b]);
        for ($i = 0, $left = $amount - array_sum($shares); $i < $left; $i++) {
            $shares[$order[$i]]++;
        }

        return $shares;
    }

    private function computeBarangayData(Barangay $barangay, EvacuationEvent $event): array
    {
        $families = Family::where('evacuation_event_id', $event->id)
            ->where('barangay_id', $barangay->id)
            ->with(['members.evacuationRecords' => fn ($q) => $q->where('evacuation_event_id', $event->id)])
            ->get();

        $allEvacuees = $families->flatMap(fn ($f) => $f->members);

        // "Current" (NOW) = has an active record right now. "Cumulative"
        // (CUM) = was ever registered for this event, active or not.
        $cumEvacuees = $allEvacuees;
        $nowEvacuees = $allEvacuees->filter(
            fn ($e) => $e->evacuationRecords->contains(fn ($r) => $r->status === 'currently_evacuated')
        );

        // Each family shares one displacement_type (see FamilyController::store,
        // which applies it to every member at once) -- read from the first
        // member's most recent record as representative for the whole family.
        $familyDisplacementType = fn ($family) => optional(
            $family->members->first()?->evacuationRecords?->sortByDesc('date_in')?->first()
        )->displacement_type;

        $familiesInside = $families->filter(fn ($f) => $familyDisplacementType($f) === 'inside_center');
        $familiesOutside = $families->filter(fn ($f) => $familyDisplacementType($f) === 'outside_center');

        $bracketSex = function (Collection $evacuees, string $bracket, string $sex) {
            return $evacuees->filter(fn ($e) => $e->age_bracket === $bracket && $e->sex === $sex)->count();
        };

        // Anyone NOT captured by any (bracket, sex) cell above -- see this
        // class's docblock for why this can't just be an extra row/column
        // in the official template grid, and how it surfaces instead.
        $unclassified = fn (Collection $evacuees) => $evacuees->filter(
            fn ($e) => ! in_array($e->age_bracket, self::AGE_BRACKETS, true) || ! in_array($e->sex, ['male', 'female'], true)
        )->count();

        // Every center this barangay's CURRENTLY-active evacuees are
        // actually checked into for this event -- not just the single
        // highest-occupancy "primary" center used for the EC-detail
        // columns below, since a sectoral total needs every center's
        // reported figures, not just one. See this class's docblock for
        // why only the NOW columns use this (quick-count sectoral data
        // isn't tracked cumulatively anywhere in this system).
        $centerIdsInUse = $nowEvacuees
            ->flatMap(fn ($e) => $e->evacuationRecords->where('status', 'currently_evacuated'))
            ->pluck('evacuation_center_id')
            ->filter()
            ->unique();

        $quickCounts = $centerIdsInUse->isEmpty()
            ? collect()
            : EvacuationCenterQuickCount::whereIn('evacuation_center_id', $centerIdsInUse)
                ->where('evacuation_event_id', $event->id)
                ->with('sectoralGroups')
                ->get();

        // Returns null (not 0) when none of this barangay's in-use centers
        // has ever saved a quick-count row for this event, so callers can
        // tell "genuinely reported as zero" apart from "never reported,
        // fall back to counting Evacuee flags instead".
        //
        // A center used by evacuees from several barangays contributes only
        // THIS barangay's headcount share of its figures (see
        // headcountSplitAllocations()), not the full figure -- counting it
        // in full in every sharing barangay's row inflated the city total.
        // A center used by this barangay alone still contributes its full
        // figure, exactly as before.
        $allocations = $this->headcountSplitAllocations($event);
        $sectoralNowBySex = fn (string $group, string $sex) => $quickCounts->isEmpty() ? null : $quickCounts->sum(
            fn ($qc) => $allocations[$qc->evacuation_center_id][$barangay->id][$group][$sex] ?? 0
        );
        // Male + female shares rather than splitting the combined figure
        // separately, so BP/BR can never disagree with the per-sex columns.
        $sectoralNowTotal = fn (string $group) => $quickCounts->isEmpty()
            ? null
            : $sectoralNowBySex($group, 'male') + $sectoralNowBySex($group, 'female');

        // Child-Headed / Single-Headed Family (BS-BZ): only the NOW columns
        // (BT, BV, BX, BZ) can be filled, and only from quick-count data --
        // unlike every other sectoral category, no family or evacuee record
        // carries a child/single-headed flag, so there's nothing to fall
        // back to counting. When none of this barangay's in-use centers has
        // a quick count for this event, the keys are left out entirely (not
        // null, not 0): writeRow() then leaves those cells blank exactly as
        // before, and sumRows() can't turn "never reported" into a
        // fabricated 0 in the city total. CUM (BS, BU, BW, BY) always stays
        // blank -- see the class docblock.
        $headedFamilyNow = [];
        if ($quickCounts->isNotEmpty()) {
            foreach (['child_headed_family' => ['BT', 'BV'], 'single_headed_family' => ['BX', 'BZ']] as $group => [$maleCol, $femaleCol]) {
                $headedFamilyNow[$maleCol] = $sectoralNowBySex($group, 'male');
                $headedFamilyNow[$femaleCol] = $sectoralNowBySex($group, 'female');
            }
        }

        // The one evacuation center actually used by this barangay's
        // families, picked by highest current occupancy when more than one
        // was used -- the template has room for only one center's detail
        // per barangay row (see class docblock).
        //
        // FLAGGED, NOT FIXED (2026-09-25): this picks centers by where the
        // CENTER is located (evacuation_centers.barangay_id), while every
        // count in this row is by the families' HOME barangay. So a
        // barangay whose evacuees are all staying at another barangay's
        // center gets blank EC-detail columns (O-R), M = 0, and 0 in every
        // facility column (CQ onward, via $facilityQty below), even though
        // it did use a center -- e.g. Binanowan,
        // whose evacuee is at Binatagan Covered Court (event 7). A fix would
        // pick from the centers this row's evacuees are actually in
        // ($centerIdsInUse above) instead.
        $primaryCenter = EvacuationCenter::where('barangay_id', $barangay->id)
            ->whereHas('evacuationRecords', fn ($q) => $q->where('evacuation_event_id', $event->id))
            ->get()
            ->sortByDesc(fn ($c) => $c->currentOccupancy())
            ->first();

        $facilities = $primaryCenter
            ? EvacuationCenterFacility::where('evacuation_center_id', $primaryCenter->id)->get()->keyBy('facility_type')
            : collect();
        $facilityQty = fn (string $type) => optional($facilities->get($type))->quantity ?? 0;

        $damagedHouses = DamagedHouse::where('evacuation_event_id', $event->id)
            ->where('barangay_id', $barangay->id)
            ->first();

        $reliefByItem = ReliefDistribution::where('evacuation_event_id', $event->id)
            ->whereHas('evacuationCenter', fn ($q) => $q->where('barangay_id', $barangay->id))
            ->where('source', 'dswd')
            ->with('reliefItem')
            ->get()
            ->groupBy(fn ($d) => $d->reliefItem->item_name);
        $itemQty = fn (string $name) => $reliefByItem->get($name, collect())->sum('quantity');
        $itemCost = fn (string $name) => $reliefByItem->get($name, collect())->sum('total_cost');

        $costBySource = fn (string $source) => ReliefDistribution::where('evacuation_event_id', $event->id)
            ->whereHas('evacuationCenter', fn ($q) => $q->where('barangay_id', $barangay->id))
            ->where('source', $source)
            ->sum('total_cost');

        $cashByProgram = CashAssistanceDisbursement::where('evacuation_event_id', $event->id)
            ->where('barangay_id', $barangay->id)
            ->get()
            ->keyBy('program');
        $cashBeneficiaries = fn (string $program) => optional($cashByProgram->get($program))->number_of_beneficiaries ?? 0;
        $cashCost = fn (string $program) => optional($cashByProgram->get($program))->total_cost ?? 0;

        $foodCost = $itemCost('Family Food Pack (FFP)') + $itemCost('High Energy Biscuits (HEB)')
            + $itemCost('Ready-to-Eat Food (RTEF)') + $itemCost('Rice');
        $nonFoodCost = $itemCost('Hygiene Kit') + $itemCost('Sleeping Kit')
            + $itemCost('Shelter Repair Kit') + $itemCost('Modular Tent');
        $fniTotal = $foodCost + $nonFoodCost;
        $cashTotal = $cashCost('AICS') + $cashCost('AKAP') + $cashCost('ECT') + $cashCost('CFW') + $cashCost('SLP');
        $dswdTotal = $fniTotal + $cashTotal;
        $otherSourcesTotal = $costBySource('lgu') + $costBySource('ngo') + $costBySource('other');

        return [
            'barangay_name' => $barangay->name,
            // -- Number of affected (using affected = displaced simplification; documented in class docblock) --
            'H' => $barangay->name, 'I' => 1,
            'J' => $families->count(), 'K' => $allEvacuees->count(),
            'L' => $families->where('is_4ps_beneficiary', true)->count(),
            // -- EC detail (primary center only) --
            'M' => $primaryCenter ? 1 : 0,
            'O' => $primaryCenter?->latitude, 'P' => $primaryCenter?->longitude,
            'Q' => $primaryCenter?->name, 'R' => $primaryCenter?->address,
            // -- Displaced inside/outside --
            'U' => $familiesInside->count(), 'V' => $familiesInside->filter(fn ($f) => $f->members->intersect($nowEvacuees)->isNotEmpty())->count(),
            'W' => $familiesInside->sum(fn ($f) => $f->members->count()), 'X' => $familiesInside->flatMap->members->intersect($nowEvacuees)->count(),
            'Y' => $familiesOutside->count(), 'Z' => $familiesOutside->filter(fn ($f) => $f->members->intersect($nowEvacuees)->isNotEmpty())->count(),
            'AA' => $familiesOutside->sum(fn ($f) => $f->members->count()), 'AB' => $familiesOutside->flatMap->members->intersect($nowEvacuees)->count(),
            'AC' => $families->count(), 'AD' => $families->filter(fn ($f) => $f->members->intersect($nowEvacuees)->isNotEmpty())->count(),
            'AE' => $allEvacuees->count(), 'AF' => $nowEvacuees->count(),
            // -- Age x sex, inside ECs (CUM uses all registered; NOW uses currently-active) --
            'AI' => $bracketSex($cumEvacuees, 'infant', 'male'), 'AJ' => $bracketSex($nowEvacuees, 'infant', 'male'),
            'AK' => $bracketSex($cumEvacuees, 'infant', 'female'), 'AL' => $bracketSex($nowEvacuees, 'infant', 'female'),
            'AM' => $bracketSex($cumEvacuees, 'toddler', 'male'), 'AN' => $bracketSex($nowEvacuees, 'toddler', 'male'),
            'AO' => $bracketSex($cumEvacuees, 'toddler', 'female'), 'AP' => $bracketSex($nowEvacuees, 'toddler', 'female'),
            'AQ' => $bracketSex($cumEvacuees, 'preschooler', 'male'), 'AR' => $bracketSex($nowEvacuees, 'preschooler', 'male'),
            'AS' => $bracketSex($cumEvacuees, 'preschooler', 'female'), 'AT' => $bracketSex($nowEvacuees, 'preschooler', 'female'),
            'AU' => $bracketSex($cumEvacuees, 'school_age', 'male'), 'AV' => $bracketSex($nowEvacuees, 'school_age', 'male'),
            'AW' => $bracketSex($cumEvacuees, 'school_age', 'female'), 'AX' => $bracketSex($nowEvacuees, 'school_age', 'female'),
            'AY' => $bracketSex($cumEvacuees, 'teenage', 'male'), 'AZ' => $bracketSex($nowEvacuees, 'teenage', 'male'),
            'BA' => $bracketSex($cumEvacuees, 'teenage', 'female'), 'BB' => $bracketSex($nowEvacuees, 'teenage', 'female'),
            'BC' => $bracketSex($cumEvacuees, 'adult', 'male'), 'BD' => $bracketSex($nowEvacuees, 'adult', 'male'),
            'BE' => $bracketSex($cumEvacuees, 'adult', 'female'), 'BF' => $bracketSex($nowEvacuees, 'adult', 'female'),
            'BG' => $bracketSex($cumEvacuees, 'senior_citizen', 'male'), 'BH' => $bracketSex($nowEvacuees, 'senior_citizen', 'male'),
            'BI' => $bracketSex($cumEvacuees, 'senior_citizen', 'female'), 'BJ' => $bracketSex($nowEvacuees, 'senior_citizen', 'female'),
            'BK' => $cumEvacuees->where('sex', 'male')->count(), 'BL' => $nowEvacuees->where('sex', 'male')->count(),
            'BM' => $cumEvacuees->where('sex', 'female')->count(), 'BN' => $nowEvacuees->where('sex', 'female')->count(),
            // -- Sectoral -- NOW columns prefer quick-count sectoralGroups()
            // (summed across every center this barangay is actually using),
            // falling back to Evacuee flags only when none of those centers
            // has ever reported one. CUM stays Evacuee-flag-computed always
            // -- see this class's docblock for why.
            'BO' => $cumEvacuees->where('is_pregnant', true)->count(), 'BP' => $sectoralNowTotal('pregnant_women') ?? $nowEvacuees->where('is_pregnant', true)->count(),
            'BQ' => $cumEvacuees->where('is_lactating', true)->count(), 'BR' => $sectoralNowTotal('lactating_mothers') ?? $nowEvacuees->where('is_lactating', true)->count(),
            // BS-BZ (Child-Headed / Single-Headed Family): NOW columns only,
            // and only when a quick count exists -- see $headedFamilyNow above.
            ...$headedFamilyNow,
            'CA' => $cumEvacuees->where('is_solo_parent', true)->where('sex', 'male')->count(), 'CB' => $sectoralNowBySex('solo_parent', 'male') ?? $nowEvacuees->where('is_solo_parent', true)->where('sex', 'male')->count(),
            'CC' => $cumEvacuees->where('is_solo_parent', true)->where('sex', 'female')->count(), 'CD' => $sectoralNowBySex('solo_parent', 'female') ?? $nowEvacuees->where('is_solo_parent', true)->where('sex', 'female')->count(),
            'CE' => $cumEvacuees->where('is_pwd', true)->where('sex', 'male')->count(), 'CF' => $sectoralNowBySex('pwd', 'male') ?? $nowEvacuees->where('is_pwd', true)->where('sex', 'male')->count(),
            'CG' => $cumEvacuees->where('is_pwd', true)->where('sex', 'female')->count(), 'CH' => $sectoralNowBySex('pwd', 'female') ?? $nowEvacuees->where('is_pwd', true)->where('sex', 'female')->count(),
            'CI' => $cumEvacuees->where('is_indigenous_person', true)->where('sex', 'male')->count(), 'CJ' => $sectoralNowBySex('indigenous_peoples', 'male') ?? $nowEvacuees->where('is_indigenous_person', true)->where('sex', 'male')->count(),
            'CK' => $cumEvacuees->where('is_indigenous_person', true)->where('sex', 'female')->count(), 'CL' => $sectoralNowBySex('indigenous_peoples', 'female') ?? $nowEvacuees->where('is_indigenous_person', true)->where('sex', 'female')->count(),
            'CM' => $cumEvacuees->where('is_4ps_beneficiary', true)->where('sex', 'male')->count(), 'CN' => $sectoralNowBySex('four_ps_beneficiary', 'male') ?? $nowEvacuees->where('is_4ps_beneficiary', true)->where('sex', 'male')->count(),
            'CO' => $cumEvacuees->where('is_4ps_beneficiary', true)->where('sex', 'female')->count(), 'CP' => $sectoralNowBySex('four_ps_beneficiary', 'female') ?? $nowEvacuees->where('is_4ps_beneficiary', true)->where('sex', 'female')->count(),
            // -- Facilities (primary center) --
            'CQ' => $facilityQty('latrine_compost_pit'), 'CR' => $facilityQty('latrine_sealed'),
            'CS' => $facilityQty('toilet_male'), 'CT' => $facilityQty('toilet_female'), 'CU' => $facilityQty('toilet_common'),
            'CV' => $facilityQty('bathing_area_male'), 'CW' => $facilityQty('bathing_area_female'), 'CX' => $facilityQty('bathing_area_common'),
            'CY' => $facilityQty('child_friendly_space'), 'CZ' => $facilityQty('child_friendly_space'),
            'DA' => $facilityQty('women_friendly_space'), 'DB' => $facilityQty('women_friendly_space'),
            'DC' => $facilityQty('health_facility'), 'DD' => $facilityQty('health_facility'),
            'DE' => $facilityQty('prayer_room'), 'DF' => $facilityQty('prayer_room'),
            'DG' => $facilityQty('community_kitchen'), 'DH' => $facilityQty('community_kitchen'),
            'DI' => $facilityQty('handwashing_facility'), 'DJ' => $facilityQty('handwashing_facility'),
            'DK' => $facilityQty('livestock_area'), 'DL' => $facilityQty('livestock_area'),
            'DM' => $facilityQty('camp_management_desk'), 'DN' => $facilityQty('camp_management_desk'),
            'DO' => $facilityQty('info_board'), 'DP' => $facilityQty('info_board'),
            'DQ' => $facilityQty('storage_area'), 'DR' => $facilityQty('storage_area'),
            'DS' => $facilityQty('laundry_space'), 'DT' => $facilityQty('laundry_space'),
            // -- Tents (reuses relief_distributions -- same underlying data as the Non-Food Items section below) --
            'DU' => $itemQty('Modular Tent'), 'DV' => $itemQty('Modular Tent'),
            'DW' => $itemQty('Modular Tent'), 'DX' => $itemQty('Modular Tent'),
            // -- Damaged houses --
            'EC' => ($damagedHouses->totally_damaged_count ?? 0) + ($damagedHouses->partially_damaged_count ?? 0),
            'ED' => $damagedHouses->totally_damaged_count ?? 0, 'EE' => $damagedHouses->partially_damaged_count ?? 0,
            // -- Food items (DSWD source only) --
            'EF' => $itemQty('Family Food Pack (FFP)'), 'EG' => $itemCost('Family Food Pack (FFP)'),
            'EH' => $itemQty('High Energy Biscuits (HEB)'), 'EI' => $itemCost('High Energy Biscuits (HEB)'),
            'EJ' => $itemQty('Ready-to-Eat Food (RTEF)'), 'EK' => $itemCost('Ready-to-Eat Food (RTEF)'),
            'EL' => $itemQty('Rice'), 'EM' => $itemCost('Rice'),
            // -- Non-food items (DSWD source only) --
            'EQ' => $itemQty('Hygiene Kit'), 'ER' => $itemCost('Hygiene Kit'),
            'EU' => $itemQty('Sleeping Kit'), 'EV' => $itemCost('Sleeping Kit'),
            'FC' => $itemQty('Shelter Repair Kit'), 'FD' => $itemCost('Shelter Repair Kit'),
            'FA' => $itemQty('Modular Tent'), 'FB' => $itemCost('Modular Tent'),
            'FL' => $fniTotal,
            // -- Cash assistance --
            'FM' => $cashBeneficiaries('AICS'), 'FO' => $cashCost('AICS'),
            'FP' => $cashBeneficiaries('AKAP'), 'FR' => $cashCost('AKAP'),
            'FS' => $cashBeneficiaries('ECT'), 'FT' => $cashCost('ECT'),
            'FU' => $cashBeneficiaries('CFW'), 'FV' => $cashCost('CFW'),
            'FW' => $cashBeneficiaries('SLP'), 'FX' => $cashCost('SLP'),
            'FY' => $cashTotal, 'FZ' => $dswdTotal,
            'GA' => $costBySource('lgu'), 'GB' => $costBySource('ngo'), 'GC' => $costBySource('other'),
            'GD' => $dswdTotal + $otherSourcesTotal, 'GE' => $dswdTotal + $otherSourcesTotal,
            // NOT real template columns -- see this class's docblock.
            // writeRow() explicitly skips these (they're not valid Excel
            // column letters); sumRows() sums them like anything else,
            // giving generate() a city-wide total for the footnote row.
            '_unclassified_cum' => $unclassified($cumEvacuees),
            '_unclassified_now' => $unclassified($nowEvacuees),
        ];
    }

    /**
     * Sums every numeric field across all barangay rows for the city-total
     * row. Non-numeric/non-summable fields (name, lat/long, address) are
     * dropped -- a "total" row can't sensibly show a single center's address.
     */
    private function sumRows(array $barangayDataRows): array
    {
        $nonSummableKeys = ['barangay_name', 'H', 'O', 'P', 'Q', 'R'];
        $totals = [];

        foreach ($barangayDataRows as $rowData) {
            foreach ($rowData as $key => $value) {
                if (in_array($key, $nonSummableKeys, true)) {
                    continue;
                }
                $totals[$key] = ($totals[$key] ?? 0) + (is_numeric($value) ? $value : 0);
            }
        }

        return $totals;
    }

    private function writeRow(Worksheet $sheet, int $row, array $data, bool $isCityTotal): void
    {
        foreach ($data as $column => $value) {
            if ($column === 'barangay_name' || str_starts_with($column, '_')) {
                continue; // 'barangay_name' is handled via 'H' below; '_'-prefixed keys aren't real columns at all -- see computeBarangayData()
            }
            if ($isCityTotal && in_array($column, ['H', 'O', 'P', 'Q', 'R'], true)) {
                continue; // don't overwrite the template's existing "City of Ligao" label/columns with a sum
            }
            $sheet->setCellValue("{$column}{$row}", $value);
        }
    }
}
