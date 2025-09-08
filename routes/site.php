<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
 
 

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//  Route::get('/users', function () {
//     return response()->json(['message' => 'API is working!']);
// });

Route::get('/site/city/keyword', [App\Http\Controllers\Site\SiteController::class, 'getSearch']);
 

 