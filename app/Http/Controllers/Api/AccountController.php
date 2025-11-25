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

		$data['client'] = Client::find($user->id);
		
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
