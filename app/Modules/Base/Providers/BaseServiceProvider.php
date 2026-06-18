<?php

namespace App\Modules\Base\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class BaseServiceProvider extends ServiceProvider{

    public function register(){

        if(file_exists(__DIR__ . '/../Config/config.php')){

            $this->mergeConfigFrom(
                __DIR__ . '/../Config/config.php',
                'modules.cms'
            );
        }

        if(file_exists(__DIR__ . '/../Config/app_handle.php')){

            $moduleConfig = require __DIR__ . '/../Config/app_handle.php';

            $existing = config('app_handle', []);
            
            config([
                'app_handle' => array_merge($existing, $moduleConfig)
            ]);
        }
    }

    public function boot(){

        Route::middleware('api')
            ->prefix('api')
            ->group(__DIR__ . '/../Routes/api.php');
    }
}