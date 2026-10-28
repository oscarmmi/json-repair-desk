<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RepairController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->name('home');
Route::post('/repair', RepairController::class)->name('repair');
