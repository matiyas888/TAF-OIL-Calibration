<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AllowableLossProduct;
use App\Models\DensityCorrect20;
use App\Models\FactorCorrect20;
use App\Models\TruckCalibration;
use App\Models\TruckHeader;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Service to import petroleum lookup and calibration tables from CSV exports.
 */
final class LookupTableImporter
{
    /**
     * Import truck calibration points from CSV.
     * Expected columns: plate_no, compartment_no, dip_mm, litres.
     *
     * @param  string  $filePath  Absolute path to the CSV file.
     * @return int Number of records imported.
     */
    public function importTruckCalibrations(string $filePath): int
    {
        $handle = $this->openCsv($filePath);
        $header = $this->readHeader($handle);

        $plateIdx = $this->findColumn($header, ['plateno', 'plate_no', 'plate']);
        $compIdx = $this->findColumn($header, ['compartment_no', 'compartment', 'comp', 'sub_comp']);
        $dipIdx = $this->findColumn($header, ['dip_mm', 'dip', 'dipmm', 'depth']);
        $litresIdx = $this->findColumn($header, ['litres', 'liter', 'liters', 'volume', 'capacity']);

        $records = [];
        $truckPlates = [];
        $count = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (empty($row) || ! isset($row[$plateIdx])) {
                    continue;
                }

                $plate = trim((string) $row[$plateIdx]);
                if ($plate === '') {
                    continue;
                }

                $comp = (int) ($row[$compIdx] ?? 1);
                $dip = (int) ($row[$dipIdx] ?? 0);
                $litres = (float) ($row[$litresIdx] ?? 0.0);

                $records[] = [
                    'plate_no' => $plate,
                    'compartment_no' => $comp,
                    'dip_mm' => $dip,
                    'litres' => $litres,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $truckPlates[$plate][$comp] = max($truckPlates[$plate][$comp] ?? 0, $litres);
                $count++;

                if (count($records) >= 500) {
                    TruckCalibration::query()->insert($records);
                    $records = [];
                }
            }

            if (! empty($records)) {
                TruckCalibration::query()->insert($records);
            }

            // Sync TruckHeaders
            foreach ($truckPlates as $plate => $compartments) {
                $totalCap = array_sum($compartments);
                TruckHeader::query()->updateOrCreate(
                    ['plate_no' => $plate],
                    [
                        'compartment_count' => count($compartments),
                        'total_capacity' => $totalCap,
                    ]
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);

        return $count;
    }

    /**
     * Import Density_Correct_20 lookup table from CSV.
     * Supports long format (observed_density, temperature, density_20)
     * and matrix format (temperature columns, observed density rows).
     *
     * @param  string  $filePath  Absolute path to the CSV file.
     * @return int Number of records imported.
     */
    public function importDensityCorrect20(string $filePath): int
    {
        $handle = $this->openCsv($filePath);
        $header = $this->readHeader($handle);

        $densIdx = $this->findColumnNullable($header, ['observed_density', 'obs_density', 'density', 'sg']);
        $tempIdx = $this->findColumnNullable($header, ['temperature', 'temp', 'celsius', 't']);
        $d20Idx = $this->findColumnNullable($header, ['density_20', 'd20', 'rho20', 'dens20']);

        $records = [];
        $count = 0;

        DB::beginTransaction();
        try {
            if ($densIdx !== null && $tempIdx !== null && $d20Idx !== null) {
                // Long format
                while (($row = fgetcsv($handle)) !== false) {
                    if (empty($row) || ! isset($row[$densIdx])) {
                        continue;
                    }

                    $records[] = [
                        'observed_density' => (float) $row[$densIdx],
                        'temperature' => (float) $row[$tempIdx],
                        'density_20' => (float) $row[$d20Idx],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $count++;

                    if (count($records) >= 500) {
                        DensityCorrect20::query()->insert($records);
                        $records = [];
                    }
                }
            } else {
                // Matrix format: first column is density, subsequent columns are temperatures
                while (($row = fgetcsv($handle)) !== false) {
                    if (empty($row)) {
                        continue;
                    }

                    $obsDens = (float) $row[0];
                    for ($c = 1; $c < count($row); $c++) {
                        if (! isset($header[$c]) || ! is_numeric($header[$c])) {
                            continue;
                        }

                        $temp = (float) $header[$c];
                        $d20 = (float) $row[$c];

                        $records[] = [
                            'observed_density' => $obsDens,
                            'temperature' => $temp,
                            'density_20' => $d20,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $count++;

                        if (count($records) >= 500) {
                            DensityCorrect20::query()->insert($records);
                            $records = [];
                        }
                    }
                }
            }

            if (! empty($records)) {
                DensityCorrect20::query()->insert($records);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);

        return $count;
    }

    /**
     * Import Factor_Correct_20 lookup table from CSV.
     * Supports long format (temperature, density_20, factor)
     * and matrix format.
     *
     * @param  string  $filePath  Absolute path to the CSV file.
     * @return int Number of records imported.
     */
    public function importFactorCorrect20(string $filePath): int
    {
        $handle = $this->openCsv($filePath);
        $header = $this->readHeader($handle);

        $tempIdx = $this->findColumnNullable($header, ['temperature', 'temp', 'celsius', 't']);
        $d20Idx = $this->findColumnNullable($header, ['density_20', 'd20', 'rho20', 'density']);
        $factorIdx = $this->findColumnNullable($header, ['factor', 'vcf', 'factor_20', 'vcf20']);

        $records = [];
        $count = 0;

        DB::beginTransaction();
        try {
            if ($tempIdx !== null && $d20Idx !== null && $factorIdx !== null) {
                // Long format
                while (($row = fgetcsv($handle)) !== false) {
                    if (empty($row) || ! isset($row[$tempIdx])) {
                        continue;
                    }

                    $records[] = [
                        'temperature' => (float) $row[$tempIdx],
                        'density_20' => (float) $row[$d20Idx],
                        'factor' => (float) $row[$factorIdx],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $count++;

                    if (count($records) >= 500) {
                        FactorCorrect20::query()->insert($records);
                        $records = [];
                    }
                }
            } else {
                // Matrix format: first column is temperature, other columns are density20
                while (($row = fgetcsv($handle)) !== false) {
                    if (empty($row)) {
                        continue;
                    }

                    $temp = (float) $row[0];
                    for ($c = 1; $c < count($row); $c++) {
                        if (! isset($header[$c]) || ! is_numeric($header[$c])) {
                            continue;
                        }

                        $d20 = (float) $header[$c];
                        $factor = (float) $row[$c];

                        $records[] = [
                            'temperature' => $temp,
                            'density_20' => $d20,
                            'factor' => $factor,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                        $count++;

                        if (count($records) >= 500) {
                            FactorCorrect20::query()->insert($records);
                            $records = [];
                        }
                    }
                }
            }

            if (! empty($records)) {
                FactorCorrect20::query()->insert($records);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);

        return $count;
    }

    /**
     * Import allowable loss products from CSV.
     *
     * @param  string  $filePath  Absolute path to the CSV file.
     * @return int Number of records imported.
     */
    public function importAllowableLossProducts(string $filePath): int
    {
        $handle = $this->openCsv($filePath);
        $header = $this->readHeader($handle);

        $itemIdx = $this->findColumn($header, ['item_number', 'item', 'itemno', 'fmtitemno']);
        $nameIdx = $this->findColumnNullable($header, ['product_name', 'desc', 'description', 'name']);
        $lossIdx = $this->findColumnNullable($header, ['allowable_loss_percent', 'loss_percent', 'percent', 'loss']);
        $priceIdx = $this->findColumnNullable($header, ['price_per_litre', 'unitprice', 'price', 'unit_cost', 'rate']);
        $openIdx = $this->findColumnNullable($header, ['opening_balance', 'opening', 'open']);
        $adjIdx = $this->findColumnNullable($header, ['adjustment', 'adjestment', 'adj']);
        $pipeIdx = $this->findColumnNullable($header, ['pipeline_stock', 'pipeline', 'pipe']);

        $count = 0;

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (empty($row) || ! isset($row[$itemIdx])) {
                    continue;
                }

                $item = trim((string) $row[$itemIdx]);
                if ($item === '') {
                    continue;
                }

                $name = $nameIdx !== null ? trim((string) ($row[$nameIdx] ?? '')) : $item;
                $lossPct = $lossIdx !== null ? (float) ($row[$lossIdx] ?? 0.08) : 0.08;
                // If expressed as 0.08 vs 8, ensure normalized percentage
                $price = $priceIdx !== null ? (float) ($row[$priceIdx] ?? 0.0) : 0.0;
                $open = $openIdx !== null ? (float) ($row[$openIdx] ?? 0.0) : 0.0;
                $adj = $adjIdx !== null ? (float) ($row[$adjIdx] ?? 0.0) : 0.0;
                $pipe = $pipeIdx !== null ? (float) ($row[$pipeIdx] ?? 0.0) : 0.0;

                AllowableLossProduct::query()->updateOrCreate(
                    ['item_number' => $item],
                    [
                        'product_name' => $name !== '' ? $name : $item,
                        'allowable_loss_percent' => $lossPct,
                        'price_per_litre' => $price,
                        'opening_balance' => $open,
                        'adjustment' => $adj,
                        'pipeline_stock' => $pipe,
                    ]
                );
                $count++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }

        fclose($handle);

        return $count;
    }

    /**
     * @return resource
     */
    private function openCsv(string $filePath)
    {
        if (! file_exists($filePath) || ! is_readable($filePath)) {
            throw new InvalidArgumentException("File not found or unreadable: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            throw new RuntimeException("Failed to open file: {$filePath}");
        }

        return $handle;
    }

    /**
     * Read and normalize CSV headers (strip BOM, lowercase).
     *
     * @param  resource  $handle
     * @return array<int, string>
     */
    private function readHeader($handle): array
    {
        $header = fgetcsv($handle);
        if ($header === false) {
            throw new RuntimeException('CSV file is empty.');
        }

        return array_map(function (string $col): string {
            $col = preg_replace('/[\x{FEFF}]/u', '', $col) ?? $col;

            return strtolower(trim($col));
        }, $header);
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<string>  $candidates
     */
    private function findColumn(array $header, array $candidates): int
    {
        $idx = $this->findColumnNullable($header, $candidates);
        if ($idx === null) {
            throw new InvalidArgumentException('Missing required column in CSV. Expected one of: '.implode(', ', $candidates));
        }

        return $idx;
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<string>  $candidates
     */
    private function findColumnNullable(array $header, array $candidates): ?int
    {
        foreach ($candidates as $cand) {
            $idx = array_search(strtolower($cand), $header, true);
            if ($idx !== false) {
                return (int) $idx;
            }
        }

        return null;
    }
}
