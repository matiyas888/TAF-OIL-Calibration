<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AllowableLossProduct;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        AllowableLossProduct::query()->updateOrCreate(
            ['item_number' => 'P07-0001'],
            [
                'product_name' => 'JET-A1',
                'allowable_loss_percent' => 0.1200,
                'price_per_litre' => 319.7600,
            ]
        );

        AllowableLossProduct::query()->updateOrCreate(
            ['item_number' => 'P01-0001'],
            [
                'product_name' => 'Automotive Gas Oil (AGO)',
                'allowable_loss_percent' => 0.0800,
                'price_per_litre' => 85.5000,
            ]
        );

        AllowableLossProduct::query()->updateOrCreate(
            ['item_number' => 'P02-0001'],
            [
                'product_name' => 'Regular Gasoline (MGR)',
                'allowable_loss_percent' => 0.0800,
                'price_per_litre' => 92.0000,
            ]
        );

        AllowableLossProduct::query()->updateOrCreate(
            ['item_number' => 'P03-0001'],
            [
                'product_name' => 'Kerosene (KERO)',
                'allowable_loss_percent' => 0.0800,
                'price_per_litre' => 78.0000,
            ]
        );

        $this->call(TruckSeeder::class);
        $this->call(LookupTablesSeeder::class);
    }
}
