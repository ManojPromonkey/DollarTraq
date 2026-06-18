<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Motive\MotiveApiService;

class SyncMotiveLocations extends Command
{
    protected $signature = 'motive:locations-sync';

    protected $description = 'Sync Motive Vehicle Locations';

    public function handle()
    {
        $motive = app(MotiveApiService::class);

        $locations = $motive->get('/v3/vehicle_locations');

        dd($locations);
    }
}