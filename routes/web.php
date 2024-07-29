<?php
use App\Http\Controllers\CoordenadasController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/processar-csv', [CoordenadasController::class, 'processarCSV']);
