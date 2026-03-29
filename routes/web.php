<?php

use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });
use Illuminate\Support\Facades\Redis;
Route::get('/redis-test', function () {
    Redis::set('test_key', 'QuickDials');
    return Redis::get('test_key');
});
  


require __DIR__.'/site.php'; 
require __DIR__.'/website.php'; 

Route::get('/test', function () {
    return response()->json(['message' => 'API is working!']);
});