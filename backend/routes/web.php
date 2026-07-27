<?php

use App\Http\Controllers\DocumentationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/api/documentation', [DocumentationController::class, 'ui'])->name('api.documentation');
Route::get('/api/documentation/openapi.yaml', [DocumentationController::class, 'spec'])->name('api.documentation.spec');
