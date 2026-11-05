<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\TransferOrderCalculator;
use Illuminate\Console\Command;

class ValidateHistoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'petroleum:validate-history
                            {file : Path to CSV file containing historical transfer orders (tblTODaily_history)}
                            {--limit=0 : Limit the number of records to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate PHP calculation rules against historical Access records';

    /**
     * Execute the console command.
     */
    public function handle(TransferOrderCalculator $calculator): int
    {
        $file = $this->argument('file');
        $limit = (int) $this->option('limit');

        if (! file_exists($file) || ! is_readable($file)) {
            $this->error("Cannot read history file: {$file}");

            return self::FAILURE;
        }

        $handle = fopen($file, 'r');
        if ($handle === false) {
            $this->error("Failed to open file: {$file}");

            return self::FAILURE;
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            $this->error('History CSV is empty.');
            fclose($handle);

            return self::FAILURE;
        }

        $headerMap = array_flip(array_map(fn (string $c): string => strtolower(trim(preg_replace('/[\x{FEFF}]/u', '', $c) ?? $c)), $header));

        $totalRows = 0;
        $meteredMatches = 0;
        $observedMatches = 0;
        $lossMatches = 0;
        $allowedLossMatches = 0;
        $debitVolumeMatches = 0;
        $debitAmountMatches = 0;
        $debitDiscrepancies = [];

        $this->info("Analyzing historical records from: {$file}...");

        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row)) {
                continue;
            }

            $totalRows++;
            if ($limit > 0 && $totalRows > $limit) {
                $totalRows--;
                break;
            }

            // Extract fields with fallbacks
            $startVol20 = (float) ($row[$headerMap['txtvol20_0'] ?? $headerMap['start_vol20'] ?? 0] ?? 0.0);
            $finalVol20Saved = (float) ($row[$headerMap['txtvol20_1'] ?? $headerMap['final_vol20'] ?? 0] ?? 0.0);
            $lossSaved = (float) ($row[$headerMap['loss'] ?? $headerMap['vol_loss'] ?? 0] ?? 0.0);
            $percent = (float) ($row[$headerMap['txtpercent'] ?? $headerMap['percent'] ?? 0] ?? 0.08);
            $allowedSaved = (float) ($row[$headerMap['allowed_loss'] ?? $headerMap['txtallowable'] ?? 0] ?? 0.0);
            $debitVolSaved = (float) ($row[$headerMap['debit_vol'] ?? $headerMap['txtdebitvol'] ?? 0] ?? 0.0);
            $price = (float) ($row[$headerMap['price'] ?? $headerMap['txtprice'] ?? 0] ?? 0.0);
            $debitAmtSaved = (float) ($row[$headerMap['debit_amt'] ?? $headerMap['txtdebitamt'] ?? 0] ?? 0.0);

            $mFrom1 = (float) ($row[$headerMap['frommet1'] ?? $headerMap['from_meter1'] ?? 0] ?? 0.0);
            $mTo1 = (float) ($row[$headerMap['tomet1'] ?? $headerMap['to_meter1'] ?? 0] ?? 0.0);
            $drained1 = (float) ($row[$headerMap['drained1'] ?? 0] ?? 0.0);
            $mFrom2 = (float) ($row[$headerMap['frommet2'] ?? $headerMap['from_meter2'] ?? 0] ?? 0.0);
            $mTo2 = (float) ($row[$headerMap['tomet2'] ?? $headerMap['to_meter2'] ?? 0] ?? 0.0);
            $drained2 = (float) ($row[$headerMap['drained2'] ?? 0] ?? 0.0);
            $factor = (float) ($row[$headerMap['factor'] ?? $headerMap['txtfactor'] ?? 0] ?? 1.0);
            $obsLitres = (float) ($row[$headerMap['observed_litres'] ?? 0] ?? 0.0);

            // 1. Metered volume verification
            $meteredTotal = ($mTo1 - $mFrom1 + $drained1) + ($mTo2 - $mFrom2 + $drained2);
            $meteredVol20 = round($meteredTotal * $factor, 2);
            $obsVol20 = round($obsLitres * $factor, 2);

            if (abs($meteredVol20 - $finalVol20Saved) <= 1.0) {
                $meteredMatches++;
            } elseif (abs($obsVol20 - $finalVol20Saved) <= 1.0) {
                $observedMatches++;
            }

            // 2. Loss formula verification: |Vol20_start - Vol20_final|
            $calcLoss = $calculator->calculateLoss($startVol20, $finalVol20Saved);
            if (abs($calcLoss - $lossSaved) <= 1.0) {
                $lossMatches++;
            }

            // 3. Allowed loss: round(Vol20_start * percent / 100)
            $calcAllowed = (float) $calculator->calculateAllowedLoss($startVol20, $percent);
            if (abs($calcAllowed - $allowedSaved) <= 1.0) {
                $allowedLossMatches++;
            }

            // 4. Debit volume: <= 150 L with >= 0.5 buffer, or > 150 L full loss
            $calcDebitVol = $calculator->calculateDebitVolume($calcLoss, $startVol20, $percent);
            if (abs($calcDebitVol - $debitVolSaved) <= 1.0) {
                $debitVolumeMatches++;
            } else {
                if (count($debitDiscrepancies) < 5) {
                    $debitDiscrepancies[] = [
                        'row' => $totalRows,
                        'loss' => $calcLoss,
                        'saved_debit' => $debitVolSaved,
                        'calc_debit' => $calcDebitVol,
                        'allowed' => $calcAllowed,
                    ];
                }
            }

            // 5. Debit amount: Voldebit * price
            $calcDebitAmt = $calculator->calculateDebitAmount($calcDebitVol, $price);
            if (abs($calcDebitAmt - $debitAmtSaved) <= 1.0) {
                $debitAmountMatches++;
            }
        }

        fclose($handle);

        if ($totalRows === 0) {
            $this->warn('No records were processed.');

            return self::SUCCESS;
        }

        $pct = fn (int $n): string => number_format(($n / $totalRows) * 100, 2).'%';

        $this->table(
            ['Validation Metric', 'Matches', 'Total Records', 'Match Rate'],
            [
                ['Metered Volume Rule (Item 3)', $meteredMatches, $totalRows, $pct($meteredMatches)],
                ['Observed Volume Rule (Fallback)', $observedMatches, $totalRows, $pct($observedMatches)],
                ['Loss Formula |start - final| (Item 4)', $lossMatches, $totalRows, $pct($lossMatches)],
                ['Allowed Loss round(Vol * pct / 100)', $allowedLossMatches, $totalRows, $pct($allowedLossMatches)],
                ['Debit Volume (Items 6)', $debitVolumeMatches, $totalRows, $pct($debitVolumeMatches)],
                ['Debit Amount Vol * Price (Item 7)', $debitAmountMatches, $totalRows, $pct($debitAmountMatches)],
            ]
        );

        if (! empty($debitDiscrepancies)) {
            $this->warn('Sample Debit Volume Discrepancies (investigating manual overrides or edge cases):');
            foreach ($debitDiscrepancies as $d) {
                $this->line("  Row {$d['row']}: Loss={$d['loss']}L, Allowed={$d['allowed']}L | Saved Debit={$d['saved_debit']}L vs Calc Debit={$d['calc_debit']}L");
            }
        }

        return self::SUCCESS;
    }
}
