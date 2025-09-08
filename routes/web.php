<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });
 
require __DIR__.'/site.php'; 

Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});