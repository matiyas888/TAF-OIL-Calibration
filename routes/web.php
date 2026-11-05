<?php

declare(strict_types=1);

use App\Http\Controllers\PetroleumController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PetroleumController::class, 'index'])->name('petroleum.index');
Route::post('/calculate', [PetroleumController::class, 'calculate'])->name('petroleum.calculate');
Route::get('/trucks/{plate}', [PetroleumController::class, 'getTruck'])->name('petroleum.trucks.get')->where('plate', '.*');
Route::post('/calculate-dip', [PetroleumController::class, 'calculateDip'])->name('petroleum.calculate-dip');
Route::post('/transfer-orders', [PetroleumController::class, 'store'])->name('petroleum.store');
Route::get('/transfer-orders/{to}/debit-note', [PetroleumController::class, 'debitNote'])->name('petroleum.debit-note');
Route::post('/debit-notes/preview', [PetroleumController::class, 'previewDebitNote'])->name('petroleum.debit-notes.preview');
Route::post('/debit-notes/manual', [PetroleumController::class, 'storeManualDebitNote'])->name('petroleum.debit-notes.manual');
Route::get('/debit-notes', fn () => redirect()->route('petroleum.tables', ['table' => 'debit_notes']))->name('petroleum.debit-notes.index');
Route::get('/tables', [PetroleumController::class, 'tables'])->name('petroleum.tables');
Route::post('/truck-calibrations', [PetroleumController::class, 'storeCalibration'])->name('petroleum.calibrations.store');
Route::post('/truck-calibrations/bulk', [PetroleumController::class, 'storeBulkCalibrations'])->name('petroleum.calibrations.bulk');
Route::delete('/truck-calibrations/{id}', [PetroleumController::class, 'destroyCalibration'])->name('petroleum.calibrations.destroy');
