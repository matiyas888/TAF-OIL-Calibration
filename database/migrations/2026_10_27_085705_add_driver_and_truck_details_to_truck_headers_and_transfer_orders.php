<?php

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
        Schema::table('truck_headers', function (Blueprint $table) {
            $table->string('truck_no', 30)->nullable()->after('plate_no');
            $table->string('carrier_name', 100)->nullable()->after('trailer_plate_no');
            $table->string('driver_name', 100)->nullable()->after('carrier_name');
            $table->string('driver_licence_no', 50)->nullable()->after('driver_name');
        });

        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->string('trailer_plate_no', 30)->nullable()->after('plate_no');
            $table->string('carrier_name', 100)->nullable()->after('trailer_plate_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('truck_headers', function (Blueprint $table) {
            $table->dropColumn(['truck_no', 'carrier_name', 'driver_name', 'driver_licence_no']);
        });

        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->dropColumn(['trailer_plate_no', 'carrier_name']);
        });
    }
};
