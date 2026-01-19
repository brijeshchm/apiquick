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

		$common = [
			'business_name'          => trim($client->business_name),
			'customer_name' => trim($client->sirName).' '.$client->first_name.' '.$client->last_name,
			'email'         => trim($client->email),
			'phone'         => $client->mobile,
			'country'       => $client->country,
			'state'         => $client->state,
			'city'          => $client->city,
			'client_id'     => $client->id,
			'username'      => $client->username,
			'tds_status'    => 'No',
			'tds_amount'    => '0',
		];

		$packages = [
			'coins_1000'  => [1000, 1111], //0.90
			'coins_2000'  => [2000, 2272],//0.89
			'coins_3000'  => [3000, 3529],//0.86
			'coins_5000'  => [5000, 6099], //0.84
			'coins_10000' => [10000, 12500],//0.78
			// 'coins_20000' => [20000, 27777],//0.72
			// 'coins_40000' => [40000, 57777],//0.70
			// 'coins_50000' => [50000, 76923],//0.66
		];

		/* ✅ Free Package */
		if ($client->coins_free == '0') {
			$free = array_merge($common, [
				'amt' => 0,
				'gst_status' => 'No',
				'gst_tax' => '0',
				'gst_total_amount' => '0',
				'total_amount' => '0',
				'coins' => 555,
				'package' => 'Free Package',
			]);

			$free['encrypt'] = $this->dataEncodeJsonBase64($free);
			$data['coins_free'] = $free;
		}

		/* ✅ Paid Packages */
		foreach ($packages as $key => [$amount, $coins]) {

			$gst = round($amount * 0.18);

			$item = array_merge($common, [
				'amt' => $amount,
				'gst_status' => 'yes',
				'gst_tax' => $gst,
				'gst_total_amount' => $amount + $gst,
				'total_amount' => $amount + $gst,
				'coins' => $coins,
				'package' => "{$amount} Rs to {$coins} Coins",
			]);

			$item['encrypt'] = $this->dataEncodeJsonBase64($item);
			$data[$key] = $item;
		}


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
	 *     tags={"Account settings"},
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
