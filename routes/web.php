<?php

use App\Http\Controllers\LocaleController;
use Illuminate\Support\Facades\Route;

Route::post('/locale/{locale}', LocaleController::class)
    ->whereIn('locale', array_keys(config('localization.supported', [])))
    ->name('locale.switch');

Route::get('/', function () {
    return view('welcome');
});
