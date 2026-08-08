<?php
// Deterministic generator for pagasa_sample_daily.csv, checked in alongside
// its output so the fixture is reproducible, not "mystery data". Run with:
//   php database/samples/generate_sample_weather.php
//
// Formula, for day index d (0-based, starting 2026-04-01):
//   rainfall_mm = max(0, round(20 + 15*sin(2*pi*d/7) + gaussian_noise(0, 4), 1))
//   wind_speed_kph = max(0, round(15 + 8*sin(2*pi*d/7 + 1.2) + gaussian_noise(0, 3), 1))
// A 7-day sine wave gives the fixture a clear weekly-seasonal signal for
// SARIMA to detect during pipeline testing, plus Gaussian noise so it isn't
// a perfectly clean sinusoid. Seeded (mt_srand(42)) for reproducibility --
// rerunning this script always produces byte-identical output.
// THIS IS FABRICATED TEST DATA, NOT REAL PAGASA RECORDS. Import it only
// with `php artisan weather:import ... --sample`.

mt_srand(42);

function gaussianNoise(float $mean, float $stddev): float
{
    // Box-Muller transform
    $u1 = mt_rand() / mt_getrandmax();
    $u2 = mt_rand() / mt_getrandmax();
    $z0 = sqrt(-2 * log($u1)) * cos(2 * M_PI * $u2);

    return $mean + $z0 * $stddev;
}

$startDate = new DateTime('2026-04-01');
$rows = [];
$rows[] = ['date', 'rainfall_mm', 'wind_speed_kph', 'station'];

for ($d = 0; $d < 120; $d++) {
    $date = (clone $startDate)->modify("+{$d} days")->format('Y-m-d');

    $rainfall = 20 + 15 * sin(2 * M_PI * $d / 7) + gaussianNoise(0, 4);
    $rainfall = max(0, round($rainfall, 1));

    $wind = 15 + 8 * sin(2 * M_PI * $d / 7 + 1.2) + gaussianNoise(0, 3);
    $wind = max(0, round($wind, 1));

    $rows[] = [$date, $rainfall, $wind, ''];
}

$fp = fopen(__DIR__ . '/pagasa_sample_daily.csv', 'w');
foreach ($rows as $row) {
    fputcsv($fp, $row);
}
fclose($fp);

echo 'Generated ' . (count($rows) - 1) . " rows.\n";
