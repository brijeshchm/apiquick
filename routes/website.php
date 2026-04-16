<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\Website\WebsiteController;
 

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

Route::get('/api/website/city/keyword', [WebsiteController::class, 'getSearch']);
Route::get('/api/website/homePage', [WebsiteController::class, 'homePage']);
//Route::get('/api/website/popularSearches', [WebsiteController::class, 'popularSearches']);
Route::get('/api/website/repairsServices', [WebsiteController::class, 'repairsServices']);
Route::get('/api/website/weddingPlanning', [WebsiteController::class, 'weddingPlanning']);
Route::get('/api/website/wedding-page', [WebsiteController::class, 'weddingPage']);
Route::get('/api/website/entranceExams', [WebsiteController::class, 'entranceExams']);
Route::get('/api/website/studyAbroad', [WebsiteController::class, 'studyAbroad']);
Route::get('/api/website/getKeyword', [WebsiteController::class, 'getKeyword']);
Route::get('/api/website/getBlog', [WebsiteController::class, 'getBlog']);
Route::get('/api/website/blog', [WebsiteController::class, 'getBlogDetails']);

 

 Route::get('/api/website/getCategories', [WebsiteController::class, 'getCategories']);
 Route::get('/api/website/categoryTabsFooter', [WebsiteController::class, 'categoryTabsFooter']);
 Route::get('/api/website/cityTabsFooter', [WebsiteController::class, 'cityTabsFooter']);


 Route::get('/api/website/searchCategories', [WebsiteController::class, 'searchCategories']);
 Route::get('/api/website/child', [WebsiteController::class, 'searchChild']);
 Route::get('/api/website/getChild', [WebsiteController::class, 'getChild']);
 Route::get('/api/website/searchChild ', [WebsiteController::class, 'searchChild']);
 Route::get('/api/website/home-slider', [WebsiteController::class, 'getHomeSlider']);
 Route::get('/api/website/getCityList', [WebsiteController::class, 'getCityList']);
 Route::get('/api/website/getZoneList', [WebsiteController::class, 'getZoneList']);
 Route::get('/api/website/get-keyword-list', [WebsiteController::class, 'getKeywordList']);
 Route::get('/api/website/getZoneByCityList', [WebsiteController::class, 'getZoneByCityList']);
 Route::get('/api/website/business-services', [WebsiteController::class, 'businessServices']);
 Route::get('/api/website/footer-links', [WebsiteController::class, 'footerLinks']);
 Route::get('/api/website/common-linked', [WebsiteController::class, 'commonLinked']);
  
 Route::get('/api/website/business-details/{slug}', [WebsiteController::class, 'businessDetails']);
 Route::get('/api/website/about-us', [WebsiteController::class, 'aboutus']);
 Route::get('/api/website/contact-us', [WebsiteController::class, 'contactus']);
 Route::get('/api/website/pricing', [WebsiteController::class, 'pricing']);
 Route::get('/api/website/privacy-policy', [WebsiteController::class, 'privacyPolicy']);
 Route::get('/api/website/terms-conditions', [WebsiteController::class, 'termsConditions']);
 Route::get('/api/website/copyright-policy', [WebsiteController::class, 'copyrightPolicy']);
 Route::get('/api/website/faq', [WebsiteController::class, 'FAQ']);
 Route::get('/api/website/business-owners', [WebsiteController::class, 'businessOwners']);
