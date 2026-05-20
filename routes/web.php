<?php

use App\Http\Controllers\MaatregelenToolController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MaatregelenToolController::class, 'index'])->name('maatregelen.index');
Route::post('/maatregelen/preview', [MaatregelenToolController::class, 'preview'])->name('maatregelen.preview');
Route::redirect('/resultaat', '/');
