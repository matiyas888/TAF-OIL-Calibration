<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DensityCorrect20;
use App\Models\FactorCorrect20;
use App\Services\DensityCalc;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LookupTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Generates standard 361-row ASTM 54B/D lookup grids (19 x 19)
     * corresponding to MS Access Density_Correct_20 and Factor_Correct_20.
     */
    public function run(): void
    {
        if (DensityCorrect20::query()->count() >= 361 && FactorCorrect20::query()->count() >= 361) {
            return;
        }

        // Standard density grid (19 values: 720 to 860 kg/m3)
        $densities = [
            720.0, 725.0, 730.0, 735.0, 740.0, 745.0, 750.0, 755.0,
            760.0, 770.0, 780.0, 785.0, 790.0, 800.0, 810.0, 820.0,
            830.0, 840.0, 850.0,
        ];

        // Standard temperature grid (19 values: 12.0 to 30.0 °C)
        $temperatures = [
            12.0, 13.0, 14.0, 15.0, 16.0, 17.0, 18.0, 19.0, 19.5, 20.0,
            20.5, 21.0, 22.0, 23.0, 24.0, 25.0, 26.0, 28.0, 30.0,
        ];

        $densityRows = [];
        $factorRows = [];

        foreach ($densities as $obsDens) {
            foreach ($temperatures as $temp) {
                $d20 = DensityCalc::density20($obsDens, $temp);
                $factor = DensityCalc::vcf20($d20, $temp);

                $densityRows[] = [
                    'observed_density' => $obsDens,
                    'temperature' => $temp,
                    'density_20' => $d20,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $factorRows[] = [
                    'temperature' => $temp,
                    'density_20' => $d20,
                    'factor' => $factor,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::transaction(function () use ($densityRows, $factorRows) {
            DensityCorrect20::query()->truncate();
            FactorCorrect20::query()->truncate();

            foreach (array_chunk($densityRows, 100) as $chunk) {
                DensityCorrect20::query()->insert($chunk);
            }

            foreach (array_chunk($factorRows, 100) as $chunk) {
                FactorCorrect20::query()->insert($chunk);
            }
        });
    }
}
