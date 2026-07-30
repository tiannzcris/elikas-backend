<?php

namespace App\Services\Reports;

use App\Models\EvacuationCenter;
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
 * KNOWN GAP, same as the DROMIC Region V report: "Child-Headed Family" and
 * "Single-Headed Family" rows are shown with a value of 0 -- this system
 * does not track those two sectoral flags (see DromicRegionVReportService's
 * docblock for the same note).
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
        $fourPsCount = $allEvacuees->where('is_4ps_beneficiary', true)->count();

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
        $this->writeDataRow($sheet, $row, 'Total', $ageTotal['male'], $ageTotal['female'], bold: true);
        $row += 2;

        // -- Sectoral Group --
        $row = $this->writeSectionHeader($sheet, $row, 'Sectoral Group', ['Male', 'Female', 'Total']);

        $sectors = [
            'is_pwd' => 'Persons with Disability/ies (PWDs)',
            '__child_headed' => 'Child-Headed Family/ies',    // not tracked -- always 0, see class docblock
            '__single_headed' => 'Single-Headed Family/ies',  // not tracked -- always 0, see class docblock
            'is_solo_parent' => 'Solo Parent/s',
            'is_pregnant' => 'Pregnant Women',
            'is_lactating' => 'Lactating Mother/s',
            'is_4ps_beneficiary' => '4Ps Beneficiary/ies',
            'is_indigenous_person' => 'Indigenous Peoples (IPs)',
        ];

        $sectorTotal = ['male' => 0, 'female' => 0];
        foreach ($sectors as $flag => $label) {
            if (str_starts_with($flag, '__')) {
                $male = 0;
                $female = 0;
            } else {
                $male = $now->filter(fn ($e) => $e->{$flag} && $e->sex === 'male')->count();
                $female = $now->filter(fn ($e) => $e->{$flag} && $e->sex === 'female')->count();
            }
            $sectorTotal['male'] += $male;
            $sectorTotal['female'] += $female;
            $row = $this->writeDataRow($sheet, $row, $label, $male, $female);
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

    private function writeDataRow($sheet, int $row, string $label, int $male, int $female, bool $bold = false): int
    {
        $sheet->setCellValue("A{$row}", $label);
        $sheet->setCellValue("D{$row}", $male);
        $sheet->setCellValue("F{$row}", $female);
        $sheet->setCellValue("H{$row}", $male + $female);
        if ($bold) {
            $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
        }

        return $row + 1;
    }
}
