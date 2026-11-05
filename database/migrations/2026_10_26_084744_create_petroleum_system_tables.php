<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Truck Headers
        Schema::create('truck_headers', function (Blueprint $table) {
            $table->id();
            $table->string('plate_no', 30)->unique();
            $table->string('trailer_plate_no', 30)->nullable();
            $table->unsignedInteger('compartment_count')->default(1);
            $table->decimal('total_capacity', 12, 2)->default(0);
            $table->timestamps();
        });

        // 2. Truck Sub Calibration (dip mm -> litres)
        Schema::create('truck_calibrations', function (Blueprint $table) {
            $table->id();
            $table->string('plate_no', 30)->index();
            $table->unsignedInteger('compartment_no');
            $table->unsignedInteger('dip_mm');
            $table->decimal('litres', 12, 2);
            $table->timestamps();

            $table->index(['plate_no', 'compartment_no', 'dip_mm'], 'idx_truck_comp_dip');
        });

        // 3. Density Correction at 20°C (361 rows lookup table)
        Schema::create('density_correct_20', function (Blueprint $table) {
            $table->id();
            $table->decimal('observed_density', 8, 2);
            $table->decimal('temperature', 6, 2);
            $table->decimal('density_20', 8, 2);
            $table->timestamps();

            $table->index(['observed_density', 'temperature'], 'idx_density_temp');
        });

        // 4. Volume Conversion Factor (VCF) at 20°C (361 rows lookup table)
        Schema::create('factor_correct_20', function (Blueprint $table) {
            $table->id();
            $table->decimal('temperature', 6, 2);
            $table->decimal('density_20', 8, 2);
            $table->decimal('factor', 10, 6);
            $table->timestamps();

            $table->index(['temperature', 'density_20'], 'idx_factor_temp_dens');
        });

        // 5. Allowable Loss & Pricing Products
        Schema::create('allowable_loss_products', function (Blueprint $table) {
            $table->id();
            $table->string('item_number', 50)->unique();
            $table->string('product_name', 150);
            $table->decimal('allowable_loss_percent', 6, 4)->default(0.0800); // 0.08% or 0.12%
            $table->decimal('price_per_litre', 12, 4)->default(0.0000);
            $table->decimal('opening_balance', 14, 2)->default(0.00);
            $table->decimal('adjustment', 14, 2)->default(0.00);
            $table->decimal('pipeline_stock', 14, 2)->default(0.00);
            $table->timestamps();
        });

        // 6. Transfer Orders (TO)
        Schema::create('transfer_orders', function (Blueprint $table) {
            $table->id();
            $table->string('to_number', 50)->unique();
            $table->date('order_date');
            $table->string('plate_no', 30)->index();
            $table->string('product_item_number', 50)->index();

            // Start measurements
            $table->decimal('start_observed_density', 8, 2);
            $table->decimal('start_temperature', 6, 2);
            $table->decimal('start_density_20', 8, 2);
            $table->decimal('start_factor', 10, 6);
            $table->decimal('start_observed_litres', 12, 2);
            $table->decimal('start_vol20', 12, 2);

            // Final measurements (Meters / Observed)
            $table->boolean('use_metered')->default(true);
            $table->decimal('meter_reading_from_1', 12, 2)->nullable();
            $table->decimal('meter_reading_to_1', 12, 2)->nullable();
            $table->decimal('drained_1', 12, 2)->nullable();
            $table->decimal('meter_reading_from_2', 12, 2)->nullable();
            $table->decimal('meter_reading_to_2', 12, 2)->nullable();
            $table->decimal('drained_2', 12, 2)->nullable();
            $table->decimal('metered_total', 12, 2)->nullable();

            $table->decimal('final_observed_litres', 12, 2)->nullable();
            $table->decimal('final_observed_density', 8, 2)->nullable();
            $table->decimal('final_temperature', 6, 2)->nullable();
            $table->decimal('final_density_20', 8, 2)->nullable();
            $table->decimal('final_factor', 10, 6);
            $table->decimal('final_vol20', 12, 2);

            // Loss & Debit Calculations (stored per TO)
            $table->decimal('loss_volume', 12, 2)->default(0.00);
            $table->decimal('allowable_loss_percent', 6, 4)->default(0.0800);
            $table->integer('allowed_loss_volume')->default(0);
            $table->decimal('allowed_loss_2dp', 12, 2)->default(0.00);
            $table->decimal('debit_volume', 12, 2)->default(0.00);
            $table->decimal('price_per_litre', 12, 4)->default(0.0000);
            $table->decimal('debit_amount', 14, 2)->default(0.00);

            // Sage 300 Integration Status
            $table->string('status', 30)->default('draft');
            $table->boolean('sage_posted')->default(false);
            $table->string('sage_receipt_number', 50)->nullable();
            $table->timestamp('sage_posted_at')->nullable();

            $table->timestamps();
        });

        // 7. TO Compartment Dips
        Schema::create('transfer_order_compartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_order_id')->constrained('transfer_orders')->cascadeOnDelete();
            $table->unsignedInteger('compartment_no');
            $table->unsignedInteger('dip_mm');
            $table->integer('litres');
            $table->timestamps();
        });

        // 8. Daily Stock Reconciliation
        Schema::create('daily_stocks', function (Blueprint $table) {
            $table->id();
            $table->date('stock_date')->index();
            $table->string('product_item_number', 50)->index();
            $table->decimal('opening_balance', 14, 2)->default(0.00);
            $table->decimal('receipts', 14, 2)->default(0.00);
            $table->decimal('returns', 14, 2)->default(0.00);
            $table->decimal('available', 14, 2)->default(0.00);
            $table->decimal('sales', 14, 2)->default(0.00);
            $table->decimal('adjustment', 14, 2)->default(0.00);
            $table->decimal('closing_balance', 14, 2)->default(0.00);
            $table->decimal('physical_tank_stock', 14, 2)->default(0.00);
            $table->decimal('pipeline_stock', 14, 2)->default(0.00);
            $table->decimal('total_physical_stock', 14, 2)->default(0.00);
            $table->decimal('operational_variance', 14, 2)->default(0.00);
            $table->timestamps();

            $table->unique(['stock_date', 'product_item_number'], 'idx_date_product');
        });

        // 9. Product Variances (Daily / Monthly)
        Schema::create('product_variances', function (Blueprint $table) {
            $table->id();
            $table->date('report_date')->index();
            $table->string('product_item_number', 50)->index();
            $table->decimal('start_vol20', 14, 2)->default(0.00);
            $table->decimal('final_vol20', 14, 2)->default(0.00);
            $table->decimal('debit_volume', 14, 2)->default(0.00);
            $table->decimal('net_loss', 14, 2)->default(0.00);
            $table->decimal('net_loss_percent', 8, 4)->default(0.0000);
            $table->decimal('sales_volume', 14, 2)->default(0.00);
            $table->decimal('temperature_loss', 14, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variances');
        Schema::dropIfExists('daily_stocks');
        Schema::dropIfExists('transfer_order_compartments');
        Schema::dropIfExists('transfer_orders');
        Schema::dropIfExists('allowable_loss_products');
        Schema::dropIfExists('factor_correct_20');
        Schema::dropIfExists('density_correct_20');
        Schema::dropIfExists('truck_calibrations');
        Schema::dropIfExists('truck_headers');
    }
};
