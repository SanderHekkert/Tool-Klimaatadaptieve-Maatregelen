<?php

use App\Http\Controllers\MaatregelenToolController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MaatregelenToolController::class, 'index'])->name('maatregelen.index');
Route::post('/maatregelen/start', [MaatregelenToolController::class, 'start'])->name('maatregelen.start');
Route::get('/maatregelen/tool', [MaatregelenToolController::class, 'tool'])->name('maatregelen.tool');
Route::post('/maatregelen/preview', [MaatregelenToolController::class, 'preview'])->name('maatregelen.preview');
Route::post('/maatregelen/pdf', [MaatregelenToolController::class, 'downloadPdf'])->name('maatregelen.pdf');
Route::redirect('/resultaat', '/');
