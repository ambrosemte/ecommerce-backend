<?php

use App\Http\Controllers\DeployController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/deploy', [DeployController::class, 'deploy'])->name('deploy') ->middleware('guest');
