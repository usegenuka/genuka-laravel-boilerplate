<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth.genuka'])->get('/', function () {
    $company = request()->attributes->get('genuka_company');

    return view('welcome', ['company' => $company]);
});
