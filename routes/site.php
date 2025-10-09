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

Route::get('/api/site/city/keyword', [App\Http\Controllers\Site\SiteController::class, 'getSearch']);
Route::get('/api/site/homePage', [App\Http\Controllers\Site\SiteController::class, 'homePage']);
Route::get('/api/site/popularSearches', [App\Http\Controllers\Site\SiteController::class, 'popularSearches']);
Route::get('/api/site/repairsServices', [App\Http\Controllers\Site\SiteController::class, 'repairsServices']);
Route::get('/api/site/weddingPlanning', [App\Http\Controllers\Site\SiteController::class, 'weddingPlanning']);
Route::get('/api/site/entranceExams', [App\Http\Controllers\Site\SiteController::class, 'entranceExams']);
Route::get('/api/site/studyAbroad', [App\Http\Controllers\Site\SiteController::class, 'studyAbroad']);
Route::get('/api/site/getKeyword', [App\Http\Controllers\Site\SiteController::class, 'getKeyword']);
Route::get('/api/site/getBlog-homepage', [App\Http\Controllers\Site\SiteController::class, 'getBlogHomepage']);
 

 