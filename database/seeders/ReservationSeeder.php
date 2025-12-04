<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Reservation;
use App\Models\User;
use App\Models\Resource;

class ReservationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        //  Reservation::create([
        //      'user_id' => 1,
        //      'reservation_time'=>'2025-12-01 08:48:00',
        //      'guests'=>4,
        //      'note'=>'Szülinapi vacsi'
        // ]);

        Reservation::factory(20)->create(); 
    }
}
