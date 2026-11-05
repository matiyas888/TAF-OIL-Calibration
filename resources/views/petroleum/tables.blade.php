<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Tables & Lookups - TAF Oil Ethiopia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .access-table-th {
            @apply px-3 py-2 text-left text-[11px] font-bold text-slate-700 uppercase bg-slate-100 border-b border-slate-300;
        }
        .access-table-td {
            @apply px-3 py-2 text-xs border-b border-slate-200 text-slate-800;
        }
    </style>
</head>
<body class="bg-slate-200 min-h-screen text-slate-800 font-sans antialiased p-3 md:p-6">

    <div class="max-w-7xl mx-auto space-y-4">

        <!-- Top Navigation Bar -->
        <div class="bg-slate-900 text-white px-4 py-3 rounded-lg shadow flex flex-col sm:flex-row justify-between items-center gap-3">
            <div class="flex items-center space-x-3">
                <i class="fa-solid fa-database text-amber-400 text-lg"></i>
                <div>
                    <h1 class="text-sm font-black uppercase tracking-wider text-amber-400">TAF OIL ETHIOPIA - PETROLEUM DATABASE TABLES</h1>
                    <p class="text-[11px] text-slate-400">Reverse-engineered MS Access SQLite Database Structure & Lookups</p>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('petroleum.index') }}" class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded font-bold text-xs flex items-center space-x-1 shadow transition">
                    <i class="fa-solid fa-arrow-left mr-1"></i>
                    <span>Back to Transhipment Order UI</span>
                </a>
            </div>
        </div>

        <!-- Table Selector Tabs -->
        <div class="bg-white p-2 rounded-lg shadow-sm border border-slate-300 flex flex-wrap gap-1.5 text-xs">
            <a href="?table=truck_headers"
                class="px-3 py-2 rounded font-bold flex items-center space-x-1.5 transition {{ $activeTable === 'truck_headers' ? 'bg-amber-600 text-white shadow' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                <i class="fa-solid fa-truck"></i>
                <span>truck_headers</span>
                <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTable === 'truck_headers' ? 'bg-amber-800 text-white' : 'bg-slate-300 text-slate-700' }}">
                    {{ $tableCounts['truck_headers'] }}
                </span>
            </a>

            <a href="?table=truck_calibrations"
                class="px-3 py-2 rounded font-bold flex items-center space-x-1.5 transition {{ $activeTable === 'truck_calibrations' ? 'bg-amber-600 text-white shadow' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                <i class="fa-solid fa-ruler"></i>
                <span>truck_calibrations</span>
                <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTable === 'truck_calibrations' ? 'bg-amber-800 text-white' : 'bg-slate-300 text-slate-700' }}">
                    {{ $tableCounts['truck_calibrations'] }}
                </span>
            </a>

            <a href="?table=allowable_loss_products"
                class="px-3 py-2 rounded font-bold flex items-center space-x-1.5 transition {{ $activeTable === 'allowable_loss_products' ? 'bg-amber-600 text-white shadow' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                <i class="fa-solid fa-gas-pump"></i>
                <span>allowable_loss_products</span>
                <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTable === 'allowable_loss_products' ? 'bg-amber-800 text-white' : 'bg-slate-300 text-slate-700' }}">
                    {{ $tableCounts['allowable_loss_products'] }}
                </span>
            </a>

            <a href="?table=transfer_orders"
                class="px-3 py-2 rounded font-bold flex items-center space-x-1.5 transition {{ $activeTable === 'transfer_orders' ? 'bg-amber-600 text-white shadow' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                <i class="fa-solid fa-file-invoice"></i>
                <span>transfer_orders</span>
                <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTable === 'transfer_orders' ? 'bg-amber-800 text-white' : 'bg-slate-300 text-slate-700' }}">
                    {{ $tableCounts['transfer_orders'] }}
                </span>
            </a>

            <a href="?table=debit_notes"
                class="px-3 py-2 rounded font-bold flex items-center space-x-1.5 transition {{ $activeTable === 'debit_notes' ? 'bg-rose-700 text-white shadow' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                <i class="fa-solid fa-receipt"></i>
                <span>debit_notes</span>
                <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTable === 'debit_notes' ? 'bg-rose-900 text-white' : 'bg-rose-200 text-rose-800 font-bold' }}">
                    {{ $tableCounts['debit_notes'] }}
                </span>
            </a>

            <a href="?table=density_correct_20"
                class="px-3 py-2 rounded font-bold flex items-center space-x-1.5 transition {{ $activeTable === 'density_correct_20' ? 'bg-amber-600 text-white shadow' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                <i class="fa-solid fa-temperature-half"></i>
                <span>density_correct_20</span>
                <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTable === 'density_correct_20' ? 'bg-amber-800 text-white' : 'bg-slate-300 text-slate-700' }}">
                    {{ $tableCounts['density_correct_20'] }}
                </span>
            </a>

            <a href="?table=factor_correct_20"
                class="px-3 py-2 rounded font-bold flex items-center space-x-1.5 transition {{ $activeTable === 'factor_correct_20' ? 'bg-amber-600 text-white shadow' : 'bg-slate-100 hover:bg-slate-200 text-slate-700' }}">
                <i class="fa-solid fa-chart-line"></i>
                <span>factor_correct_20</span>
                <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] {{ $activeTable === 'factor_correct_20' ? 'bg-amber-800 text-white' : 'bg-slate-300 text-slate-700' }}">
                    {{ $tableCounts['factor_correct_20'] }}
                </span>
            </a>
        </div>

        <!-- Session Status Flash Alert -->
        @if(session('status'))
            <div class="bg-emerald-100 border border-emerald-400 text-emerald-900 px-4 py-3 rounded-lg text-xs font-semibold flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                    <span>{{ session('status') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-950 font-bold text-base">&times;</button>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-rose-100 border border-rose-400 text-rose-900 px-4 py-3 rounded-lg text-xs font-semibold shadow-sm">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Table Content Card -->
        <div class="bg-white rounded-lg shadow border border-slate-300 overflow-hidden">

            <!-- 1. TRUCK HEADERS -->
            @if($activeTable === 'truck_headers')
                <div class="p-3 bg-slate-100 border-b border-slate-200 flex justify-between items-center text-xs">
                    <div>
                        <strong class="text-slate-800 text-sm">Table: truck_headers</strong>
                        <span class="text-slate-500 ml-2">(MS Access legacy: <code>tblmaintbl_truck</code>)</span>
                    </div>
                    <span class="text-slate-600 font-mono font-semibold">{{ $trucks->count() }} registered vehicles</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead>
                            <tr>
                                <th class="access-table-th">Plate No</th>
                                <th class="access-table-th">Truck No</th>
                                <th class="access-table-th">Trailer Plate</th>
                                <th class="access-table-th">Carrier / Transporter</th>
                                <th class="access-table-th">Assigned Driver</th>
                                <th class="access-table-th">Licence No</th>
                                <th class="access-table-th text-center">Comps</th>
                                <th class="access-table-th text-right">Capacity (L)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($trucks as $t)
                                <tr class="hover:bg-amber-50/50">
                                    <td class="access-table-td font-mono font-bold text-slate-900">{{ $t->plate_no }}</td>
                                    <td class="access-table-td font-mono">{{ $t->truck_no ?: '-' }}</td>
                                    <td class="access-table-td font-mono">{{ $t->trailer_plate_no ?: '-' }}</td>
                                    <td class="access-table-td font-semibold text-slate-700">
                                        <span class="px-2 py-0.5 rounded text-[11px] {{ str_contains($t->carrier_name, 'TAF') ? 'bg-indigo-100 text-indigo-800 font-bold' : 'bg-slate-100 text-slate-800' }}">
                                            {{ $t->carrier_name }}
                                        </span>
                                    </td>
                                    <td class="access-table-td font-bold text-slate-900">{{ $t->driver_name }}</td>
                                    <td class="access-table-td font-mono text-slate-600">{{ $t->driver_licence_no }}</td>
                                    <td class="access-table-td text-center font-mono">{{ $t->compartment_count }}</td>
                                    <td class="access-table-td text-right font-mono font-bold text-slate-900">{{ number_format($t->total_capacity, 0) }} L</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- 2. TRUCK CALIBRATIONS -->
            @if($activeTable === 'truck_calibrations')
                <div class="p-3 bg-slate-100 border-b border-slate-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-2 text-xs">
                    <div>
                        <strong class="text-slate-800 text-sm">Table: truck_calibrations</strong>
                        <span class="text-slate-500 ml-2">(MS Access legacy: <code>tblsubtbl_truck</code>)</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-slate-600 font-mono font-semibold">{{ $calibrations->count() }} calibration points</span>
                        <button type="button" onclick="document.getElementById('addCalibModal').classList.toggle('hidden')"
                            class="px-2.5 py-1 bg-amber-600 hover:bg-amber-500 text-white font-bold rounded shadow-sm text-xs flex items-center space-x-1">
                            <i class="fa-solid fa-plus mr-1"></i>
                            <span>Add Calibration</span>
                        </button>
                    </div>
                </div>

                <!-- Calibration Entry Forms (Single & Bulk) -->
                <div id="addCalibModal" class="p-4 bg-amber-50/60 border-b border-amber-200 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                    <!-- Form 1: Quick Add Single Point -->
                    <form method="POST" action="{{ route('petroleum.calibrations.store') }}" class="p-3 bg-white rounded border border-amber-300 shadow-sm space-y-2">
                        @csrf
                        <div class="font-bold text-slate-800 flex items-center space-x-1.5 pb-1 border-b border-slate-200 text-xs">
                            <i class="fa-solid fa-ruler-vertical text-amber-600"></i>
                            <span>Add Single Calibration Point</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Truck Plate</label>
                                <input type="text" name="plate_no" list="truckPlateList" placeholder="e.g. A03843/*29988" required
                                    class="w-full px-2 py-1 border border-slate-300 rounded font-mono text-xs focus:ring-1 focus:ring-amber-500">
                                <datalist id="truckPlateList">
                                    @foreach($trucks as $trk)
                                        <option value="{{ $trk->plate_no }}">{{ $trk->driver_name }} ({{ $trk->carrier_name }})</option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Compartment #</label>
                                <select name="compartment_no" required class="w-full px-2 py-1 border border-slate-300 rounded font-mono text-xs">
                                    <option value="1">Comp 1</option>
                                    <option value="2">Comp 2</option>
                                    <option value="3">Comp 3</option>
                                    <option value="4">Comp 4</option>
                                    <option value="5">Comp 5</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Dip (mm)</label>
                                <input type="number" step="0.1" name="dip_mm" placeholder="e.g. 1500" required
                                    class="w-full px-2 py-1 border border-slate-300 rounded font-mono text-xs focus:ring-1 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Volume (Litres)</label>
                                <input type="number" step="0.1" name="litres" placeholder="e.g. 10250" required
                                    class="w-full px-2 py-1 border border-slate-300 rounded font-mono text-xs focus:ring-1 focus:ring-amber-500">
                            </div>
                        </div>
                        <div class="text-right pt-1">
                            <button type="submit" class="px-3 py-1 bg-amber-600 hover:bg-amber-700 text-white font-bold rounded text-xs shadow-sm">
                                <i class="fa-solid fa-save mr-1"></i> Save Point
                            </button>
                        </div>
                    </form>

                    <!-- Form 2: Bulk Import Calibration Chart -->
                    <form method="POST" action="{{ route('petroleum.calibrations.bulk') }}" class="p-3 bg-white rounded border border-amber-300 shadow-sm space-y-2">
                        @csrf
                        <div class="font-bold text-slate-800 flex items-center space-x-1.5 pb-1 border-b border-slate-200 text-xs">
                            <i class="fa-solid fa-file-csv text-emerald-600"></i>
                            <span>Bulk Import Calibration Table (CSV / Paste)</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Truck Plate</label>
                                <input type="text" name="plate_no" list="truckPlateList" placeholder="e.g. A03843/*29988" required
                                    class="w-full px-2 py-1 border border-slate-300 rounded font-mono text-xs focus:ring-1 focus:ring-amber-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Compartment #</label>
                                <select name="compartment_no" required class="w-full px-2 py-1 border border-slate-300 rounded font-mono text-xs">
                                    <option value="1">Comp 1</option>
                                    <option value="2">Comp 2</option>
                                    <option value="3">Comp 3</option>
                                    <option value="4">Comp 4</option>
                                    <option value="5">Comp 5</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Paste Dip(mm) and Litres (one per line, comma or space separated)</label>
                            <textarea name="bulk_data" rows="3" placeholder="100, 750&#10;200, 1500&#10;300, 2250&#10;1000, 7500" required
                                class="w-full px-2 py-1 border border-slate-300 rounded font-mono text-xs focus:ring-1 focus:ring-amber-500"></textarea>
                        </div>
                        <div class="text-right pt-0.5">
                            <button type="submit" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded text-xs shadow-sm">
                                <i class="fa-solid fa-upload mr-1"></i> Import Calibration Chart
                            </button>
                        </div>
                    </form>
                </div>

                <div class="overflow-x-auto max-h-[600px] overflow-y-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="sticky top-0 bg-slate-100 z-10">
                            <tr>
                                <th class="access-table-th">Truck Plate</th>
                                <th class="access-table-th text-center">Compartment No</th>
                                <th class="access-table-th text-right">Dip Measurement (mm)</th>
                                <th class="access-table-th text-right">Volume (Litres)</th>
                                <th class="access-table-th">Interpolation Formula</th>
                                <th class="access-table-th text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono">
                            @foreach($calibrations as $c)
                                <tr class="hover:bg-amber-50/50">
                                    <td class="access-table-td font-bold text-slate-900">{{ $c->plate_no }}</td>
                                    <td class="access-table-td text-center">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-800">
                                            Comp {{ $c->compartment_no }}
                                        </span>
                                    </td>
                                    <td class="access-table-td text-right font-bold text-amber-700">{{ $c->dip_mm }} mm</td>
                                    <td class="access-table-td text-right font-bold text-emerald-700">{{ number_format($c->litres, 0) }} L</td>
                                    <td class="access-table-td text-[11px] text-slate-500 font-sans">
                                        Straight-line linear interpolation: round(y0 + (x - x0) / (x1 - x0) * (y1 - y0))
                                    </td>
                                    <td class="access-table-td text-center">
                                        <form method="POST" action="{{ route('petroleum.calibrations.destroy', $c->id) }}" onsubmit="return confirm('Delete calibration point for {{ $c->plate_no }} (Comp {{ $c->compartment_no }}, {{ $c->dip_mm }}mm)?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-800 text-xs" title="Delete Point">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- 3. PRODUCTS & ALLOWABLE LOSS -->
            @if($activeTable === 'allowable_loss_products')
                <div class="p-3 bg-slate-100 border-b border-slate-200 flex justify-between items-center text-xs">
                    <div>
                        <strong class="text-slate-800 text-sm">Table: allowable_loss_products</strong>
                        <span class="text-slate-500 ml-2">(MS Access legacy: <code>TBLALLOWABLE_LOSS_PRODUCT</code> & <code>_1</code>)</span>
                    </div>
                    <span class="text-slate-600 font-mono font-semibold">{{ $products->count() }} fuel products</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead>
                            <tr>
                                <th class="access-table-th">Product Code</th>
                                <th class="access-table-th">Product Name</th>
                                <th class="access-table-th text-right">Allowable Loss %</th>
                                <th class="access-table-th text-right">Price per Litre (ETB)</th>
                                <th class="access-table-th text-right">Opening Balance</th>
                                <th class="access-table-th text-right">Pipeline Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($products as $p)
                                <tr class="hover:bg-amber-50/50">
                                    <td class="access-table-td font-mono font-bold text-indigo-900">{{ $p->item_number }}</td>
                                    <td class="access-table-td font-bold text-slate-900">{{ $p->product_name }}</td>
                                    <td class="access-table-td text-right font-mono font-bold text-emerald-700">
                                        {{ number_format($p->allowable_loss_percent, 2) }}%
                                    </td>
                                    <td class="access-table-td text-right font-mono font-bold text-slate-900">
                                        {{ number_format($p->price_per_litre, 2) }} ETB
                                    </td>
                                    <td class="access-table-td text-right font-mono text-slate-600">
                                        {{ number_format($p->opening_balance, 2) }} L
                                    </td>
                                    <td class="access-table-td text-right font-mono text-slate-600">
                                        {{ number_format($p->pipeline_stock, 2) }} L
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- 4. TRANSFER ORDERS -->
            @if($activeTable === 'transfer_orders')
                <div class="p-3 bg-slate-100 border-b border-slate-200 flex justify-between items-center text-xs">
                    <div>
                        <strong class="text-slate-800 text-sm">Table: transfer_orders</strong>
                        <span class="text-slate-500 ml-2">(MS Access legacy: <code>tblTODaily_history</code>)</span>
                    </div>
                    <span class="text-slate-600 font-mono font-semibold">{{ $orders->count() }} recorded orders</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead>
                            <tr>
                                <th class="access-table-th">TO Number</th>
                                <th class="access-table-th">Reference</th>
                                <th class="access-table-th">Date</th>
                                <th class="access-table-th">Plate No</th>
                                <th class="access-table-th">Driver</th>
                                <th class="access-table-th text-right">Start Vol @ 20°C</th>
                                <th class="access-table-th text-right">Discharge Vol @ 20°C</th>
                                <th class="access-table-th text-right">Loss (L)</th>
                                <th class="access-table-th text-right">Debit (L)</th>
                                <th class="access-table-th text-right">Debit Amount</th>
                                <th class="access-table-th text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($orders as $o)
                                <tr class="hover:bg-amber-50/50">
                                    <td class="access-table-td font-mono font-bold text-indigo-900">{{ $o->to_number }}</td>
                                    <td class="access-table-td font-mono text-slate-600">{{ $o->reference ?: '-' }}</td>
                                    <td class="access-table-td">{{ $o->order_date }}</td>
                                    <td class="access-table-td font-mono font-bold">{{ $o->plate_no }}</td>
                                    <td class="access-table-td font-bold text-slate-800">{{ $o->driver_name ?: '-' }}</td>
                                    <td class="access-table-td text-right font-mono">{{ number_format($o->start_vol20, 0) }} L</td>
                                    <td class="access-table-td text-right font-mono">{{ number_format($o->final_vol20, 0) }} L</td>
                                    <td class="access-table-td text-right font-mono font-bold text-rose-700">{{ number_format($o->loss_volume, 0) }} L</td>
                                    <td class="access-table-td text-right font-mono font-bold {{ $o->debit_volume > 0 ? 'text-rose-700' : 'text-emerald-700' }}">
                                        {{ number_format($o->debit_volume, 0) }} L
                                    </td>
                                    <td class="access-table-td text-right font-mono font-bold text-amber-800">
                                        {{ number_format($o->debit_amount, 2) }} ETB
                                    </td>
                                    <td class="access-table-td text-center">
                                        <a href="{{ route('petroleum.debit-note', $o->id) }}" class="px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-100 text-indigo-800 hover:bg-indigo-200">
                                            Debit Note
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- 5. DEBIT NOTES REGISTER -->
            @if($activeTable === 'debit_notes')
                <div class="p-3 bg-rose-50 border-b border-rose-200 flex flex-col md:flex-row justify-between items-start md:items-center gap-2 text-xs">
                    <div>
                        <strong class="text-rose-900 text-sm flex items-center gap-1.5">
                            <i class="fa-solid fa-receipt text-rose-600"></i>
                            Transporter Debit Notes Register
                        </strong>
                        <span class="text-slate-500 ml-2">(Official Claims for Transporter Shortages exceeding allowance)</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-rose-800 font-mono font-bold">{{ $debitNotes->count() }} debit notes issued</span>
                        <button type="button" onclick="document.getElementById('manualDebitModal').classList.toggle('hidden')"
                            class="px-2.5 py-1 bg-rose-700 hover:bg-rose-800 text-white font-bold rounded shadow-sm text-xs flex items-center space-x-1">
                            <i class="fa-solid fa-plus mr-1"></i>
                            <span>Issue Manual Debit Note</span>
                        </button>
                    </div>
                </div>

                <!-- Form: Issue Manual Debit Note Modal -->
                <div id="manualDebitModal" class="hidden p-4 bg-rose-50/70 border-b border-rose-300">
                    <form method="POST" action="{{ route('petroleum.debit-notes.manual') }}" class="p-4 bg-white rounded-lg border border-rose-300 shadow-sm space-y-3 max-w-4xl mx-auto text-xs">
                        @csrf
                        <div class="font-bold text-rose-900 flex items-center justify-between pb-2 border-b border-slate-200">
                            <div class="flex items-center space-x-1.5">
                                <i class="fa-solid fa-file-invoice-dollar text-rose-600"></i>
                                <span>Create & Issue Carrier Debit Note</span>
                            </div>
                            <span class="text-[10px] text-slate-500 font-normal">Direct short-delivery recovery</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Truck Plate No</label>
                                <input type="text" name="plate_no" list="truckPlateList" placeholder="e.g. A03843/*29988" required
                                    class="w-full px-2 py-1.5 border border-slate-300 rounded font-mono text-xs focus:ring-1 focus:ring-rose-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Transporter / Carrier</label>
                                <input type="text" name="carrier_name" placeholder="e.g. TAF Transport"
                                    class="w-full px-2 py-1.5 border border-slate-300 rounded text-xs focus:ring-1 focus:ring-rose-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Driver Name</label>
                                <input type="text" name="driver_name" placeholder="e.g. Kebede Tessema"
                                    class="w-full px-2 py-1.5 border border-slate-300 rounded text-xs focus:ring-1 focus:ring-rose-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Reference / Waybill (FDC)</label>
                                <input type="text" name="reference" placeholder="e.g. FDC-99995"
                                    class="w-full px-2 py-1.5 border border-slate-300 rounded font-mono text-xs focus:ring-1 focus:ring-rose-500">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Product Code</label>
                                <select name="product_item_number" required class="w-full px-2 py-1.5 border border-slate-300 rounded font-mono text-xs">
                                    @foreach($products as $prod)
                                        <option value="{{ $prod->item_number }}">{{ $prod->item_number }} - {{ $prod->item_description }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Transaction Date</label>
                                <input type="date" name="order_date" value="{{ date('Y-m-d') }}" required
                                    class="w-full px-2 py-1.5 border border-slate-300 rounded font-mono text-xs focus:ring-1 focus:ring-rose-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 bg-slate-50 p-2.5 rounded border border-slate-200">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Origin Vol @ 20°C (L)</label>
                                <input type="number" step="0.1" name="origin_vol20" value="48000" required
                                    class="w-full px-2 py-1 border border-slate-300 rounded font-mono text-xs text-right">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Total Loss (L)</label>
                                <input type="number" step="0.1" name="loss_volume" placeholder="e.g. 180" required
                                    class="w-full px-2 py-1 border border-slate-300 rounded font-mono text-xs text-right font-bold text-rose-700">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Chargeable Debit Vol (L)</label>
                                <input type="number" step="0.1" name="debit_volume" placeholder="e.g. 180" required
                                    class="w-full px-2 py-1 border border-slate-300 rounded font-mono text-xs text-right font-bold text-rose-700">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-600 uppercase mb-0.5">Price / Litre (ETB)</label>
                                <input type="number" step="0.001" name="price_per_litre" value="319.760" required
                                    class="w-full px-2 py-1 border border-slate-300 rounded font-mono text-xs text-right font-bold">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-2 pt-1">
                            <button type="button" onclick="document.getElementById('manualDebitModal').classList.add('hidden')"
                                class="px-3 py-1 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded text-xs">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-1.5 bg-rose-700 hover:bg-rose-800 text-white font-bold rounded text-xs shadow-sm flex items-center space-x-1">
                                <i class="fa-solid fa-check mr-1"></i>
                                <span>Generate & Save Debit Note</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Top Summary Cards -->
                <div class="p-3 bg-slate-50 border-b border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                    <div class="bg-white p-2.5 rounded border border-slate-200 shadow-sm">
                        <span class="text-[10px] text-slate-500 font-bold uppercase block">Debit Notes</span>
                        <span class="text-xl font-black font-mono text-slate-900">{{ $debitNotes->count() }}</span>
                    </div>
                    <div class="bg-white p-2.5 rounded border border-slate-200 shadow-sm">
                        <span class="text-[10px] text-slate-500 font-bold uppercase block">Total Physical Shortage</span>
                        <span class="text-xl font-black font-mono text-slate-800">{{ number_format($debitNotes->sum('loss_volume'), 0) }} L</span>
                    </div>
                    <div class="bg-white p-2.5 rounded border border-slate-200 shadow-sm">
                        <span class="text-[10px] text-slate-500 font-bold uppercase block">Total Debited Volume</span>
                        <span class="text-xl font-black font-mono text-rose-700">{{ number_format($debitNotes->sum('debit_volume'), 0) }} L</span>
                    </div>
                    <div class="bg-white p-2.5 rounded border border-slate-200 shadow-sm">
                        <span class="text-[10px] text-slate-500 font-bold uppercase block">Total Debited Amount</span>
                        <span class="text-xl font-black font-mono text-amber-700">{{ number_format($debitNotes->sum('debit_amount'), 2) }} ETB</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-xs">
                        <thead class="bg-slate-100 font-bold uppercase text-slate-700">
                            <tr>
                                <th class="access-table-th">Debit Note #</th>
                                <th class="access-table-th">Date</th>
                                <th class="access-table-th">Truck Plate</th>
                                <th class="access-table-th">Transporter</th>
                                <th class="access-table-th">Driver</th>
                                <th class="access-table-th">Product</th>
                                <th class="access-table-th text-right">Physical Shortage</th>
                                <th class="access-table-th text-right">Contract Allowance</th>
                                <th class="access-table-th text-right">Debited Litres</th>
                                <th class="access-table-th text-right">Rate (ETB)</th>
                                <th class="access-table-th text-right">Debit Total (ETB)</th>
                                <th class="access-table-th text-center">Voucher Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-mono">
                            @forelse($debitNotes as $dn)
                                <tr class="hover:bg-rose-50/40">
                                    <td class="access-table-td font-bold text-rose-800">DN-{{ $dn->to_number }}</td>
                                    <td class="access-table-td text-slate-600">{{ $dn->order_date }}</td>
                                    <td class="access-table-td font-bold text-slate-900">{{ $dn->plate_no }}</td>
                                    <td class="access-table-td font-sans text-slate-700">{{ $dn->carrier_name ?: 'TAF Fleet' }}</td>
                                    <td class="access-table-td font-sans font-semibold text-slate-800">{{ $dn->driver_name ?: '-' }}</td>
                                    <td class="access-table-td font-sans">{{ $dn->product_item_number }}</td>
                                    <td class="access-table-td text-right font-bold text-slate-800">{{ number_format($dn->loss_volume, 0) }} L</td>
                                    <td class="access-table-td text-right text-slate-500">{{ number_format($dn->allowed_loss_volume, 0) }} L</td>
                                    <td class="access-table-td text-right font-bold text-rose-700">{{ number_format($dn->debit_volume, 0) }} L</td>
                                    <td class="access-table-td text-right">{{ number_format($dn->price_per_litre, 2) }}</td>
                                    <td class="access-table-td text-right font-bold text-amber-800 text-sm">{{ number_format($dn->debit_amount, 2) }} ETB</td>
                                    <td class="access-table-td text-center">
                                        <a href="{{ route('petroleum.debit-note', $dn->id) }}" target="_blank"
                                            class="px-2.5 py-1 rounded font-sans font-bold bg-rose-700 hover:bg-rose-800 text-white text-[11px] shadow-sm inline-flex items-center space-x-1">
                                            <i class="fa-solid fa-print mr-1"></i>
                                            <span>Print Voucher</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="px-4 py-8 text-center text-slate-400 font-sans">
                                        No debit notes issued yet. Orders with shortages exceeding allowance will appear here.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
            @if($activeTable === 'density_correct_20')
                <div class="p-3 bg-slate-100 border-b border-slate-200 flex justify-between items-center text-xs">
                    <div>
                        <strong class="text-slate-800 text-sm">Table: density_correct_20</strong>
                        <span class="text-slate-500 ml-2">(MS Access legacy: <code>Density_Correct_20</code> - 361 rows)</span>
                    </div>
                    <span class="text-slate-600 font-mono font-semibold">Showing 100 of {{ $tableCounts['density_correct_20'] }} rows</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 font-mono">
                        <thead>
                            <tr>
                                <th class="access-table-th text-right">Observed Density (kg/m³)</th>
                                <th class="access-table-th text-right">Observed Temperature (°C)</th>
                                <th class="access-table-th text-right">Standard Density @ 20°C (kg/m³)</th>
                                <th class="access-table-th">Standard Formula</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($densitySample as $d)
                                <tr class="hover:bg-amber-50/50">
                                    <td class="access-table-td text-right font-bold text-slate-800">{{ number_format($d->observed_density, 1) }}</td>
                                    <td class="access-table-td text-right text-amber-700 font-bold">{{ number_format($d->temperature, 1) }} °C</td>
                                    <td class="access-table-td text-right font-bold text-indigo-800">{{ number_format($d->density_20, 2) }}</td>
                                    <td class="access-table-td text-[11px] text-slate-500 font-sans">
                                        ASTM 54B/D Table 20 Conversion: <code>DensityCalc::density20({{ $d->observed_density }}, {{ $d->temperature }})</code>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- 6. FACTOR CORRECT 20 -->
            @if($activeTable === 'factor_correct_20')
                <div class="p-3 bg-slate-100 border-b border-slate-200 flex justify-between items-center text-xs">
                    <div>
                        <strong class="text-slate-800 text-sm">Table: factor_correct_20</strong>
                        <span class="text-slate-500 ml-2">(MS Access legacy: <code>Factor_Correct_20</code> - 361 rows)</span>
                    </div>
                    <span class="text-slate-600 font-mono font-semibold">Showing 100 of {{ $tableCounts['factor_correct_20'] }} rows</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 font-mono">
                        <thead>
                            <tr>
                                <th class="access-table-th text-right">Temperature (°C)</th>
                                <th class="access-table-th text-right">Density @ 20°C (kg/m³)</th>
                                <th class="access-table-th text-right">Volume Correction Factor (VCF)</th>
                                <th class="access-table-th">Standard Formula</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($factorSample as $f)
                                <tr class="hover:bg-amber-50/50">
                                    <td class="access-table-td text-right text-amber-700 font-bold">{{ number_format($f->temperature, 1) }} °C</td>
                                    <td class="access-table-td text-right font-bold text-slate-800">{{ number_format($f->density_20, 2) }}</td>
                                    <td class="access-table-td text-right font-bold text-emerald-700">{{ number_format($f->factor, 6) }}</td>
                                    <td class="access-table-td text-[11px] text-slate-500 font-sans">
                                        ASTM VCF 20°C: <code>DensityCalc::vcf20({{ $f->density_20 }}, {{ $f->temperature }})</code>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

        </div>

    </div>

</body>
</html>
