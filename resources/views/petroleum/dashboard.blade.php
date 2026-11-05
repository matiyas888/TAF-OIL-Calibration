<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TAF OIL PLC - TRANSHIPMENT ORDER</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; background-color: #cbd5e1; }
        .access-input {
            background-color: #ffffff;
            border: 1px solid #94a3b8;
            font-size: 0.8rem;
            padding: 0.2rem 0.4rem;
            color: #0f172a;
        }
        .access-input:focus {
            outline: 2px solid #2563eb;
            background-color: #eff6ff;
        }
        .access-input-yellow {
            background-color: #fef08a !important; /* Exact Access highlight yellow */
            font-weight: 700;
        }
        .access-label {
            font-size: 0.68rem;
            font-weight: 700;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        .access-btn {
            font-size: 0.78rem;
            font-weight: 700;
            padding: 0.35rem 1rem;
            border-radius: 2px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
    </style>
</head>
<body class="text-slate-800 antialiased min-h-screen p-2 md:p-6">

    <div class="max-w-7xl mx-auto bg-slate-100 rounded shadow-2xl border-2 border-slate-400 overflow-hidden">

        <!-- Top Status Bar & Quick Example Loader -->
        <div class="bg-slate-800 text-white px-4 py-2 flex flex-col sm:flex-row justify-between items-center text-xs">
            <div class="flex items-center space-x-3 mb-2 sm:mb-0">
                <span class="font-bold text-amber-400 uppercase tracking-wider">TAF OIL ETHIOPIA / MS Access Port</span>
                <span class="text-slate-400">|</span>
                <span class="text-slate-300">frmmaindimension</span>
            </div>
            <!-- Quick Pre-fill Switcher & Table Inspector Link -->
            <div class="flex items-center space-x-2">
                <span class="text-slate-400">Load Screenshot:</span>
                <button type="button" id="btnLoadShot1" class="px-2 py-1 bg-indigo-700 hover:bg-indigo-600 text-white rounded font-bold text-[11px]">
                    Photo 1 (FDC-99995)
                </button>
                <button type="button" id="btnLoadShot2" class="px-2 py-1 bg-emerald-700 hover:bg-emerald-600 text-white rounded font-bold text-[11px]">
                    Photo 2 (A40983)
                </button>
                <a href="{{ route('petroleum.tables') }}" class="px-2.5 py-1 bg-amber-600 hover:bg-amber-500 text-white rounded font-bold text-[11px] flex items-center space-x-1 shadow-sm transition">
                    <i class="fa-solid fa-table-list mr-1"></i>
                    <span>Database Tables</span>
                </a>
            </div>
        </div>

        @if(session('status'))
            <div class="p-3 bg-emerald-100 border-b border-emerald-300 text-emerald-900 text-xs flex items-center justify-between">
                <div class="flex items-center space-x-2 font-semibold">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>{{ session('status') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900 font-bold">&times;</button>
            </div>
        @endif

        <form id="toForm" method="POST" action="{{ route('petroleum.store') }}" class="p-4 md:p-6 space-y-3">
            @csrf

            <!-- Form Title Header -->
            <div class="border-b-2 border-slate-400 pb-2 flex justify-between items-center">
                <div>
                    <span class="text-xs font-black text-red-700 block tracking-wider">TAF OIL PLC (TAF OIL ETHIOPIA)</span>
                    <h1 class="text-2xl font-black text-slate-900 tracking-wider">TRANSHIPMENT ORDER</h1>
                </div>
                <div class="text-right text-xs text-slate-600 font-bold">
                    <span>SYSTEM DATE: {{ date('m/d/Y') }}</span>
                </div>
            </div>

            <!-- Row 1: Ship From, Ship To & Reference Block -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3 text-xs border border-slate-300 p-2.5 bg-slate-50 rounded">
                <!-- Ship From -->
                <div class="md:col-span-4 space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-700">SHIP FROM</span>
                        <span class="text-[10px] text-slate-500 uppercase">STORAGE POINT</span>
                    </div>
                    <div class="flex space-x-1.5">
                        <input type="text" name="ship_from_storage_point" id="ship_from_storage_point" value="TAF OIL Djibouti"
                            class="access-input flex-1 font-semibold rounded">
                        <input type="date" name="ship_from_date" id="ship_from_date" value="2026-10-22" class="access-input rounded w-28 text-center">
                        <input type="text" name="ship_from_loc" id="ship_from_loc" value="108" placeholder="LOC" class="access-input rounded w-12 text-center font-bold">
                    </div>
                </div>

                <!-- Ship To -->
                <div class="md:col-span-4 space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-slate-700">SHIP TO</span>
                        <span class="text-[10px] text-slate-500 uppercase">DESTINATION DEPOT</span>
                    </div>
                    <div class="flex space-x-1.5">
                        <input type="text" name="ship_to_depot" id="ship_to_depot" value="AVIATION - DEPOT"
                            class="access-input flex-1 font-semibold rounded">
                        <input type="date" name="ship_to_date" id="ship_to_date" value="2026-10-26" class="access-input rounded w-28 text-center">
                        <input type="text" name="ship_to_loc" id="ship_to_loc" value="105" placeholder="LOC" class="access-input rounded w-12 text-center font-bold">
                    </div>
                </div>

                <!-- Reference & Plate & Product -->
                <div class="md:col-span-4 space-y-1 text-right">
                    <div class="flex items-center justify-end space-x-2">
                        <span class="font-bold text-slate-700">Reference:</span>
                        <input type="text" name="reference" id="reference" value="FDC-99995"
                            class="access-input access-input-yellow rounded w-36 text-center">
                    </div>
                    <div class="flex items-center justify-end space-x-2">
                        <span class="font-bold text-slate-700">Select Plate:</span>
                        <select name="plate_no" id="plate_select" class="access-input access-input-yellow rounded w-48 font-bold font-mono text-xs">
                            @foreach($trucks as $trk)
                                <option value="{{ $trk->plate_no }}"
                                    data-truck="{{ $trk->truck_no }}"
                                    data-trailer="{{ $trk->trailer_plate_no }}"
                                    data-carrier="{{ $trk->carrier_name }}"
                                    data-driver="{{ $trk->driver_name }}"
                                    data-licence="{{ $trk->driver_licence_no }}"
                                    data-capacity="{{ $trk->total_capacity }}"
                                    data-comps="{{ $trk->compartment_count }}"
                                    {{ $trk->plate_no === 'A40983/*41304' ? 'selected' : '' }}>
                                    {{ $trk->plate_no }} ({{ $trk->carrier_name }})
                                </option>
                            @endforeach
                        </select>
                        <input type="hidden" name="plate_no_custom" id="plate_no" value="A40983/*41304">
                    </div>
                    <div class="flex items-center justify-end space-x-2">
                        <span class="font-bold text-slate-700">Product Type:</span>
                        <select name="product_item_number" id="product_item_number" class="access-input access-input-yellow rounded w-36 font-bold">
                            @foreach($products as $prod)
                                <option value="{{ $prod->item_number }}"
                                    data-name="{{ $prod->product_name }}"
                                    data-loss="{{ $prod->allowable_loss_percent }}"
                                    data-price="{{ $prod->price_per_litre }}"
                                    {{ str_contains($prod->product_name, 'JET') ? 'selected' : '' }}>
                                    {{ $prod->product_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="to_number" id="to_number" value="TO-20261026-40983">
                    <input type="hidden" name="order_date" id="order_date" value="2026-10-26">
                </div>
            </div>

            <!-- Dedicated Truck & Driver Information Banner -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-2.5 text-xs border border-amber-300 p-2.5 bg-gradient-to-r from-amber-50 via-yellow-50 to-orange-50 rounded items-center shadow-sm">
                <div class="md:col-span-3">
                    <span class="text-[10px] text-slate-600 font-bold uppercase block">
                        <i class="fa-solid fa-building text-amber-700 mr-1"></i> Transporter / Carrier
                    </span>
                    <input type="text" name="carrier_name" id="carrier_name" value="Third Party (Dekebo)"
                        class="access-input rounded font-semibold text-slate-800 w-full bg-white">
                </div>
                <div class="md:col-span-2">
                    <span class="text-[10px] text-slate-600 font-bold uppercase block">
                        <i class="fa-solid fa-trailer text-amber-700 mr-1"></i> Trailer Plate
                    </span>
                    <input type="text" name="trailer_plate_no" id="trailer_plate_no" value="41304"
                        class="access-input rounded font-mono font-bold text-slate-800 w-full bg-white text-center">
                </div>
                <div class="md:col-span-3">
                    <span class="text-[10px] text-slate-600 font-bold uppercase block">
                        <i class="fa-solid fa-id-badge text-amber-700 mr-1"></i> Driver Name
                    </span>
                    <input type="text" id="driver_name_top" value="Mohammed Ahmed"
                        class="access-input rounded font-bold text-slate-900 w-full bg-white">
                </div>
                <div class="md:col-span-2">
                    <span class="text-[10px] text-slate-600 font-bold uppercase block">
                        <i class="fa-solid fa-address-card text-amber-700 mr-1"></i> Licence No
                    </span>
                    <input type="text" id="driver_licence_top" value="DL-ETH-88219"
                        class="access-input rounded font-mono font-semibold text-slate-800 w-full bg-white text-center">
                </div>
                <div class="md:col-span-2 text-right">
                    <span class="text-[10px] text-slate-600 font-bold uppercase block">Nominal Capacity</span>
                    <span id="disp_truck_capacity" class="font-mono font-black text-amber-900 text-sm">50,293 L</span>
                </div>
            </div>

            <!-- Row 2: Main Access Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 border border-slate-300 p-2.5 bg-white rounded shadow-inner">

                <!-- Left: Compartments Table (SHIP PKG/VOL Left on Board) -->
                <div class="lg:col-span-3 border-r border-slate-200 pr-2 space-y-1.5">
                    <div class="grid grid-cols-3 gap-1 text-[10px] font-bold text-slate-700 text-center uppercase bg-slate-100 py-1 border border-slate-300 rounded">
                        <span>SHIP</span>
                        <span>PKG/VOL</span>
                        <span>Left on Board</span>
                    </div>

                    <!-- 5 Compartments matching photo -->
                    <div class="space-y-1">
                        <div class="grid grid-cols-3 gap-1">
                            <input type="number" id="dip_1" value="1512" class="access-input access-input-yellow text-center rounded comp-dip">
                            <input type="number" id="vol_1" value="11395" class="access-input text-right rounded font-mono comp-vol">
                            <input type="number" value="0" class="access-input text-center rounded text-slate-400">
                        </div>
                        <div class="grid grid-cols-3 gap-1">
                            <input type="number" id="dip_2" value="1498" class="access-input access-input-yellow text-center rounded comp-dip">
                            <input type="number" id="vol_2" value="11850" class="access-input text-right rounded font-mono comp-vol">
                            <input type="number" value="0" class="access-input text-center rounded text-slate-400">
                        </div>
                        <div class="grid grid-cols-3 gap-1">
                            <input type="number" id="dip_3" value="1575" class="access-input access-input-yellow text-center rounded comp-dip">
                            <input type="number" id="vol_3" value="12667" class="access-input text-right rounded font-mono comp-vol">
                            <input type="number" value="0" class="access-input text-center rounded text-slate-400">
                        </div>
                        <div class="grid grid-cols-3 gap-1">
                            <input type="number" id="dip_4" value="1633" class="access-input access-input-yellow text-center rounded comp-dip">
                            <input type="number" id="vol_4" value="14381" class="access-input text-right rounded font-mono comp-vol">
                            <input type="number" value="0" class="access-input text-center rounded text-slate-400">
                        </div>
                        <div class="grid grid-cols-3 gap-1">
                            <input type="number" id="dip_5" value="0" class="access-input access-input-yellow text-center rounded comp-dip">
                            <input type="number" id="vol_5" value="0" class="access-input text-right rounded font-mono comp-vol">
                            <input type="number" value="0" class="access-input text-center rounded text-slate-400">
                        </div>
                    </div>

                    <!-- SG (Observed Density) -->
                    <div class="flex items-center justify-between pt-1">
                        <span class="access-label">SG (Observed):</span>
                        <input type="number" step="0.1" name="start_density" id="start_density" value="787"
                            class="access-input access-input-yellow text-center font-bold rounded w-24">
                    </div>
                </div>

                <!-- Product Description & Code -->
                <div class="lg:col-span-2 border-r border-slate-200 pr-2 space-y-2">
                    <div>
                        <label class="access-label block mb-1">PRODUCT DESCRIPTION</label>
                        <input type="text" id="product_desc" readonly value="JET-A1"
                            class="access-input w-full bg-slate-50 font-bold text-slate-800 rounded">
                    </div>
                    <div>
                        <label class="access-label block mb-1">PRODUCT CODE</label>
                        <input type="text" id="product_code_disp" readonly value="P07-0001"
                            class="access-input w-full bg-slate-50 font-mono text-slate-700 rounded">
                    </div>
                </div>

                <!-- Center Measurements: Observed Volume, Temp, SG at 20, VCF, Vol 20 -->
                <div class="lg:col-span-4 border-r border-slate-200 pr-2 space-y-2">
                    <div class="grid grid-cols-4 gap-1 text-[10px] font-bold text-slate-700 text-center uppercase bg-slate-100 py-1 border border-slate-300 rounded">
                        <span>Observed Vol</span>
                        <span>Temp °C</span>
                        <span>S.G. @ 20</span>
                        <span>VCF Factor</span>
                    </div>
                    <div class="grid grid-cols-4 gap-1">
                        <input type="number" name="start_observed_litres" id="start_observed_litres" value="50293"
                            class="access-input text-right font-bold rounded font-mono">
                        <input type="number" step="0.1" name="start_temperature" id="start_temperature" value="20.00"
                            class="access-input access-input-yellow text-center rounded font-bold">
                        <input type="text" id="disp_start_density_20" readonly value="787.00"
                            class="access-input text-center font-bold bg-slate-50 rounded">
                        <input type="text" id="disp_start_factor" readonly value="1.000000"
                            class="access-input text-center font-bold font-mono bg-slate-50 rounded">
                    </div>

                    <!-- Volume at 20°C (Origin vs Discharge) -->
                    <div class="pt-2 border-t border-slate-200 space-y-1.5">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700">Origin Volume @ 20°C:</span>
                            <input type="number" step="0.01" name="origin_vol20" id="origin_vol20" value="49829"
                                class="access-input access-input-yellow rounded w-36 text-right font-mono text-sm">
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-700">Discharge Vol @ 20°C:</span>
                            <input type="text" id="disp_final_vol20" readonly value="49787"
                                class="access-input rounded w-36 text-right font-mono text-sm bg-slate-50 font-bold text-indigo-900">
                        </div>
                    </div>
                </div>

                <!-- Right: OVER / SHORT Summary -->
                <div class="lg:col-span-3 space-y-2 text-xs">
                    <div class="text-[10px] font-bold text-slate-700 uppercase bg-slate-100 py-1 border border-slate-300 rounded text-center">
                        OVER / SHORT
                    </div>

                    <div class="space-y-1">
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600 font-medium">SG @ 15 DJ:</span>
                            <input type="number" step="0.0001" name="sg_15_dj_1" id="sg_15_dj_1" value="0.7916"
                                class="access-input access-input-yellow text-center rounded w-24 font-mono font-bold">
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-slate-600 font-medium">SG @ 15 Rec:</span>
                            <input type="number" step="0.0001" name="sg_15_dj_2" id="sg_15_dj_2" value="0.7907"
                                class="access-input text-center rounded w-24 font-mono font-bold">
                        </div>
                        <div class="flex justify-between items-center text-slate-700 font-bold border-b border-slate-200 pb-1">
                            <span>SG Diff:</span>
                            <span id="disp_sg_diff" class="font-mono text-emerald-700 font-bold">+0.0009</span>
                            <input type="hidden" name="sg_15_diff" id="sg_15_diff" value="0.0009">
                        </div>
                    </div>

                    <!-- Shortage / Loss Volume -->
                    <div class="p-2 bg-slate-50 border border-slate-300 rounded text-center">
                        <span class="access-label block text-slate-600">Loss / Over-Short:</span>
                        <span id="disp_shortage_vol" class="text-2xl font-black font-mono text-rose-700">42 L</span>
                    </div>
                </div>
            </div>

            <!-- Row 3: Meters & Allowance & Debit Note Section -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-2.5 border border-slate-300 p-2.5 bg-slate-50 rounded">

                <!-- Left: Flow Meter Part One & Part Two -->
                <div class="lg:col-span-6 space-y-2 text-xs border-r border-slate-300 pr-2">
                    <div class="flex items-center justify-between pb-1">
                        <label class="inline-flex items-center font-bold text-slate-800 cursor-pointer">
                            <input type="checkbox" name="use_metered" id="use_metered" value="1" checked class="mr-1.5 rounded text-indigo-600">
                            By Flow Meter
                        </label>
                        <span class="text-[10px] text-slate-500 uppercase">Metered Total = (TO - FROM + DRAINED)</span>
                    </div>

                    <!-- Part One -->
                    <div class="grid grid-cols-4 gap-1.5 items-center bg-white p-2 rounded border border-slate-200">
                        <span class="font-bold text-slate-700">PART ONE</span>
                        <div>
                            <span class="text-[10px] text-slate-500 block">TO</span>
                            <input type="number" name="meter_to_1" id="meter_to_1" value="162906459"
                                class="access-input access-input-yellow rounded w-full font-mono text-right font-bold">
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-500 block">FROM</span>
                            <input type="number" name="meter_from_1" id="meter_from_1" value="162856672"
                                class="access-input access-input-yellow rounded w-full font-mono text-right font-bold">
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-500 block">DRAINED</span>
                            <input type="number" name="drained_1" id="drained_1" value="0"
                                class="access-input rounded w-full font-mono text-right">
                        </div>
                    </div>

                    <!-- Part Two -->
                    <div class="grid grid-cols-4 gap-1.5 items-center bg-white p-2 rounded border border-slate-200">
                        <span class="font-bold text-slate-700">PART TWO</span>
                        <div>
                            <span class="text-[10px] text-slate-500 block">TO</span>
                            <input type="number" name="meter_to_2" id="meter_to_2" value="0"
                                class="access-input access-input-yellow rounded w-full font-mono text-right font-bold">
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-500 block">FROM</span>
                            <input type="number" name="meter_from_2" id="meter_from_2" value="0"
                                class="access-input access-input-yellow rounded w-full font-mono text-right font-bold">
                        </div>
                        <div>
                            <span class="text-[10px] text-slate-500 block">DRAINED</span>
                            <input type="number" name="drained_2" id="drained_2" value="0"
                                class="access-input rounded w-full font-mono text-right">
                        </div>
                    </div>

                    <!-- Total Metered -->
                    <div class="flex justify-between items-center p-2 bg-indigo-50 border border-indigo-200 rounded">
                        <span class="font-bold text-indigo-900 uppercase">TOTAL METERED:</span>
                        <span id="disp_metered_total" class="font-mono font-black text-base text-indigo-900">49,787 L</span>
                    </div>
                </div>

                <!-- Right: Allowance & Debit Note Voucher -->
                <div class="lg:col-span-6 space-y-2 text-xs">
                    <!-- Allowed Loss -->
                    <div class="flex items-center justify-between p-2 bg-white rounded border border-slate-200">
                        <div class="flex items-center space-x-2">
                            <input type="number" step="0.01" name="allowable_loss_percent" id="allowable_loss_percent" value="0.12"
                                class="access-input rounded w-16 text-center font-bold">
                            <span class="font-bold text-slate-700">% ALLOWED LOSS IS:</span>
                        </div>
                        <span id="disp_allowed_loss" class="font-mono font-bold text-slate-800 text-sm">60 L (59.79)</span>
                    </div>

                    <!-- Debit Note Box -->
                    <div class="border border-slate-300 bg-white p-2.5 rounded space-y-2">
                        <div class="flex justify-between items-center border-b border-slate-200 pb-1">
                            <span class="font-bold text-sm text-slate-800 uppercase">Debit Note</span>
                            <span id="disp_debit_branch" class="text-[10px] px-2 py-0.5 rounded font-bold bg-emerald-100 text-emerald-800 uppercase">
                                No Debit (Loss &le; Allowed)
                            </span>
                        </div>

                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div>
                                <span class="text-[10px] text-slate-500 uppercase font-bold block">Debit Vol (L)</span>
                                <span id="disp_debit_vol" class="text-xl font-black font-mono text-slate-900">0</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 uppercase font-bold block">Unit Cost (ETB)</span>
                                <input type="number" step="0.001" name="price_per_litre" id="price_per_litre" value="319.760"
                                    class="access-input rounded w-full text-center font-bold font-mono">
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 uppercase font-bold block">Amount (ETB)</span>
                                <span id="disp_debit_amt" class="text-xl font-black font-mono text-amber-700">0.00</span>
                            </div>
                        </div>

                        <!-- Insurance -->
                        <div class="flex items-center space-x-4 pt-1 text-[11px] border-t border-slate-100">
                            <span class="font-bold text-slate-600">INSURANCE:</span>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" name="insurance_status" value="No" checked class="mr-1 text-indigo-600"> No
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" name="insurance_status" value="Yes" class="mr-1 text-indigo-600"> Yes
                            </label>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="radio" name="insurance_status" value="Recovered" class="mr-1 text-indigo-600"> Recovered
                            </label>
                        </div>
                    </div>

                    <!-- Bottom Reference Details: Via, Side No, Driver, Licence -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-1.5 pt-1 text-[10px]">
                        <div>
                            <span class="block text-slate-500 font-bold">TRANSHIPMENT NO.</span>
                            <input type="text" name="transhipment_order_no" id="transhipment_order_no" class="access-input rounded w-full font-mono">
                        </div>
                        <div>
                            <span class="block text-slate-500 font-bold">DELIVERY ORDER</span>
                            <input type="text" name="delivery_order_no" id="delivery_order_no" class="access-input rounded w-full font-mono">
                        </div>
                        <div>
                            <span class="block text-slate-500 font-bold">DRIVER NAME</span>
                            <input type="text" name="driver_name" id="driver_name" class="access-input rounded w-full">
                        </div>
                        <div>
                            <span class="block text-slate-500 font-bold">LICENCE NO.</span>
                            <input type="text" name="driver_licence_no" id="driver_licence_no" class="access-input rounded w-full font-mono">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Action Toolbar Matching Access Screen Buttons: [Debit Note] [Reprint] [Preview] [Save] [Close] -->
            <div class="flex flex-col sm:flex-row items-center justify-between pt-2 border-t border-slate-300 gap-2">
                <div class="flex space-x-2">
                    <button type="button" id="btnRecalc" class="access-btn bg-slate-700 hover:bg-slate-800 text-white shadow">
                        <i class="fa-solid fa-calculator mr-1"></i> Recalculate
                    </button>
                </div>

                <!-- Access Action Buttons: Debit Note | Reprint | Preview | Save | Close -->
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" id="btnActionDebit" class="access-btn text-indigo-800 bg-white border border-slate-400 hover:bg-indigo-50 font-bold shadow-sm">
                        Debit Note
                    </button>
                    <button type="button" id="btnActionReprint" class="access-btn text-slate-800 bg-white border border-slate-400 hover:bg-slate-100 font-bold shadow-sm">
                        Reprint
                    </button>
                    <button type="button" id="btnActionPreview" class="access-btn text-indigo-900 bg-indigo-50 border-2 border-indigo-600 hover:bg-indigo-100 font-black shadow-sm">
                        Preview
                    </button>
                    <button type="submit" id="btnActionSave" class="access-btn text-white bg-blue-700 hover:bg-blue-800 font-bold shadow-sm">
                        Save
                    </button>
                    <button type="button" onclick="window.location.reload()" class="access-btn text-slate-800 bg-slate-200 border border-slate-300 hover:bg-slate-300 font-bold shadow-sm">
                        Close
                    </button>
                </div>
            </div>
        </form>

        <!-- Saved Transhipment Orders History Table (tblTODaily_history) -->
        <div class="border-t-2 border-slate-400 bg-white">
            <div class="px-4 py-2.5 bg-slate-200 border-b border-slate-300 flex justify-between items-center text-xs">
                <span class="font-bold uppercase text-slate-800">Saved Transhipment Orders (tblTODaily_history)</span>
                <span class="text-slate-500">History log</span>
            </div>
            <div class="overflow-x-auto max-h-60 overflow-y-auto">
                <table class="min-w-full divide-y divide-slate-200 text-xs">
                    <thead class="bg-slate-100 text-slate-700 font-bold uppercase sticky top-0">
                        <tr>
                            <th class="px-3 py-2 text-left">Ref / TO #</th>
                            <th class="px-3 py-2 text-left">Plate</th>
                            <th class="px-3 py-2 text-left">Product</th>
                            <th class="px-3 py-2 text-right">Origin Vol20</th>
                            <th class="px-3 py-2 text-right">Discharge Vol20</th>
                            <th class="px-3 py-2 text-right">Shortage (L)</th>
                            <th class="px-3 py-2 text-right">Debit (L)</th>
                            <th class="px-3 py-2 text-right">Debit Amount</th>
                            <th class="px-3 py-2 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-mono text-[11px]">
                        @forelse($recentOrders as $order)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2 font-bold text-indigo-800 font-sans">{{ $order->reference ?? $order->to_number }}</td>
                                <td class="px-3 py-2">{{ $order->plate_no }}</td>
                                <td class="px-3 py-2 font-sans">{{ $order->product_item_number }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format((float)$order->start_vol20, 2) }}</td>
                                <td class="px-3 py-2 text-right">{{ number_format((float)$order->final_vol20, 2) }}</td>
                                <td class="px-3 py-2 text-right font-bold text-rose-600">{{ number_format((float)$order->loss_volume, 2) }}</td>
                                <td class="px-3 py-2 text-right font-bold">{{ number_format((float)$order->debit_volume, 2) }}</td>
                                <td class="px-3 py-2 text-right font-bold text-amber-700">{{ number_format((float)$order->debit_amount, 2) }} ETB</td>
                                <td class="px-3 py-2 text-center">
                                    <a href="{{ route('petroleum.debit-note', $order) }}" target="_blank"
                                        class="px-2 py-0.5 bg-indigo-100 hover:bg-indigo-200 text-indigo-900 rounded font-sans font-bold text-[10px]">
                                        Debit Note
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-4 text-center text-slate-400 font-sans">
                                    No records yet. Click "Save" above to record transhipment orders.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- JavaScript reproducing the exact MS Access Form Calculation Formulas & Quick Switchers -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('toForm');
            const btnRecalc = document.getElementById('btnRecalc');
            const productSelect = document.getElementById('product_item_number');
            const plateSelect = document.getElementById('plate_select');

            // Plate Selection Change Listener
            if (plateSelect) {
                plateSelect.addEventListener('change', function() {
                    const opt = this.options[this.selectedIndex];
                    if (!opt) return;

                    const plate = opt.value;
                    const truck = opt.dataset.truck || '';
                    const trailer = opt.dataset.trailer || '';
                    const carrier = opt.dataset.carrier || '';
                    const driver = opt.dataset.driver || '';
                    const licence = opt.dataset.licence || '';
                    const capacity = opt.dataset.capacity || '0';
                    const comps = opt.dataset.comps || '5';

                    document.getElementById('plate_no').value = plate;
                    document.getElementById('trailer_plate_no').value = trailer;
                    document.getElementById('carrier_name').value = carrier;

                    document.getElementById('driver_name_top').value = driver;
                    document.getElementById('driver_name').value = driver;

                    document.getElementById('driver_licence_top').value = licence;
                    document.getElementById('driver_licence_no').value = licence;

                    document.getElementById('disp_truck_capacity').textContent = 
                        Number(capacity).toLocaleString('en-US') + ' L (' + comps + ' Comps)';

                    const orderDate = (document.getElementById('order_date').value || '').replace(/-/g, '');
                    document.getElementById('to_number').value = 'TO-' + (orderDate || '20261026') + '-' + (truck || 'TRK');

                    // Recalculate dips for all non-empty compartments using new truck's calibration
                    for (let i = 1; i <= 5; i++) {
                        const dipVal = Number(document.getElementById('dip_' + i).value) || 0;
                        if (dipVal > 0) {
                            recalculateDip(i);
                        }
                    }
                });
            }

            // Sync Driver Details Top <-> Bottom
            const syncInputs = [
                ['driver_name_top', 'driver_name'],
                ['driver_licence_top', 'driver_licence_no']
            ];
            syncInputs.forEach(([topId, btmId]) => {
                const topEl = document.getElementById(topId);
                const btmEl = document.getElementById(btmId);
                if (topEl && btmEl) {
                    topEl.addEventListener('input', () => { btmEl.value = topEl.value; });
                    btmEl.addEventListener('input', () => { topEl.value = btmEl.value; });
                }
            });

            // Auto-calculate dip mm to litres for each compartment
            for (let i = 1; i <= 5; i++) {
                const dipInput = document.getElementById('dip_' + i);
                if (dipInput) {
                    dipInput.addEventListener('change', () => recalculateDip(i));
                }
            }

            function recalculateDip(compNo) {
                const dipVal = Number(document.getElementById('dip_' + compNo).value) || 0;
                const plate = plateSelect ? plateSelect.value : document.getElementById('plate_no').value;
                if (dipVal <= 0 || !plate) return;

                fetch("{{ route('petroleum.calculate-dip') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        plate_no: plate,
                        compartment_no: compNo,
                        dip_mm: dipVal
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.litres > 0) {
                        document.getElementById('vol_' + compNo).value = data.litres;
                        sumCompartments();
                    }
                })
                .catch(e => console.error(e));
            }

            // Quick Example Switchers
            document.getElementById('btnLoadShot1').addEventListener('click', function() {
                loadPreset({
                    reference: 'FDC-99995',
                    plate_no: 'A03843/*29988',
                    trailer: '29988',
                    carrier: 'Third Party Logistics',
                    driver: 'Kebede Tessema',
                    licence: 'DL-ETH-74312',
                    product_desc: 'JET-A1',
                    product_code: 'P07-0001',
                    ship_from_date: '2026-10-20',
                    ship_to_date: '2026-10-26',
                    dips: [1512, 1498, 1575, 1633, 0],
                    vols: [11395, 11850, 12667, 13223, 0],
                    start_density: 789.0,
                    start_temp: 19.50,
                    origin_vol20: 48710,
                    m_to_1: 162856152,
                    m_from_1: 162807512,
                    sg_dj_1: 0.7916,
                    sg_dj_2: 0.7923
                });
            });

            document.getElementById('btnLoadShot2').addEventListener('click', function() {
                loadPreset({
                    reference: '',
                    plate_no: 'A40983/*41304',
                    trailer: '41304',
                    carrier: 'Third Party (Dekebo)',
                    driver: 'Mohammed Ahmed',
                    licence: 'DL-ETH-88219',
                    product_desc: 'JET-A1',
                    product_code: 'P07-0001',
                    ship_from_date: '2026-10-22',
                    ship_to_date: '2026-10-26',
                    dips: [1395, 1850, 2667, 4381, 0],
                    vols: [11395, 11850, 12667, 14381, 0],
                    start_density: 787.0,
                    start_temp: 20.00,
                    origin_vol20: 49829,
                    m_to_1: 162906459,
                    m_from_1: 162856672,
                    sg_dj_1: 0.7916,
                    sg_dj_2: 0.7907
                });
            });

            function loadPreset(p) {
                document.getElementById('reference').value = p.reference;
                
                if (plateSelect) {
                    plateSelect.value = p.plate_no;
                }
                document.getElementById('plate_no').value = p.plate_no;
                if (p.trailer) document.getElementById('trailer_plate_no').value = p.trailer;
                if (p.carrier) document.getElementById('carrier_name').value = p.carrier;
                if (p.driver) {
                    document.getElementById('driver_name_top').value = p.driver;
                    document.getElementById('driver_name').value = p.driver;
                }
                if (p.licence) {
                    document.getElementById('driver_licence_top').value = p.licence;
                    document.getElementById('driver_licence_no').value = p.licence;
                }

                document.getElementById('ship_from_date').value = p.ship_from_date;
                document.getElementById('ship_to_date').value = p.ship_to_date;

                for (let i = 1; i <= 5; i++) {
                    document.getElementById('dip_' + i).value = p.dips[i-1];
                    document.getElementById('vol_' + i).value = p.vols[i-1];
                }

                document.getElementById('start_density').value = p.start_density;
                document.getElementById('start_temperature').value = p.start_temp;
                document.getElementById('origin_vol20').value = p.origin_vol20;

                document.getElementById('meter_to_1').value = p.m_to_1;
                document.getElementById('meter_from_1').value = p.m_from_1;
                document.getElementById('drained_1').value = 0;
                document.getElementById('meter_to_2').value = 0;
                document.getElementById('meter_from_2').value = 0;
                document.getElementById('drained_2').value = 0;

                document.getElementById('sg_15_dj_1').value = p.sg_dj_1;
                document.getElementById('sg_15_dj_2').value = p.sg_dj_2;

                sumCompartments();
            }

            productSelect.addEventListener('change', function() {
                const opt = this.options[this.selectedIndex];
                if (opt.dataset.name) document.getElementById('product_desc').value = opt.dataset.name;
                document.getElementById('product_code_disp').value = opt.value;
                if (opt.dataset.loss) document.getElementById('allowable_loss_percent').value = opt.dataset.loss;
                if (opt.dataset.price) document.getElementById('price_per_litre').value = opt.dataset.price;
                calculateAll();
            });

            // Auto-sum compartment volumes into Observed Volume
            const compVols = document.querySelectorAll('.comp-vol');
            compVols.forEach(inp => {
                inp.addEventListener('input', sumCompartments);
            });

            function sumCompartments() {
                let total = 0;
                compVols.forEach(inp => {
                    total += Number(inp.value) || 0;
                });
                document.getElementById('start_observed_litres').value = total;
                calculateAll();
            }

            // Meter inputs
            const meterInputs = ['meter_to_1', 'meter_from_1', 'drained_1', 'meter_to_2', 'meter_from_2', 'drained_2'];
            meterInputs.forEach(id => {
                document.getElementById(id).addEventListener('input', calculateAll);
            });

            // SG @ 15 DJ Difference calculation
            ['sg_15_dj_1', 'sg_15_dj_2'].forEach(id => {
                document.getElementById(id).addEventListener('input', function() {
                    const sg1 = Number(document.getElementById('sg_15_dj_1').value) || 0;
                    const sg2 = Number(document.getElementById('sg_15_dj_2').value) || 0;
                    const diff = sg1 - sg2;
                    const diffEl = document.getElementById('disp_sg_diff');
                    const sign = diff > 0 ? '+' : '';
                    diffEl.textContent = sign + diff.toFixed(4);
                    diffEl.className = 'font-mono font-bold ' + (diff < 0 ? 'text-rose-600' : 'text-emerald-700');
                    document.getElementById('sg_15_diff').value = diff.toFixed(4);
                });
            });

            // Watch all relevant fields
            ['start_density', 'start_temperature', 'origin_vol20', 'allowable_loss_percent', 'price_per_litre', 'use_metered']
                .forEach(id => {
                    document.getElementById(id).addEventListener('input', calculateAll);
                });

            btnRecalc.addEventListener('click', calculateAll);

            // Open Debit Note Voucher preview in new tab
            const btnActionDebit = document.getElementById('btnActionDebit');
            if (btnActionDebit) {
                btnActionDebit.addEventListener('click', function() {
                    const origAction = form.action;
                    const origTarget = form.target;
                    form.action = "{{ route('petroleum.debit-notes.preview') }}";
                    form.target = "_blank";
                    form.submit();
                    form.action = origAction;
                    form.target = origTarget;
                });
            }

            // Preview button triggers recalculation
            const btnActionPreview = document.getElementById('btnActionPreview');
            if (btnActionPreview) {
                btnActionPreview.addEventListener('click', function() {
                    calculateAll();
                });
            }

            function calculateAll() {
                const data = new FormData(form);

                // Auto-sum metered total locally
                const m1 = (Number(document.getElementById('meter_to_1').value) || 0)
                         - (Number(document.getElementById('meter_from_1').value) || 0)
                         + (Number(document.getElementById('drained_1').value) || 0);
                const m2 = (Number(document.getElementById('meter_to_2').value) || 0)
                         - (Number(document.getElementById('meter_from_2').value) || 0)
                         + (Number(document.getElementById('drained_2').value) || 0);
                const meteredTotal = m1 + m2;
                document.getElementById('disp_metered_total').textContent = meteredTotal.toLocaleString('en-US') + ' L';

                // SG 15 diff
                const sg1 = Number(document.getElementById('sg_15_dj_1').value) || 0;
                const sg2 = Number(document.getElementById('sg_15_dj_2').value) || 0;
                const sgDiff = sg1 - sg2;
                const diffEl = document.getElementById('disp_sg_diff');
                diffEl.textContent = (sgDiff > 0 ? '+' : '') + sgDiff.toFixed(4);
                diffEl.className = 'font-mono font-bold ' + (sgDiff < 0 ? 'text-rose-600' : 'text-emerald-700');

                // Call server calculation engine
                fetch("{{ route('petroleum.calculate') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: data
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        const c = res.calculations;
                        document.getElementById('disp_start_density_20').value = res.start_density_20.toFixed(2);
                        document.getElementById('disp_start_factor').value = res.start_factor.toFixed(6);

                        // Destination Vol 20
                        const dischargeVol20 = c.final_vol20;
                        document.getElementById('disp_final_vol20').value = Math.round(dischargeVol20);

                        // Shortage Volume (Origin - Discharge)
                        const loss = c.loss_volume;
                        document.getElementById('disp_shortage_vol').textContent = Math.round(loss) + ' L';

                        // Allowable Loss
                        document.getElementById('disp_allowed_loss').textContent = c.allowed_loss_volume + ' L (' + c.allowed_loss_2dp + ')';

                        // Debit note values
                        document.getElementById('disp_debit_vol').textContent = c.debit_volume;
                        document.getElementById('disp_debit_amt').textContent = (c.debit_amount).toFixed(2);

                        const branchEl = document.getElementById('disp_debit_branch');
                        if (c.loss_volume > 150) {
                            branchEl.className = 'text-[10px] px-2 py-0.5 rounded font-bold bg-rose-600 text-white uppercase';
                            branchEl.textContent = 'Branch 2 (> 150 L): Full loss debited';
                        } else if (c.debit_volume > 0) {
                            branchEl.className = 'text-[10px] px-2 py-0.5 rounded font-bold bg-amber-600 text-white uppercase';
                            branchEl.textContent = 'Branch 1 (<= 150 L): Excess >= 0.5 L';
                        } else {
                            branchEl.className = 'text-[10px] px-2 py-0.5 rounded font-bold bg-emerald-100 text-emerald-800 uppercase';
                            branchEl.textContent = 'No Debit (Loss <= Allowed)';
                        }
                    }
                })
                .catch(err => console.error(err));
            }

            // Run initial calculation on load
            calculateAll();
        });
    </script>
</body>
</html>
