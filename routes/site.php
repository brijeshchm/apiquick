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
Route::get('/api/site/wedding-page', [App\Http\Controllers\Site\SiteController::class, 'weddingPage']);
Route::get('/api/site/entranceExams', [App\Http\Controllers\Site\SiteController::class, 'entranceExams']);
Route::get('/api/site/studyAbroad', [App\Http\Controllers\Site\SiteController::class, 'studyAbroad']);
Route::get('/api/site/getKeyword', [App\Http\Controllers\Site\SiteController::class, 'getKeyword']);
Route::get('/api/site/getBlog', [App\Http\Controllers\Site\SiteController::class, 'getBlog']);
Route::get('/api/site/blog/{slug}', [App\Http\Controllers\Site\SiteController::class, 'getBlogDetails']);

 

 Route::get('/api/site/categories', [App\Http\Controllers\Site\SiteController::class, 'getCategories']);
 Route::get('/api/site/child', [App\Http\Controllers\Site\SiteController::class, 'getChild']);
 Route::get('/api/site/home-slider', [App\Http\Controllers\Site\SiteController::class, 'getHomeSlider']);
 Route::get('/api/site/getCityList', [App\Http\Controllers\Site\SiteController::class, 'getCityList']);
 Route::get('/api/site/get-keyword-list', [App\Http\Controllers\Site\SiteController::class, 'getKeywordList']);
 Route::get('/api/site/getZoneByCityList', [App\Http\Controllers\Site\SiteController::class, 'getZoneByCityList']);
 Route::get('/api/site/business-services', [App\Http\Controllers\Site\SiteController::class, 'businessServices']);
 Route::get('/api/site/footer-links', [App\Http\Controllers\Site\SiteController::class, 'footerLinks']);
 Route::get('/api/site/common-linked', [App\Http\Controllers\Site\SiteController::class, 'commonLinked']);
  
 Route::get('/api/site/business-details/{slug}', [App\Http\Controllers\Site\SiteController::class, 'businessDetails']);
 Route::get('/api/site/about-us', [App\Http\Controllers\Site\SiteController::class, 'aboutus']);
 Route::get('/api/site/contact-us', [App\Http\Controllers\Site\SiteController::class, 'contactus']);
 Route::get('/api/site/pricing', [App\Http\Controllers\Site\SiteController::class, 'pricing']);
 Route::get('/api/site/privacy-policy', [App\Http\Controllers\Site\SiteController::class, 'privacyPolicy']);
 Route::get('/api/site/terms-conditions', [App\Http\Controllers\Site\SiteController::class, 'termsConditions']);
 Route::get('/api/site/copyright-policy', [App\Http\Controllers\Site\SiteController::class, 'copyrightPolicy']);
 Route::get('/api/site/faq', [App\Http\Controllers\Site\SiteController::class, 'FAQ']);
 Route::get('/api/site/business-owners', [App\Http\Controllers\Site\SiteController::class, 'businessOwners']);
