<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Site\SiteController; 
 

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

Route::get('/api/site/city/keyword', [SiteController::class, 'getSearch']);
Route::get('/api/site/homePage', [SiteController::class, 'homePage']);
Route::get('/api/site/popularSearches', [SiteController::class, 'popularSearches']);
Route::get('/api/site/repairsServices', [SiteController::class, 'repairsServices']);
Route::get('/api/site/weddingPlanning', [SiteController::class, 'weddingPlanning']);
Route::get('/api/site/wedding-page', [SiteController::class, 'weddingPage']);
Route::get('/api/site/entranceExams', [SiteController::class, 'entranceExams']);
Route::get('/api/site/studyAbroad', [SiteController::class, 'studyAbroad']);
Route::get('/api/site/getKeyword', [SiteController::class, 'getKeyword']);
Route::get('/api/site/getBlog', [SiteController::class, 'getBlog']);
Route::get('/api/site/blog/{slug}', [SiteController::class, 'getBlogDetails']);

 

 Route::get('/api/site/categories', [SiteController::class, 'getCategories']);
 Route::get('/api/site/child', [SiteController::class, 'getChild']);
 Route::get('/api/site/home-slider', [SiteController::class, 'getHomeSlider']);
 Route::get('/api/site/getCityList', [SiteController::class, 'getCityList']);
 Route::get('/api/site/getZoneList', [SiteController::class, 'getZoneList']);
 Route::get('/api/site/get-keyword-list', [SiteController::class, 'getKeywordList']);
 Route::get('/api/site/getZoneByCityList', [SiteController::class, 'getZoneByCityList']);
 Route::get('/api/site/business-services', [SiteController::class, 'businessServices']);
 Route::get('/api/site/footer-links', [SiteController::class, 'footerLinks']);
 Route::get('/api/site/common-linked', [SiteController::class, 'commonLinked']);
  
 Route::get('/api/site/business-details/{slug}', [SiteController::class, 'businessDetails']);
 Route::get('/api/site/about-us', [SiteController::class, 'aboutus']);
 Route::get('/api/site/contact-us', [SiteController::class, 'contactus']);
 Route::get('/api/site/pricing', [SiteController::class, 'pricing']);
 Route::get('/api/site/privacy-policy', [SiteController::class, 'privacyPolicy']);
 Route::get('/api/site/terms-conditions', [SiteController::class, 'termsConditions']);
 Route::get('/api/site/copyright-policy', [SiteController::class, 'copyrightPolicy']);
 Route::get('/api/site/faq', [SiteController::class, 'FAQ']);
 Route::get('/api/site/business-owners', [SiteController::class, 'businessOwners']);
