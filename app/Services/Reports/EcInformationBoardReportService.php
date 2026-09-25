<?php

namespace App\Services\Reports;

use App\Models\EvacuationCenter;
use App\Models\EvacuationCenterQuickCount;
use App\Models\EvacuationEvent;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Generates a per-center EC Information Board -- the single-page board
 * CSWDO Ligao City posts at each evacuation center. Built fresh (not from
 * the uploaded template) since its layout is simple enough to reproduce
 * exactly and doing so avoids shipping a second binary template file.
 *
 * 4Ps BENEFICIARIES / SECTORAL SOURCE: the header's "4Ps Beneficiaries:"
 * count and every Sectoral Group row are staff-reported aggregates, not
 * derived from individual Evacuee flags -- same reasoning as
 * EvacuationCenterQuickCount's own docblock (most evacuees are
 * placeholders with no name/details filled in, so per-person flags like
 * is_4ps_beneficiary are rarely set). When a EvacuationCenterQuickCount
 * row exists for this center+event, its beneficiaries_4ps and
 * sectoralGroups() are used directly for all eight rows; only falls back
 * to counting individual Evacuee flags when no such row exists at all
 * (i.e. the EC Board's sectoral form has never been saved here).
 *
 * CHILD-HEADED / SINGLE-HEADED FAMILY: previously hardcoded to 0 as a
 * known gap -- that's no longer true. Both are now real, collected
 * categories (the EC Board's own sectoral breakdown form has inputs for
 * them, saved to evacuation_center_quick_count_sectoral_groups, same as
 * the other six). The one difference: neither has any per-Evacuee flag
 * to fall back to (no such column exists on families or evacuees), so
 * when no quick-count row exists yet they stay 0 -- meaning "not yet
 * reported", same as every other row in that situation, not "confirmed
 * zero". DromicRegionVReportService still has its own separate, older
 * version of this same gap -- not changed here.
 *

 * PLACEHOLDER EVACUEES: a placeholder's age_bracket and/or sex can be null
 * (no age_bracket_override or real date_of_birth yet), so the age/sex
 * filters below never match them for any specific bracket/sex cell -- but
 * unlike the sectoral table (where this stays a silent, expected gap; see
 * DromicRegionVReportService's docblock), the age/sex table's own "Not Yet
 * Classified" row (see generate()) counts them explicitly, so this
 * table's own total always still equals $now->count() /
 * $allEvacuees->count() (plain row counts, not identity-filtered) instead
 * of quietly under-representing the real headcount on an official export.
 */
class EcInformationBoardReportService
{
    public function generate(EvacuationCenter $center, EvacuationEvent $event): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('EC Information Board');

        $allEvacuees = $center->evacuationRecords()
            ->where('evacuation_event_id', $event->id)
            ->with('evacuee')
            ->get()
            ->pluck('evacuee');

        $now = $allEvacuees->filter(fn ($e) => $e->evacuationRecords()
            ->where('evacuation_event_id', $event->id)
            ->where('status', 'currently_evacuated')
            ->exists());

        $familiesCum = $allEvacuees->pluck('family_id')->unique()->count();
        $familiesNow = $now->pluck('family_id')->unique()->count();

        $quickCount = EvacuationCenterQuickCount::where('evacuation_center_id', $center->id)
            ->where('evacuation_event_id', $event->id)
            ->with('sectoralGroups')
            ->first();

        $fourPsCount = $quickCount
            ? $quickCount->beneficiaries_4ps
            : $allEvacuees->where('is_4ps_beneficiary', true)->count();

        $row = 1;
        $sheet->setCellValue("A{$row}", 'EVACUATION CENTER INFORMATION BOARD');
        $sheet->mergeCells("A{$row}:H{$row}");
        $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row += 2;

        $sheet->setCellValue("A{$row}", 'Barangay:');
        $sheet->setCellValue("B{$row}", $center->barangay?->name);
        $sheet->setCellValue("F{$row}", 'As of:');
        $sheet->setCellValue("G{$row}", now()->format('F j, Y g:i A'));
        $row++;

        $sheet->setCellValue("A{$row}", 'Evacuation Center/Site:');
        $sheet->setCellValue("B{$row}", $center->name);
        $row++;

        $sheet->setCellValue("A{$row}", 'No. of Families (Cum/Now):');
        $sheet->setCellValue("C{$row}", "{$familiesCum} / {$familiesNow}");
        $sheet->setCellValue("F{$row}", 'No. of Persons (Cum/Now):');
        $sheet->setCellValue("H{$row}", "{$allEvacuees->count()} / {$now->count()}");
        $row++;

        $sheet->setCellValue("F{$row}", '4Ps Beneficiaries:');
        $sheet->setCellValue("H{$row}", $fourPsCount);
        $row += 2;

        // -- Age and Sex Disaggregation --
        $row = $this->writeSectionHeader($sheet, $row, 'Age and Sex Disaggregation', ['Male', 'Female', 'Total']);

        $brackets = [
            'infant' => 'Infants (0-6 months old)',
            'toddler' => 'Toddlers (7 mos.-2 yrs. old)',
            'preschooler' => 'Preschoolers (3-5 yrs. old)',
            'school_age' => 'School Age (6-12 yrs. old)',
            'teenage' => 'Teenage (13-17 yrs. old)',
            'adult' => 'Adult (18-59 yrs. old)',
            'senior_citizen' => 'Senior Citizens (60 and above)',
        ];

        $ageTotal = ['male' => 0, 'female' => 0];
        foreach ($brackets as $key => $label) {
            $male = $now->filter(fn ($e) => $e->age_bracket === $key && $e->sex === 'male')->count();
            $female = $now->filter(fn ($e) => $e->age_bracket === $key && $e->sex === 'female')->count();
            $ageTotal['male'] += $male;
            $ageTotal['female'] += $female;
            $row = $this->writeDataRow($sheet, $row, $label, $male, $female);
        }

        // "Not Yet Classified": anyone currently here missing sex and/or
        // age_bracket entirely (e.g. a record from before this app tracked
        // either) -- without this row they'd simply vanish from every
        // bracket above while still being part of $now->count(), which is
        // exactly the silent Now-vs-breakdown mismatch this row exists to
        // surface instead of hide. Male/Female columns here are whoever's
        // sex IS at least known; Total is the true full unclassified
        // headcount (can exceed Male+Female when sex is unknown too), so
        // this row's Total plus every bracket's Total above always equals
        // $now->count() from the Persons (Now) figure at the top -- if it
        // doesn't, that's a real bug to investigate, not something to
        // paper over here.
        $unclassified = $now->filter(
            fn ($e) => ! in_array($e->age_bracket, array_keys($brackets), true) || ! in_array($e->sex, ['male', 'female'], true)
        );
        $unclassifiedMale = $unclassified->where('sex', 'male')->count();
        $unclassifiedFemale = $unclassified->where('sex', 'female')->count();
        $ageTotal['male'] += $unclassifiedMale;
        $ageTotal['female'] += $unclassifiedFemale;
        $row = $this->writeDataRow($sheet, $row, 'Not Yet Classified (missing age/sex data)', $unclassifiedMale, $unclassifiedFemale, totalOverride: $unclassified->count());

        $this->writeDataRow($sheet, $row, 'Total', $ageTotal['male'], $ageTotal['female'], bold: true, totalOverride: $ageTotal['male'] + $ageTotal['female'] + ($unclassified->count() - $unclassifiedMale - $unclassifiedFemale));
        $row += 2;

        // -- Sectoral Group --
        $row = $this->writeSectionHeader($sheet, $row, 'Sectoral Group', ['Male', 'Female', 'Total']);

        // 'group' is the EvacuationCenterQuickCount::SECTORAL_GROUPS enum
        // value this category is stored under -- deliberately a separate
        // key from the array index (the Evacuee flag name) since the two
        // naming conventions don't match 1:1 (e.g. is_pwd vs 'pwd').
        // 'flag' => null marks a category with no per-Evacuee flag at all
        // to fall back to (see the class docblock's CHILD-HEADED /
        // SINGLE-HEADED FAMILY note).
        $sectors = [
            ['label' => 'Persons with Disability/ies (PWDs)', 'group' => 'pwd', 'flag' => 'is_pwd'],
            ['label' => 'Child-Headed Family/ies', 'group' => 'child_headed_family', 'flag' => null],
            ['label' => 'Single-Headed Family/ies', 'group' => 'single_headed_family', 'flag' => null],
            ['label' => 'Solo Parent/s', 'group' => 'solo_parent', 'flag' => 'is_solo_parent'],
            ['label' => 'Pregnant Women', 'group' => 'pregnant_women', 'flag' => 'is_pregnant'],
            ['label' => 'Lactating Mother/s', 'group' => 'lactating_mothers', 'flag' => 'is_lactating'],
            ['label' => '4Ps Beneficiary/ies', 'group' => 'four_ps_beneficiary', 'flag' => 'is_4ps_beneficiary'],
            ['label' => 'Indigenous Peoples (IPs)', 'group' => 'indigenous_peoples', 'flag' => 'is_indigenous_person'],
        ];

        $sectorTotal = ['male' => 0, 'female' => 0];
        foreach ($sectors as $meta) {
            if ($quickCount) {
                $reported = $quickCount->sectoralGroups->firstWhere('sectoral_group', $meta['group']);
                $male = $reported->male_count ?? 0;
                $female = $reported->female_count ?? 0;
            } elseif ($meta['flag']) {
                $male = $now->filter(fn ($e) => $e->{$meta['flag']} && $e->sex === 'male')->count();
                $female = $now->filter(fn ($e) => $e->{$meta['flag']} && $e->sex === 'female')->count();
            } else {
                $male = 0;
                $female = 0;
            }
            $sectorTotal['male'] += $male;
            $sectorTotal['female'] += $female;
            $row = $this->writeDataRow($sheet, $row, $meta['label'], $male, $female);
        }
        $this->writeDataRow($sheet, $row, 'Total', $sectorTotal['male'], $sectorTotal['female'], bold: true);
        $row += 2;

        // -- Available Facilities --
        $sheet->setCellValue("A{$row}", 'Available Facilities');
        $sheet->setCellValue("D{$row}", 'Total');
        $sheet->setCellValue("F{$row}", 'Concerns and Needs');
        $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
        $row++;

        // Maps this system's 19 granular facility_type values down to the
        // 12 categories the EC Information Board template actually asks
        // for (e.g. both latrine types combine into "Toilet").
        $facilities = $center->facilities()->get()->keyBy('facility_type');
        $qty = fn (...$types) => collect($types)->sum(fn ($t) => optional($facilities->get($t))->quantity ?? 0);
        $notes = fn (...$types) => collect($types)
            ->map(fn ($t) => optional($facilities->get($t))->concerns_and_needs)
            ->filter()
            ->implode('; ');

        $boardFacilities = [
            ['Information/Help Desk', $qty('camp_management_desk', 'info_board'), $notes('camp_management_desk', 'info_board')],
            ['Community Kitchen', $qty('community_kitchen'), $notes('community_kitchen')],
            ['Bathing Area', $qty('bathing_area_male', 'bathing_area_female', 'bathing_area_common'), $notes('bathing_area_male', 'bathing_area_female', 'bathing_area_common')],
            ['Toilet', $qty('toilet_male', 'toilet_female', 'toilet_common', 'latrine_compost_pit', 'latrine_sealed'), $notes('toilet_male', 'toilet_female', 'toilet_common')],
            ['Handwashing Facility', $qty('handwashing_facility'), $notes('handwashing_facility')],
            ['Laundry Space', $qty('laundry_space'), $notes('laundry_space')],
            ['Women-Friendly Space', $qty('women_friendly_space'), $notes('women_friendly_space')],
            ['Child-Friendly Space', $qty('child_friendly_space'), $notes('child_friendly_space')],
            ['Health Facility', $qty('health_facility'), $notes('health_facility')],
            ['Prayer Room', $qty('prayer_room'), $notes('prayer_room')],
            ['Livestock Area', $qty('livestock_area'), $notes('livestock_area')],
            ['Storage Area', $qty('storage_area'), $notes('storage_area')],
        ];

        foreach ($boardFacilities as [$label, $total, $note]) {
            $sheet->setCellValue("A{$row}", $label);
            $sheet->setCellValue("D{$row}", $total);
            $sheet->setCellValue("F{$row}", $note);
            $row++;
        }
        $row++;

        // -- Contact details --
        $sheet->setCellValue("A{$row}", 'CONTACT DETAILS OF EVACUATION CENTER MANAGEMENT');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row++;
        $sheet->setCellValue("A{$row}", 'Camp Manager:');
        $sheet->setCellValue("B{$row}", $center->camp_manager_name ?? '');
        $sheet->setCellValue("F{$row}", 'Contact No.:');
        $sheet->setCellValue("H{$row}", $center->camp_manager_contact ?? '');

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $outputPath = storage_path('app/reports/ec_board_'.$center->id.'_'.now()->format('Ymd_His').'.xlsx');
        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($outputPath);

        return $outputPath;
    }

    private function writeSectionHeader($sheet, int $row, string $title, array $columns): int
    {
        $sheet->setCellValue("A{$row}", $title);
        $letters = ['D', 'F', 'H'];
        foreach ($columns as $i => $col) {
            $sheet->setCellValue("{$letters[$i]}{$row}", $col);
        }
        $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
        $sheet->getStyle("A{$row}:H{$row}")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E9EFF9');

        return $row + 1;
    }

    // $totalOverride: normally the Total column is just $male + $female,
    // but the "Not Yet Classified" row (see generate()) needs a total that
    // can legitimately be HIGHER than male+female -- some of that row's
    // people have unknown sex too, so they're in the true headcount but
    // not in either the male or female column.
    private function writeDataRow($sheet, int $row, string $label, int $male, int $female, bool $bold = false, ?int $totalOverride = null): int
    {
        $sheet->setCellValue("A{$row}", $label);
        $sheet->setCellValue("D{$row}", $male);
        $sheet->setCellValue("F{$row}", $female);
        $sheet->setCellValue("H{$row}", $totalOverride ?? ($male + $female));
        if ($bold) {
            $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
        }

        return $row + 1;
    }
}
