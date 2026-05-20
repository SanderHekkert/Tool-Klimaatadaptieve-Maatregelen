<?php

use App\Http\Controllers\MaatregelenToolController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MaatregelenToolController::class, 'index'])->name('maatregelen.index');
Route::post('/resultaat', [MaatregelenToolController::class, 'result'])->name('maatregelen.result');
