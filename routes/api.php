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

Route::get('/users', [App\Http\Controllers\Api\ApiController::class, 'index']);
Route::post('/upload-documents', [App\Http\Controllers\Api\ApiController::class, 'uploadpdf']);

Route::get('/razorpay/verify',[App\Http\Controllers\Api\RazorpayController::class,'verifyPayment']);
Route::get('/razorpay/webhook',[App\Http\Controllers\Api\RazorpayController::class,'webhook']);
 
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeadBusinessController;
use App\Http\Controllers\Api\ProfileController;
 use App\Http\Controllers\Api\EnquiryController;

Route::post('/login', [AuthController::class, 'login']);


Route::post('/verifyOtp', [AuthController::class, 'verifyOtp']);
Route::post('/business/saveBusinessOwners', [AuthController::class, 'saveBusinessOwners']); 
Route::post('/business/{client_id}/saveReview', [App\Http\Controllers\Api\ReviewController::class, 'store']);
Route::post('/site/saveEnquiry', [App\Http\Controllers\Api\ContactController::class, 'saveEnquiry']);

Route::middleware('auth:sanctum')->group(function () {
    
Route::post('/logout', [AuthController::class, 'logout']);
Route::get('/business/profileInfo', [ProfileController::class, 'profileInfo']);
Route::get('/business/review', [ProfileController::class, 'profileReview']);
Route::get('/business/dashboard',[App\Http\Controllers\Api\BusinessDashboardController::class, 'dashboard']);
Route::get('/business/get-leads',[EnquiryController::class, 'getLeads']);
Route::post('/business/save-favorite',[EnquiryController::class, 'saveFavoritleads']);
Route::post('/business/un-favorite',[EnquiryController::class, 'unFavoritleads']);
Route::post('/business/save-readLead',[EnquiryController::class, 'readLead']);
Route::post('/business/scrap-lead',[EnquiryController::class, 'scrapLead']);
Route::post('/business/pause-lead',[EnquiryController::class, 'pauseLead']);
Route::get('/business/{id}/follow-up',[EnquiryController::class, 'getFollowUps']);
Route::post('/business/{id}/save-follow-up',[EnquiryController::class, 'storeFollowUp']);


Route::get('/business/get-scrap/{assignId}',[EnquiryController::class, 'getScrapLead']);
Route::post('/business/save-scrap-lead',[EnquiryController::class, 'saveScrapLead']);
Route::get('/business/enquiry',[EnquiryController::class,'enquiry']);
Route::get('/business/get-new-enquiry',[EnquiryController::class,'getNewEnquiry']);
Route::get('/business/get-myLead',[EnquiryController::class,'getMyLead']);
Route::get('/business/get-favorite-enquiry',[EnquiryController::class,'getFavoriteEnquiry']);
Route::get('/business/get-lead-details/{id}',[EnquiryController::class,'getLeadDetails']);
Route::get('/business/manage-enquiry',[EnquiryController::class,'manageEnquiry']);
Route::get('/business/export-enquiry-download',[EnquiryController::class,'exportEnquiryDownload']);
Route::get('/business/export-enquiry',[EnquiryController::class,'exportEnquiry']);
Route::get('/business/get-discussion',[App\Http\Controllers\Api\BusinessDiscussionController::class, 'getDiscussion']);
Route::get('/business/get-paginated-assigned-keywords',[App\Http\Controllers\Api\BusinessKeywordController::class, 'getPaginatedAssignedKeywords']);
 
Route::get('/business/personal-details',[App\Http\Controllers\Api\PersonalDetailsController::class, 'personalDetails']);
Route::get('/business/get-occupation',[App\Http\Controllers\Api\PersonalDetailsController::class, 'getOccupation']);
 
Route::post('/business/saveProfileInfo',[App\Http\Controllers\Api\ProfileController::class, 'saveProfileInfo']);
Route::post('/business/saveBusinessLocation',[App\Http\Controllers\Api\BusinessLocationController::class, 'saveBusinessLocation']);
Route::post('/business/savePersonalDetails',[App\Http\Controllers\Api\PersonalDetailsController::class, 'savePersonalDetails']);


Route::get('/business/social-media',[App\Http\Controllers\Api\PersonalDetailsController::class, 'getSocialMedia']);
Route::post('/business/saveSocialMedia',[App\Http\Controllers\Api\PersonalDetailsController::class, 'saveSocialMedia']);


Route::get('/business/profile-logo',[App\Http\Controllers\Api\BusinessLogoController::class, 'getProfileLogo']);
Route::post('https://www.quickdials.com/api/business/saveProfileLogo',[App\Http\Controllers\Api\BusinessLogoController::class, 'saveProfileLogo']);
Route::delete('https://www.quickdials.com/api/business/profileLogo/logoDel/{business_id}',[App\Http\Controllers\Api\BusinessLogoController::class, 'deleteLogo']);
Route::delete('https://www.quickdials.com/api/business/profileLogo/profilePicDel/{business_id}',[App\Http\Controllers\Api\BusinessLogoController::class, 'deleteProfilePic']);

Route::get('/business/get-gallery-pictures',[App\Http\Controllers\Api\BusinessLogoController::class, 'getGalleryPictures']);

Route::post('https://www.quickdials.com/api/business/save-gallery',[App\Http\Controllers\Api\BusinessLogoController::class,'saveGallary']); 
Route::post('https://www.quickdials.com/api/business/save-pictures',[App\Http\Controllers\Api\BusinessLogoController::class,'savePictures']); 

Route::get('/business/business-location',[App\Http\Controllers\Api\BusinessLocationController::class, 'businessLocation']);
Route::post('/business/saveLocationInformation',[App\Http\Controllers\Api\BusinessLocationController::class, 'saveLocationInformation']);


Route::post('/business/pauseLead',[App\Http\Controllers\Api\EnquiryController::class, 'pauseLead']);
Route::post('/business/scrapLead',[App\Http\Controllers\Api\EnquiryController::class, 'scrapLead']);
Route::post('/business/readLead',[App\Http\Controllers\Api\EnquiryController::class, 'readLead']);
 
Route::get('/business/cities/get-cities',[App\Http\Controllers\Api\BusinessController::class, 'getCities']);
Route::post('/business/cities/getajaxcities',[App\Http\Controllers\Api\BusinessController::class, 'getAjaxCities']);
Route::get('/business/state/get-state',[App\Http\Controllers\Api\BusinessController::class, 'getState']);
Route::get('/business/country/get-country',[App\Http\Controllers\Api\BusinessController::class, 'getCountry']);
Route::post('/business/city/get-city-by-state',[App\Http\Controllers\Api\BusinessController::class, 'getCityByState']);
Route::post('/business/zones/get-zone-by-city',[App\Http\Controllers\Api\BusinessController::class, 'getZoneByCity']);
Route::get('/business/zones/get-zones',[App\Http\Controllers\Api\BusinessController::class, 'getZones']);
Route::post('/business/area/get-area-by-zone',[App\Http\Controllers\Api\BusinessController::class, 'getAreaByZone']);
Route::get('/business/area/get-area',[App\Http\Controllers\Api\BusinessController::class, 'getArea']);
  
Route::post('/business/state/get-state-by-country',[App\Http\Controllers\Api\BusinessController::class, 'getStateByCountry']);
  
Route::get('/business/get-assigned-zones',[App\Http\Controllers\Api\BusinessController::class, 'getAssignedZonesPagination']);
Route::delete('/business/assignZone/delete/{id}',[App\Http\Controllers\Api\BusinessController::class, 'assignZoneDelete']);
Route::post('/business/assignLocation/selectAssignZoneDelete',[App\Http\Controllers\Api\BusinessController::class, 'selectAssignZoneDelete']);


Route::get('/business/getPackage',[App\Http\Controllers\Api\AccountController::class, 'getPackage']);
Route::get('/business/account-settings',[App\Http\Controllers\Api\AccountController::class, 'accountSettings']);
Route::get('/business/get-business-location',[App\Http\Controllers\Api\BusinessLocationController::class, 'getBusinessLocationPagination']);
Route::Delete('/business/business-location/{id}',[App\Http\Controllers\Api\BusinessLocationController::class, 'destroy']);

Route::get('/business/buy-package',[App\Http\Controllers\Api\AccountController::class, 'buyPackage']);

Route::get('/business/get-invoice-history',[App\Http\Controllers\Api\InvoiceController::class, 'getInvoiceHistory']);

Route::get('/business/get-billing-history',[App\Http\Controllers\Api\InvoiceController::class, 'getBillingHistory']);

Route::get('/business/download-invoice/{id}',[App\Http\Controllers\Api\InvoiceController::class, 'downloadInvoicePdf']);

Route::get('/business/coinsHistory',[App\Http\Controllers\Api\InvoiceController::class, 'coinsHistory']);

Route::get('/business/get-paginated-payment-history',[App\Http\Controllers\Api\InvoiceController::class, 'getPaginatedPaymentHistory']);


 Route::get('/business/help',[App\Http\Controllers\Api\BusinessController::class,'help']);
 Route::get('/business/businessActiveStatus/{id}/{val}',[App\Http\Controllers\Api\BusinessController::class,'businessActiveStatus']);
 
 Route::get('/business/get-enquiry',[App\Http\Controllers\Api\EnquiryController::class,'getEnquiry']);
 Route::get('/business/enquiry/follow-up/{id}',[App\Http\Controllers\Api\EnquiryController::class,'followUp']);
 Route::post('/business/enquiry/store-follow-up/{id}',[App\Http\Controllers\Api\EnquiryController::class,'storeFollowUp']);
 Route::get('/business/enquiry/getfollowups/{id}',[App\Http\Controllers\Api\EnquiryController::class,'getFollowUps']);  


 Route::get('/business/get-keywords',[App\Http\Controllers\Api\BusinessKeywordController::class,'getKeywords']); 


Route::post('/business/saveKeywordAssign',[App\Http\Controllers\Api\BusinessKeywordController::class,'saveKeywordAssign']); 
Route::get('/business/assignKeyword/delete/{id}',[App\Http\Controllers\Api\BusinessKeywordController::class, 'assignKeywordDelete']);
 


 Route::get('/business/coins-history',[App\Http\Controllers\Api\InvoiceController::class,'coinsHistory']);
 

 Route::post('/business/razorpay/create-payment-link',[App\Http\Controllers\Api\RazorpayController::class,'createPaymentLink']);
 




});

 