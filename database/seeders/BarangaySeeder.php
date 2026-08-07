<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Seeder;

/**
 * Barangays are fixed reference data (the actual administrative divisions
 * of Ligao City), not records staff create day-to-day -- this is why this
 * was always meant to be seeded, not entered one at a time through a web
 * form. Names and PSGC correspondence codes are sourced directly from the
 * Philippine Statistics Authority's official PSGC database
 * (psa.gov.ph/classification/psgc/barangays/0500508000), current as of 31
 * July 2025. Note the code sequence skips 050508041 -- this isn't a typo,
 * it reflects a real barangay merger reported in PSA's own First Quarter
 * 2026 PSGC update ("Merging of Two Barangays"), so the list here already
 * reflects the current, correct set of 55.
 *
 * centroid_latitude/centroid_longitude are deliberately left null -- PSA's
 * table doesn't provide per-barangay centroid coordinates, and guessing at
 * coordinates would mean fabricating geographic data. Fill these in later
 * via the actual map/GIS tools if precise centroids are needed.
 */
class BarangaySeeder extends Seeder
{
    public function run(): void
    {
        $barangays = [
            ['Abella', '050508001'], ['Allang', '050508002'], ['Amtic', '050508003'],
            ['Bacong', '050508004'], ['Bagumbayan', '050508005'], ['Balanac', '050508006'],
            ['Baligang', '050508007'], ['Barayong', '050508008'], ['Basag', '050508009'],
            ['Batang', '050508010'], ['Bay', '050508011'], ['Binanowan', '050508012'],
            ['Binatagan', '050508013'], ['Bobonsuran', '050508014'], ['Bonga', '050508015'],
            ['Busac', '050508016'], ['Busay', '050508017'], ['Cabarian', '050508018'],
            ['Calzada', '050508019'], ['Catburawan', '050508020'], ['Cavasi', '050508021'],
            ['Culliat', '050508022'], ['Dunao', '050508023'], ['Francia', '050508024'],
            ['Guilid', '050508025'], ['Herrera', '050508026'], ['Layon', '050508027'],
            ['Macalidong', '050508028'], ['Mahaba', '050508029'], ['Malama', '050508030'],
            ['Maonon', '050508031'], ['Nasisi', '050508032'], ['Nabonton', '050508033'],
            ['Oma-oma', '050508034'], ['Palapas', '050508035'], ['Pandan', '050508036'],
            ['Paulba', '050508037'], ['Paulog', '050508038'], ['Pinamaniquian', '050508039'],
            ['Pinit', '050508040'], ['Ranao-ranao', '050508042'], ['San Vicente', '050508043'],
            ['Santa Cruz', '050508044'], ['Tagpo', '050508045'], ['Tambo', '050508046'],
            ['Tandarura', '050508047'], ['Tastas', '050508048'], ['Tinago', '050508049'],
            ['Tinampo', '050508050'], ['Tiongson', '050508051'], ['Tomolin', '050508052'],
            ['Tuburan', '050508053'], ['Tula-tula Grande', '050508054'], ['Tula-tula Peque', '050508055'],
            ['Tupas', '050508056'],
        ];

        foreach ($barangays as [$name, $psgcCode]) {
            // firstOrCreate so re-running this seeder is always safe --
            // never duplicates or resets a barangay that already exists.
            Barangay::firstOrCreate(['name' => $name], ['psgc_code' => $psgcCode]);
        }

        $this->command->info(count($barangays).' barangays ready.');
    }
}
