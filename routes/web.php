<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/administration/dashboard', function () {
    return view('administration.dashboard');
});

Route::get('/applications/verify', function () {
    return view('applications.verify');
});
