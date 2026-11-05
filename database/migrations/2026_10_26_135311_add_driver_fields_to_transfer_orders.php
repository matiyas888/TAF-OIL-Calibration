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
            $table->string('driver_name', 100)->nullable()->after('delivery_order_no');
            $table->string('driver_licence_no', 50)->nullable()->after('driver_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transfer_orders', function (Blueprint $table) {
            $table->dropColumn(['driver_name', 'driver_licence_no']);
        });
    }
};
