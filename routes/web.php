<?php

use Illuminate\Support\Facades\Route;

// Kiedy użytkownik wejdzie na adres http://127.0.0.1:8000/, zobaczy nasz dashboard
Route::get('/', function () {
    return view('dashboard');
});