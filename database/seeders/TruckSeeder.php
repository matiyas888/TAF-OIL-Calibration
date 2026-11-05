<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TruckCalibration;
use App\Models\TruckHeader;
use Illuminate\Database\Seeder;

class TruckSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $trucks = [
            [
                'plate_no' => 'A40983/*41304',
                'truck_no' => 'A40983',
                'trailer_plate_no' => '41304',
                'carrier_name' => 'Third Party (Dekebo)',
                'driver_name' => 'Mohammed Ahmed',
                'driver_licence_no' => 'DL-ETH-88219',
                'compartment_count' => 5,
                'total_capacity' => 50293.00,
                'calibrations' => [
                    // Comp 1 (dip 1395 -> 11395 L)
                    ['comp' => 1, 'dip' => 0, 'litres' => 0],
                    ['comp' => 1, 'dip' => 700, 'litres' => 5700],
                    ['comp' => 1, 'dip' => 1395, 'litres' => 11395],
                    // Comp 2 (dip 1850 -> 11850 L)
                    ['comp' => 2, 'dip' => 0, 'litres' => 0],
                    ['comp' => 2, 'dip' => 900, 'litres' => 5800],
                    ['comp' => 2, 'dip' => 1850, 'litres' => 11850],
                    // Comp 3 (dip 2667 -> 12667 L)
                    ['comp' => 3, 'dip' => 0, 'litres' => 0],
                    ['comp' => 3, 'dip' => 1300, 'litres' => 6200],
                    ['comp' => 3, 'dip' => 2667, 'litres' => 12667],
                    // Comp 4 (dip 4381 -> 14381 L)
                    ['comp' => 4, 'dip' => 0, 'litres' => 0],
                    ['comp' => 4, 'dip' => 2000, 'litres' => 6500],
                    ['comp' => 4, 'dip' => 4381, 'litres' => 14381],
                    // Comp 5
                    ['comp' => 5, 'dip' => 0, 'litres' => 0],
                ],
            ],
            [
                'plate_no' => 'A03843/*29988',
                'truck_no' => 'A03843',
                'trailer_plate_no' => '29988',
                'carrier_name' => 'Third Party Logistics',
                'driver_name' => 'Kebede Tessema',
                'driver_licence_no' => 'DL-ETH-74312',
                'compartment_count' => 5,
                'total_capacity' => 49135.00,
                'calibrations' => [
                    // Comp 1 (dip 1512 -> 11395 L)
                    ['comp' => 1, 'dip' => 0, 'litres' => 0],
                    ['comp' => 1, 'dip' => 1512, 'litres' => 11395],
                    // Comp 2 (dip 1498 -> 11850 L)
                    ['comp' => 2, 'dip' => 0, 'litres' => 0],
                    ['comp' => 2, 'dip' => 1498, 'litres' => 11850],
                    // Comp 3 (dip 1575 -> 12667 L)
                    ['comp' => 3, 'dip' => 0, 'litres' => 0],
                    ['comp' => 3, 'dip' => 1575, 'litres' => 12667],
                    // Comp 4 (dip 1633 -> 13223 L)
                    ['comp' => 4, 'dip' => 0, 'litres' => 0],
                    ['comp' => 4, 'dip' => 1633, 'litres' => 13223],
                    // Comp 5
                    ['comp' => 5, 'dip' => 0, 'litres' => 0],
                ],
            ],
            [
                'plate_no' => '23417/*36454',
                'truck_no' => '23417',
                'trailer_plate_no' => '36454',
                'carrier_name' => 'TAF Oil Fleet',
                'driver_name' => 'Abebe Bekele',
                'driver_licence_no' => 'DL-ETH-65011',
                'compartment_count' => 5,
                'total_capacity' => 47062.00,
                'calibrations' => [],
            ],
            [
                'plate_no' => '43401/*31600',
                'truck_no' => '43401',
                'trailer_plate_no' => '31600',
                'carrier_name' => 'TAF Oil Fleet',
                'driver_name' => 'Mulugeta Haile',
                'driver_licence_no' => 'DL-ETH-72109',
                'compartment_count' => 5,
                'total_capacity' => 47483.00,
                'calibrations' => [],
            ],
            [
                'plate_no' => '4327/*32112',
                'truck_no' => '4327',
                'trailer_plate_no' => '32112',
                'carrier_name' => 'TAF Oil Fleet',
                'driver_name' => 'Dawit Yohannes',
                'driver_licence_no' => 'DL-ETH-59844',
                'compartment_count' => 5,
                'total_capacity' => 48802.00,
                'calibrations' => [],
            ],
            [
                'plate_no' => '23190/*36439',
                'truck_no' => '23190',
                'trailer_plate_no' => '36439',
                'carrier_name' => 'TAF Oil Fleet',
                'driver_name' => 'Girma Wolde',
                'driver_licence_no' => 'DL-ETH-81203',
                'compartment_count' => 5,
                'total_capacity' => 47284.00,
                'calibrations' => [],
            ],
            [
                'plate_no' => '27619/*38149',
                'truck_no' => '27619',
                'trailer_plate_no' => '38149',
                'carrier_name' => 'TAF Oil Fleet',
                'driver_name' => 'Solomon Tesfaye',
                'driver_licence_no' => 'DL-ETH-90231',
                'compartment_count' => 5,
                'total_capacity' => 46981.00,
                'calibrations' => [],
            ],
            [
                'plate_no' => 'A42163/*42143',
                'truck_no' => 'A42163',
                'trailer_plate_no' => '42143',
                'carrier_name' => 'Dekebo Transport',
                'driver_name' => 'Berhanu Legesse',
                'driver_licence_no' => 'DL-ETH-61405',
                'compartment_count' => 5,
                'total_capacity' => 51026.00,
                'calibrations' => [],
            ],
            [
                'plate_no' => 'A11621/*33405',
                'truck_no' => 'A11621',
                'trailer_plate_no' => '33405',
                'carrier_name' => 'Dekebo Transport',
                'driver_name' => 'Kassahun Desta',
                'driver_licence_no' => 'DL-ETH-77890',
                'compartment_count' => 5,
                'total_capacity' => 52018.00,
                'calibrations' => [],
            ],
            [
                'plate_no' => 'A42171/*42145',
                'truck_no' => 'A42171',
                'trailer_plate_no' => '42145',
                'carrier_name' => 'Third Party Logistics',
                'driver_name' => 'Fikadu Negash',
                'driver_licence_no' => 'DL-ETH-44321',
                'compartment_count' => 5,
                'total_capacity' => 49633.00,
                'calibrations' => [],
            ],
            [
                'plate_no' => 'A42168/*42144',
                'truck_no' => 'A42168',
                'trailer_plate_no' => '42144',
                'carrier_name' => 'Third Party Logistics',
                'driver_name' => 'Hailu Lemma',
                'driver_licence_no' => 'DL-ETH-55672',
                'compartment_count' => 5,
                'total_capacity' => 49868.00,
                'calibrations' => [],
            ],
            [
                'plate_no' => 'A03864/*31836',
                'truck_no' => 'A03864',
                'trailer_plate_no' => '31836',
                'carrier_name' => 'Third Party Logistics',
                'driver_name' => 'Yonas Mekonnen',
                'driver_licence_no' => 'DL-ETH-33219',
                'compartment_count' => 5,
                'total_capacity' => 47200.00,
                'calibrations' => [],
            ],
            [
                'plate_no' => 'A34811/*08620',
                'truck_no' => 'A34811',
                'trailer_plate_no' => '08620',
                'carrier_name' => 'Dekebo Transport',
                'driver_name' => 'Tekle Woldemariam',
                'driver_licence_no' => 'DL-ETH-99120',
                'compartment_count' => 5,
                'total_capacity' => 47570.00,
                'calibrations' => [],
            ],
        ];

        foreach ($trucks as $t) {
            $truck = TruckHeader::query()->updateOrCreate(
                ['plate_no' => $t['plate_no']],
                [
                    'truck_no' => $t['truck_no'],
                    'trailer_plate_no' => $t['trailer_plate_no'],
                    'carrier_name' => $t['carrier_name'],
                    'driver_name' => $t['driver_name'],
                    'driver_licence_no' => $t['driver_licence_no'],
                    'compartment_count' => $t['compartment_count'],
                    'total_capacity' => $t['total_capacity'],
                ]
            );

            if (! empty($t['calibrations'])) {
                foreach ($t['calibrations'] as $cal) {
                    TruckCalibration::query()->updateOrCreate(
                        [
                            'plate_no' => $t['plate_no'],
                            'compartment_no' => $cal['comp'],
                            'dip_mm' => $cal['dip'],
                        ],
                        [
                            'litres' => $cal['litres'],
                        ]
                    );
                }
            }
        }
    }
}
