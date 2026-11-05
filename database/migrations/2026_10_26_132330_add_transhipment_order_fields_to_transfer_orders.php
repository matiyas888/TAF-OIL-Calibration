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
        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->string('reference', 50)->nullable()->after('to_number');
            $table->string('ship_from_storage_point', 100)->nullable()->after('plate_no');
            $table->date('ship_from_date')->nullable()->after('ship_from_storage_point');
            $table->string('ship_from_loc', 30)->nullable()->after('ship_from_date');

            $table->string('ship_to_depot', 100)->nullable()->after('ship_from_loc');
            $table->date('ship_to_date')->nullable()->after('ship_to_depot');
            $table->string('ship_to_loc', 30)->nullable()->after('ship_to_date');

            $table->decimal('origin_vol20', 12, 2)->nullable()->after('start_vol20');

            $table->decimal('sg_15_dj_1', 8, 4)->nullable()->after('final_vol20');
            $table->decimal('sg_15_dj_2', 8, 4)->nullable()->after('sg_15_dj_1');
            $table->decimal('sg_15_diff', 8, 4)->nullable()->after('sg_15_dj_2');

            $table->string('insurance_status', 30)->default('No')->after('debit_amount');
            $table->string('via', 100)->nullable()->after('insurance_status');
            $table->string('side_no', 50)->nullable()->after('via');
            $table->string('transhipment_order_no', 50)->nullable()->after('side_no');
            $table->string('delivery_order_no', 50)->nullable()->after('transhipment_order_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->dropColumn([
                'reference',
                'ship_from_storage_point',
                'ship_from_date',
                'ship_from_loc',
                'ship_to_depot',
                'ship_to_date',
                'ship_to_loc',
                'origin_vol20',
                'sg_15_dj_1',
                'sg_15_dj_2',
                'sg_15_diff',
                'insurance_status',
                'via',
                'side_no',
                'transhipment_order_no',
                'delivery_order_no',
            ]);
        });
    }
};
