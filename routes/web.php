<?php

use App\Http\Controllers\CustomerInvoiceController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

// Trang khách hàng xác nhận hoá đơn nháp (không cần đăng nhập, bảo vệ bằng link riêng)
Route::prefix('xac-nhan/{token}')
    ->where(['token' => '[A-Za-z0-9]{40}'])
    ->name('customer.')
    ->middleware('throttle:60,1')
    ->group(function () {
        Route::get('/', [CustomerInvoiceController::class, 'show'])->name('show');
        Route::get('/nhap/{index}', [CustomerInvoiceController::class, 'draftFile'])->whereNumber('index')->name('draft-file');
        Route::get('/hoa-don', [CustomerInvoiceController::class, 'finalFile'])->name('final-file');
        Route::post('/xac-nhan', [CustomerInvoiceController::class, 'confirm'])->middleware('throttle:10,1')->name('confirm');
        Route::post('/yeu-cau-sua', [CustomerInvoiceController::class, 'revise'])->middleware('throttle:10,1')->name('revise');
    });
