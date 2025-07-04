<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QueryController;

Route::post('/query', [QueryController::class, 'execute']);
