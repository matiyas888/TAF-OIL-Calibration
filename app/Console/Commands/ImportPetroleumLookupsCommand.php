<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\LookupTableImporter;
use Illuminate\Console\Command;

class ImportPetroleumLookupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'petroleum:import
                            {type : Type of data to import (trucks, density, factor, products)}
                            {file : Path to CSV file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import petroleum lookup tables, truck calibrations, and products from CSV';

    /**
     * Execute the console command.
     */
    public function handle(LookupTableImporter $importer): int
    {
        $type = strtolower($this->argument('type'));
        $file = $this->argument('file');

        if (! file_exists($file)) {
            $this->error("File does not exist: {$file}");

            return self::FAILURE;
        }

        $this->info("Importing {$type} from {$file}...");

        try {
            $count = match ($type) {
                'trucks', 'truck', 'calibrations' => $importer->importTruckCalibrations($file),
                'density', 'density20', 'density_correct_20' => $importer->importDensityCorrect20($file),
                'factor', 'factor20', 'vcf', 'factor_correct_20' => $importer->importFactorCorrect20($file),
                'products', 'allowable_loss', 'allowable' => $importer->importAllowableLossProducts($file),
                default => throw new \InvalidArgumentException("Unknown import type: {$type}. Supported types: trucks, density, factor, products."),
            };

            $this->info("Successfully imported {$count} records into {$type}.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Import failed: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
