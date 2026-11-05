<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debit Note - {{ $to->to_number }} - TAF Oil</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .print-card { border: none !important; box-shadow: none !important; padding: 0 !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 p-6 md:p-12 font-sans antialiased">

    <!-- Top Action Bar -->
    <div class="max-w-4xl mx-auto mb-6 flex justify-between items-center no-print">
        <a href="{{ route('petroleum.index') }}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900 flex items-center space-x-1">
            <span>&larr; Back to Dashboard</span>
        </a>
        <button onclick="window.print()" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow transition">
            Print Debit Note
        </button>
    </div>

    <!-- Printable Debit Note Sheet -->
    <div class="max-w-4xl mx-auto bg-white p-10 rounded-xl shadow-md border border-slate-200 print-card space-y-8">

        <!-- Header -->
        <div class="border-b-2 border-slate-800 pb-6 text-center space-y-1">
            <h1 class="text-2xl font-black tracking-wider uppercase text-slate-900">TAF OIL ETHIOPIA PLC</h1>
            <p class="text-xs uppercase tracking-widest text-slate-500">Petroleum Product Shortage & Debit Note Voucher</p>
            <p class="text-xs text-slate-500">Ref: MS Access RPTtempdebitnote Replacement</p>
        </div>

        <!-- Meta Details -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6 text-sm border-b border-slate-200 pb-4">
            <div class="space-y-1.5">
                <div><span class="text-xs text-slate-500 uppercase font-bold block">Debit Note No:</span> <strong class="font-mono text-base text-indigo-900">DN-{{ $to->to_number }}</strong></div>
                <div><span class="text-xs text-slate-500 uppercase font-bold block">Transfer Order Ref:</span> <span class="font-mono">{{ $to->reference ?: $to->to_number }}</span></div>
                <div><span class="text-xs text-slate-500 uppercase font-bold block">Transaction Date:</span> <span>{{ $to->order_date }}</span></div>
            </div>
            <div class="space-y-1.5">
                <div><span class="text-xs text-slate-500 uppercase font-bold block">Truck Plate Number:</span> <strong class="font-mono text-base uppercase text-slate-900">{{ $to->plate_no }}</strong></div>
                <div><span class="text-xs text-slate-500 uppercase font-bold block">Trailer Plate:</span> <span class="font-mono">{{ $to->trailer_plate_no ?: 'N/A' }}</span></div>
                <div><span class="text-xs text-slate-500 uppercase font-bold block">Carrier / Transporter:</span> <span class="font-semibold text-slate-800">{{ $to->carrier_name ?: 'TAF Oil Fleet' }}</span></div>
            </div>
            <div class="space-y-1.5 text-right md:text-left">
                <div><span class="text-xs text-slate-500 uppercase font-bold block">Driver Name:</span> <span class="font-bold text-slate-900">{{ $to->driver_name ?: 'N/A' }}</span></div>
                <div><span class="text-xs text-slate-500 uppercase font-bold block">Driver Licence No:</span> <span class="font-mono font-semibold">{{ $to->driver_licence_no ?: 'N/A' }}</span></div>
                <div><span class="text-xs text-slate-500 uppercase font-bold block">Product Code:</span> <span class="font-semibold">{{ $to->product_item_number }}</span></div>
            </div>
        </div>

        <!-- Measurements Breakdown Table -->
        <div class="border border-slate-300 rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-100 text-slate-700 text-xs font-bold uppercase">
                    <tr>
                        <th class="px-4 py-2.5 text-left">Delivery Stage</th>
                        <th class="px-4 py-2.5 text-right">Observed (L)</th>
                        <th class="px-4 py-2.5 text-right">Temp (°C)</th>
                        <th class="px-4 py-2.5 text-right">Density @ 20°C</th>
                        <th class="px-4 py-2.5 text-right">VCF (20°C)</th>
                        <th class="px-4 py-2.5 text-right">Volume @ 20°C (L)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 font-mono text-xs">
                    <tr>
                        <td class="px-4 py-2.5 font-sans font-semibold">1. Origin / Truck Dip</td>
                        <td class="px-4 py-2.5 text-right">{{ number_format((float)$to->start_observed_litres, 2) }}</td>
                        <td class="px-4 py-2.5 text-right">{{ number_format((float)$to->start_temperature, 1) }}</td>
                        <td class="px-4 py-2.5 text-right">{{ number_format((float)$to->start_density_20, 1) }}</td>
                        <td class="px-4 py-2.5 text-right">{{ number_format((float)$to->start_factor, 6) }}</td>
                        <td class="px-4 py-2.5 text-right font-bold text-slate-900">{{ number_format((float)$to->start_vol20, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="px-4 py-2.5 font-sans font-semibold">2. Destination / Discharge</td>
                        <td class="px-4 py-2.5 text-right">{{ number_format((float)($to->metered_total ?? $to->final_observed_litres), 2) }}</td>
                        <td class="px-4 py-2.5 text-right">{{ number_format((float)$to->final_temperature, 1) }}</td>
                        <td class="px-4 py-2.5 text-right">{{ number_format((float)$to->final_density_20, 1) }}</td>
                        <td class="px-4 py-2.5 text-right">{{ number_format((float)$to->final_factor, 6) }}</td>
                        <td class="px-4 py-2.5 text-right font-bold text-slate-900">{{ number_format((float)$to->final_vol20, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Shortage & Debit Calculation Box -->
        <div class="bg-slate-50 border border-slate-300 rounded-lg p-5 space-y-3">
            <div class="flex justify-between items-center text-sm">
                <span class="text-slate-600 font-medium">Physical Loss (|Start Vol20 - Final Vol20|):</span>
                <span class="font-mono font-bold text-slate-900">{{ number_format((float)$to->loss_volume, 2) }} L</span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-slate-600 font-medium">Contract Allowable Loss ({{ $to->allowable_loss_percent }}%):</span>
                <span class="font-mono text-slate-700">{{ $to->allowed_loss_volume }} L ({{ number_format((float)$to->allowed_loss_2dp, 2) }})</span>
            </div>
            <div class="flex justify-between items-center text-sm border-t border-slate-200 pt-2">
                <div>
                    <span class="font-bold text-rose-700 block">Shortage Claimed (Debit Volume):</span>
                    <span class="text-xs text-slate-500">
                        @if($to->loss_volume > 150)
                            Branch 2 (> 150 L): Entire loss debited with no allowance deducted.
                        @else
                            Branch 1 (&le; 150 L): Excess &ge; 0.5 L buffer applied.
                        @endif
                    </span>
                </div>
                <span class="font-mono font-black text-xl text-rose-700">{{ number_format((float)$to->debit_volume, 2) }} L</span>
            </div>
            <div class="flex justify-between items-center text-sm border-t border-slate-200 pt-2">
                <span class="text-slate-600 font-medium">Tariff Rate (Price per Litre):</span>
                <span class="font-mono text-slate-900">{{ number_format((float)$to->price_per_litre, 2) }} ETB / L</span>
            </div>
            <div class="flex justify-between items-center text-lg bg-amber-100/60 p-3 rounded border border-amber-300">
                <span class="font-black text-slate-900 uppercase tracking-wide">Total Amount Payable:</span>
                <span class="font-mono font-black text-2xl text-amber-900">{{ number_format((float)$to->debit_amount, 2) }} ETB</span>
            </div>
        </div>

        <!-- GL Journal Entry Coding (tblTranscat / tblTransactDetail) -->
        <div class="border border-slate-200 rounded p-4 text-xs font-mono space-y-1 bg-white">
            <span class="font-sans font-bold text-slate-700 uppercase tracking-wider block mb-1">Sage 300 GL Coding:</span>
            <div class="flex justify-between">
                <span>Dr 122010-100 (Transporter Receivables)</span>
                <span>{{ number_format((float)$to->debit_amount, 2) }} ETB</span>
            </div>
            <div class="flex justify-between text-slate-600">
                <span>Cr 161022 (Inventory Loss Recovery)</span>
                <span>{{ number_format((float)$to->debit_amount, 2) }} ETB</span>
            </div>
        </div>

        <!-- Signature Section -->
        <div class="grid grid-cols-3 gap-8 pt-8 text-xs text-center">
            <div class="border-t border-slate-400 pt-2">
                <p class="font-bold">Prepared By</p>
                <p class="text-slate-400 mt-6">Signature & Date</p>
            </div>
            <div class="border-t border-slate-400 pt-2">
                <p class="font-bold">Verified By</p>
                <p class="text-slate-400 mt-6">Depot Supervisor</p>
            </div>
            <div class="border-t border-slate-400 pt-2">
                <p class="font-bold">Transporter / Driver</p>
                <p class="text-slate-400 mt-6">Acknowledged & Signed</p>
            </div>
        </div>

    </div>

</body>
</html>
