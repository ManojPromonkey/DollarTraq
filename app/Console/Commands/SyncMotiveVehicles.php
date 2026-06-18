<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Motive\MotiveApiService;
use App\Models\Motive\MotiveVehicle;

class SyncMotiveVehicles extends Command
{
    protected $signature = 'motive:vehicles-sync';

    protected $description = 'Sync Motive Vehicles';

    public function handle()
    {
        $motive = app(MotiveApiService::class);

        $response = $motive->get('/v1/vehicles');

        if(!isset($response['data'])){

            $this->error('Invalid response');

            return;
        }

        foreach($response['data'] as $vehicle){

            MotiveVehicle::updateOrCreate(
                [
                    'motive_id' => $vehicle['id']
                ],
                [
                    'number'   => $vehicle['number'] ?? null,
                    'make'     => $vehicle['make'] ?? null,
                    'model'    => $vehicle['model'] ?? null,
                    'raw_data' => $vehicle,
                ]
            );
        }

        $this->info('Vehicles Synced');
    }
}