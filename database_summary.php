<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name != 'migrations'");

echo "\n=======================================================\n";
echo "           DATABASE TABLES & RECORD COUNTS             \n";
echo "=======================================================\n";

foreach ($tables as $t) {
    $count = DB::table($t->name)->count();
    echo sprintf("%-28s : %6d records\n", $t->name, $count);
}

echo "=======================================================\n\n";

// Display sample products
echo "--- PRODUCTS & TARIFFS (allowable_loss_products) ---\n";
foreach (DB::table('allowable_loss_products')->get() as $p) {
    echo sprintf(" [%s] %-28s | Loss: %.2f%% | Price: %8.2f ETB\n",
        $p->item_number, $p->product_name, $p->allowable_loss_percent, $p->price_per_litre);
}

// Display sample trucks
echo "\n--- TRUCKS & ASSIGNED DRIVERS (truck_headers) ---\n";
foreach (DB::table('truck_headers')->get() as $trk) {
    echo sprintf(" Plate: %-15s | Carrier: %-22s | Driver: %-16s | Licence: %-12s | Cap: %6.0f L\n",
        $trk->plate_no, $trk->carrier_name, $trk->driver_name, $trk->driver_licence_no, $trk->total_capacity);
}

// Display calibrations count per truck
echo "\n--- CALIBRATION POINTS (truck_calibrations) ---\n";
$calibs = DB::table('truck_calibrations')->select('plate_no', DB::raw('count(*) as count'))->groupBy('plate_no')->get();
foreach ($calibs as $c) {
    echo sprintf(" Plate: %-15s | Calibrations: %d points\n", $c->plate_no, $c->count);
}

// Display saved transfer orders
echo "\n--- TRANSFER ORDERS (transfer_orders) ---\n";
foreach (DB::table('transfer_orders')->get() as $to) {
    echo sprintf(" Order: %-18s | Plate: %-15s | Driver: %-16s | Loss: %6.1f L | Debit: %6.1f L (%8.2f ETB)\n",
        $to->to_number, $to->plate_no, $to->driver_name ?? 'N/A', $to->loss_volume, $to->debit_volume, $to->debit_amount);
}
