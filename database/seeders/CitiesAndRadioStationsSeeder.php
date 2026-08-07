<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\RadioStation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitiesAndRadioStationsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $cities = [
                'Казань' => [
                    [
                        'name' => 'Серебряный Дождь',
                        'frequency' => '88.3',
                    ],
                    [
                        'name' => 'Гордость',
                        'frequency' => '97.2',
                    ],
                ],

                'Набережные Челны' => [
                    [
                        'name' => 'Гордость',
                        'frequency' => '87.9',
                    ],
                    [
                        'name' => 'Радио родных дорог',
                        'frequency' => '90.6',
                    ],
                    [
                        'name' => 'Радио Monte Carlo',
                        'frequency' => '89.5',
                    ],
                ],
            ];

            foreach ($cities as $cityName => $stations) {
                $city = City::updateOrCreate(
                    [
                        'name' => $cityName,
                    ],
                    []
                );

                foreach ($stations as $station) {
                    RadioStation::updateOrCreate(
                        [
                            'city_id' => $city->id,
                            'name' => $station['name'],
                            'frequency' => $station['frequency'],
                        ],
                        [
                            'price_per_second' => null,
                        ]
                    );
                }
            }
        });
    }
}