<?php

namespace App\Console\Commands;

use App\Models\SystemLog;
use App\Models\WeatherReading;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportWeatherReadings extends Command
{
    protected $signature = 'weather:import {path : Path to the CSV file, relative to project root or absolute}
                            {--sample : Mark every imported row as non-authoritative sample/test data, NOT real PAGASA data}';

    protected $description = 'Imports historical daily/weekly weather readings (rainfall, wind speed) from a PAGASA CSV export for SARIMA forecasting.';

    public function handle(): int
    {
        $path = $this->argument('path');
        $absolutePath = str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path)
            ? $path
            : base_path($path);

        if (! is_readable($absolutePath)) {
            $this->error("Cannot read file: {$absolutePath}");

            return self::FAILURE;
        }

        $handle = fopen($absolutePath, 'r');
        $header = fgetcsv($handle);

        if ($header === false) {
            $this->error('File is empty or not a valid CSV.');
            fclose($handle);

            return self::FAILURE;
        }

        // Matched by header name (case-insensitive, trimmed) rather than
        // fixed column position -- real PAGASA exports won't necessarily
        // list columns in the same order this system's own sample fixture
        // does.
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);
        $dateIdx = array_search('date', $header, true);
        $rainfallIdx = array_search('rainfall_mm', $header, true);
        $windIdx = array_search('wind_speed_kph', $header, true);
        $stationIdx = array_search('station', $header, true);

        if ($dateIdx === false) {
            $this->error("CSV must have a 'date' column. Found columns: ".implode(', ', $header));
            fclose($handle);

            return self::FAILURE;
        }

        $isSample = (bool) $this->option('sample');
        $sourceFile = basename($absolutePath);

        $imported = 0;
        $updated = 0;
        $skippedInvalid = 0;
        $skippedProtected = 0;
        $rowNumber = 1; // header was row 1

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            $rawDate = trim($row[$dateIdx] ?? '');
            $date = null;
            try {
                $date = Carbon::createFromFormat('Y-m-d', $rawDate)->startOfDay();
            } catch (\Throwable $e) {
                // fall through, $date stays null
            }

            if (! $date) {
                $this->warn("Row {$rowNumber}: invalid date '{$rawDate}' (expected YYYY-MM-DD) -- skipped.");
                $skippedInvalid++;

                continue;
            }

            $rainfallRaw = $rainfallIdx !== false ? trim($row[$rainfallIdx] ?? '') : '';
            $windRaw = $windIdx !== false ? trim($row[$windIdx] ?? '') : '';

            $rainfall = $this->parseNullableNonNegative($rainfallRaw);
            $wind = $this->parseNullableNonNegative($windRaw);

            if ($rainfallRaw !== '' && $rainfall === null) {
                $this->warn("Row {$rowNumber}: invalid rainfall_mm value '{$rainfallRaw}' -- skipped.");
                $skippedInvalid++;

                continue;
            }

            if ($windRaw !== '' && $wind === null) {
                $this->warn("Row {$rowNumber}: invalid wind_speed_kph value '{$windRaw}' -- skipped.");
                $skippedInvalid++;

                continue;
            }

            if ($rainfall === null && $wind === null) {
                $this->warn("Row {$rowNumber}: both rainfall_mm and wind_speed_kph are blank -- skipped.");
                $skippedInvalid++;

                continue;
            }

            $station = $stationIdx !== false ? trim($row[$stationIdx] ?? '') : '';
            $station = $station === '' ? 'default' : $station;

            $existing = WeatherReading::where('reading_date', $date->toDateString())
                ->where('station', $station)
                ->first();

            // Sample imports never overwrite real PAGASA data already on
            // file -- protects against a stale test re-import clobbering
            // the real thing once it arrives.
            if ($isSample && $existing && ! $existing->is_sample) {
                $this->warn("Row {$rowNumber} ({$date->toDateString()}/{$station}): already has real PAGASA data on file; refusing to overwrite with --sample data.");
                $skippedProtected++;

                continue;
            }

            $wasNew = ! $existing;

            WeatherReading::updateOrCreate(
                ['reading_date' => $date->toDateString(), 'station' => $station],
                [
                    'rainfall_mm' => $rainfall,
                    'wind_speed_kph' => $wind,
                    'is_sample' => $isSample,
                    'source_file' => $sourceFile,
                ]
            );

            $wasNew ? $imported++ : $updated++;
        }

        fclose($handle);

        $this->newLine();
        $this->table(
            ['Imported', 'Updated', 'Skipped (invalid)', 'Skipped (protected)'],
            [[$imported, $updated, $skippedInvalid, $skippedProtected]]
        );

        SystemLog::create([
            'user_id' => null,
            'action' => 'weather_readings.imported',
            'description' => sprintf(
                '%s import from "%s": %d imported, %d updated, %d skipped invalid, %d skipped protected.',
                $isSample ? 'Sample/test' : 'Real PAGASA',
                $sourceFile,
                $imported,
                $updated,
                $skippedInvalid,
                $skippedProtected
            ),
            'ip_address' => null,
        ]);

        if ($imported === 0 && $updated === 0) {
            $this->error('No rows were successfully imported.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function parseNullableNonNegative(string $raw): ?float
    {
        if ($raw === '') {
            return null;
        }

        if (! is_numeric($raw) || (float) $raw < 0) {
            return null;
        }

        return (float) $raw;
    }
}
