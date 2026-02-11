<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/openapi.json', function () {
    try {
        return Generator::scan([app_path()])->toJson();
    }catch (\Exception $e) {
        abort(500, $e->getMessage());
    }
});
