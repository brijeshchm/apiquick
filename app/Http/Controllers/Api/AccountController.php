<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Client\Client;

class AccountController extends Controller
{
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct(Request $request)
	{

	}
	/**
	 * @OA\Get(
	 *     path="/api/business/getPackage",
	 *     tags={"Package"},
	 *     summary="Get all packages",
	 *     description="Fetch all available packages for businesses.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="List of packages",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     type="object",
	 *                     @OA\Property(property="id", type="integer", example=1),
	 *                     @OA\Property(property="name", type="string", example="Premium Business Plan"),
	 *                     @OA\Property(property="price", type="number", format="float", example=4999.00),
	 *                     @OA\Property(property="duration", type="string", example="12 months"),
	 *                     @OA\Property(property="features", type="array",
	 *                         @OA\Items(type="string", example="24/7 Support")
	 *                     ),
	 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-04T10:00:00Z")
	 *                 )
	 *             )
	 *         )
	 *     )
	 * )
	 */
	public function getPackage(Request $request)
	{
		if (!Auth::guard('sanctum')->check()) {
			return response()->json([
				'status' => false,
				'message' => 'Unauthenticated: Token is missing or invalid',
				'error' => 'token_missing_or_invalid'
			], 401);
		}

		$user = auth('sanctum')->user();
		if (!$user) {
			return response()->json([
				'status' => false,
				'message' => 'Unauthenticated: Token is missing or invalid',
				'error' => 'token_missing_or_invalid'
			], 401);
		}

		$client = Client::find($user->id);
		$data = [];

		  if($client->coins_free=='0'){                            
                     
			$datafree['name'] = trim($client->business_name);
			$datafree['email'] = trim($client->email);
			$datafree['amt'] = trim(0);
			$datafree['phone'] = $client->mobile;
			$datafree['coins'] = trim(555);
			$datafree['country'] = $client->country;
			$datafree['state'] = $client->state;
			$datafree['city'] = $client->city;
			$datafree['id'] = $client->id;
			$datafree['username'] = $client->username;
			$datafree['package'] = "Free Package";
			$datafree['encrypt'] = $this->dataEncodeJsonBase64($datafree); 
			          
			$data['coins_free'] = $datafree;  
           }
	                         
                     
			$data1['name'] = trim($client->business_name);
			$data1['email'] = trim($client->email);
			$data1['amt'] = trim(1000);
			$data1['phone'] = $client->mobile;
			$data1['coins'] = trim(1111);
			$data1['country'] = $client->country;
			$data1['state'] = $client->state;
			$data1['city'] = $client->city;
			$data1['id'] = $client->id;
			$data1['username'] = $client->username;
			$data1['package'] = "1000 Rs to 1111 Coins";
			$data1['encrypt'] = $this->dataEncodeJsonBase64($data1); 			          
			$data['coins_1000'] = $data1;  

			$data2['name'] = trim($client->business_name);
			$data2['email'] = trim($client->email);
			$data2['amt'] = trim(2000);
			$data2['phone'] = $client->mobile;
			$data2['coins'] = trim(2272);
			$data2['country'] = $client->country;
			$data2['state'] = $client->state;
			$data2['city'] = $client->city;
			$data2['id'] = $client->id;
			$data2['username'] = $client->username;
			$data2['package'] = "2000 Rs to 2272 Coins";
			$data2['encrypt'] = $this->dataEncodeJsonBase64($data2); 			          
			$data['coins_2000'] = $data2;  

			$data3['name'] = trim($client->business_name);
			$data3['email'] = trim($client->email);
			$data3['amt'] = trim(3000);
			$data3['phone'] = $client->mobile;
			$data3['coins'] = trim(3529);
			$data3['country'] = $client->country;
			$data3['state'] = $client->state;
			$data3['city'] = $client->city;
			$data3['id'] = $client->id;
			$data3['username'] = $client->username;
			$data3['package'] = "3000 Rs to 3529 Coins";
			$data3['encrypt'] = $this->dataEncodeJsonBase64($data3); 			          
			$data['coins_3000'] = $data3;  

			$data4['name'] = trim($client->business_name);
			$data4['email'] = trim($client->email);
			$data4['amt'] = trim(5000);
			$data4['phone'] = $client->mobile;
			$data4['coins'] = trim(6099);
			$data4['country'] = $client->country;
			$data4['state'] = $client->state;
			$data4['city'] = $client->city;
			$data4['id'] = $client->id;
			$data4['username'] = $client->username;
			$data4['package'] = "5000 Rs to 6099 Coins";
			$data4['encrypt'] = $this->dataEncodeJsonBase64($data4); 			          
			$data['coins_5000'] = $data4;  

			$data5['name'] = trim($client->business_name);
			$data5['email'] = trim($client->email);
			$data5['amt'] = trim(10000);
			$data5['phone'] = $client->mobile;
			$data5['coins'] = trim(12500);
			$data5['country'] = $client->country;
			$data5['state'] = $client->state;
			$data5['city'] = $client->city;
			$data5['id'] = $client->id;
			$data5['username'] = $client->username;
			$data5['package'] = "10000 Rs to 12500 Coins";
			$data5['encrypt'] = $this->dataEncodeJsonBase64($data5); 			          
			$data['coins_10000'] = $data5;  
 



		 return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $data,

		], 200);
	}

	function dataEncodeJsonBase64($o){
				$o = json_encode($o);
				$o = base64_encode($o);
				return $o;
	}
	function dataDecodeJsonBase64($o){
				$o = base64_decode($o);
				$o = json_decode($o); 
				
				return $o;
	}

	/**
	 * @OA\Get(
	 *     path="/api/business/account-settings",
	 *     tags={"account settings"},
	 *     summary="Get all packages",
	 *     description="Fetch all available packages for businesses.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="List of packages",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     type="object",
	 *                     @OA\Property(property="id", type="integer", example=1),
	 *                     @OA\Property(property="name", type="string", example="Premium Business Plan"),
	 *                     @OA\Property(property="price", type="number", format="float", example=4999.00),
	 *                     @OA\Property(property="duration", type="string", example="12 months"),
	 *                     @OA\Property(property="features", type="array",
	 *                         @OA\Items(type="string", example="24/7 Support")
	 *                     ),
	 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-04T10:00:00Z")
	 *                 )
	 *             )
	 *         )
	 *     )
	 * )
	 */
	public function accountSettings(Request $request)
	{
		if (!Auth::guard('sanctum')->check()) {
			return response()->json([
				'status' => false,
				'message' => 'Unauthenticated: Token is missing or invalid',
				'error' => 'token_missing_or_invalid'
			], 401);
		}

		$user = auth('sanctum')->user();
		if (!$user) {
			return response()->json([
				'status' => false,
				'message' => 'Unauthenticated: Token is missing or invalid',
				'error' => 'token_missing_or_invalid'
			], 401);
		}
		$data['client'] = Client::find($user->id);
		 return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $data,

		], 200);
	}
	/**
	 * @OA\Get(
	 *     path="/api/business/buy-package",
	 *     tags={"Buy Package"},
	 *     summary="Get all buy-package",
	 *     description="Fetch all available packages for businesses.",
	 * 	   security={{"bearerAuth":{}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="List of packages",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     type="object",
	 *                     @OA\Property(property="id", type="integer", example=1),
	 *                     @OA\Property(property="name", type="string", example="Premium Business Plan"),
	 *                     @OA\Property(property="price", type="number", format="float", example=4999.00),
	 *                     @OA\Property(property="duration", type="string", example="12 months"),
	 *                     @OA\Property(property="features", type="array",
	 *                         @OA\Items(type="string", example="24/7 Support")
	 *                     ),
	 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-04T10:00:00Z")
	 *                 )
	 *             )
	 *         )
	 *     )
	 * )
	 */
	public function buyPackage(Request $request)
	{
		if (!Auth::guard('sanctum')->check()) {
			return response()->json([
				'status' => false,
				'message' => 'Unauthenticated: Token is missing or invalid',
				'error' => 'token_missing_or_invalid'
			], 401);
		}

		$user = auth('sanctum')->user();
		if (!$user) {
			return response()->json([
				'status' => false,
				'message' => 'Unauthenticated: Token is missing or invalid',
				'error' => 'token_missing_or_invalid'
			], 401);
		}
		$data['client'] = Client::find($user->id);
		 return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $data,

		], 200);
	}
}
