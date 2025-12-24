<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Client\Client; //model
use Validator;
use App\Models\Occupation;

use App\Models\Citieslists;
use App\Models\State;
class PersonalDetailsController extends Controller
{
	protected $redirectTo = '/business-owners';

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
	 *     path="/api/business/personal-details",
	 *     operationId="getPersonalDetails",
	 *     tags={"Profile"},
	 *     summary="Get personal details",
	 *     description="Fetch the personal details of the authenticated user",
	 *     security={{"bearerAuth":{}}},
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Personal details retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Personal details retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="object",
	 *
	 *                 @OA\Property(
	 *                     property="occupation",
	 *                     type="array",
	 *                     @OA\Items(
	 *                         @OA\Property(property="id", type="integer", example=1),
	 *                         @OA\Property(property="name", type="string", example="Software Engineer"),
	 *                         @OA\Property(property="status", type="integer", example=1)
	 *                     )
	 *                 ),
	 *
	 *                 @OA\Property(
	 *                     property="cities",
	 *                     type="array",
	 *                     @OA\Items(
	 *                         @OA\Property(property="id", type="integer", example=10),
	 *                         @OA\Property(property="city", type="string", example="Delhi"),
	 *                         @OA\Property(property="state_id", type="integer", example=5)
	 *                     )
	 *                 ),
	 *
	 *                 @OA\Property(
	 *                     property="edit_data",
	 *                     type="object",
	 *                     @OA\Property(property="client_id", type="integer", example=101),
	 *                     @OA\Property(property="sirName", type="string", example="Mr"),
	 *                     @OA\Property(property="first_name", type="string", example="John"),
	 *                     @OA\Property(property="middle_name", type="string", example="A"),
	 *                     @OA\Property(property="last_name", type="string", example="Doe"),
	 *                     @OA\Property(property="dob", type="string", format="date", example="1995-08-15"),
	 *                     @OA\Property(property="personal_email", type="string", example="john@example.com"),
	 *                     @OA\Property(property="marital", type="string", example="Single"),
	 *                     @OA\Property(property="mobile", type="string", example="9876543210"),
	 *                     @OA\Property(property="sec_mobile", type="string", example="9123456789"),
	 *                     @OA\Property(property="city_id", type="integer", example=10),
	 *                     @OA\Property(property="city", type="string", example="Delhi"),
	 *                     @OA\Property(property="state_id", type="integer", example=5),
	 *                     @OA\Property(property="state", type="string", example="Delhi"),
	 *                     @OA\Property(property="country", type="string", example="India"),
	 *                     @OA\Property(property="area", type="string", example="Karol Bagh"),
	 *                     @OA\Property(property="pincode", type="string", example="110005"),
	 *                     @OA\Property(property="gender", type="string", example="Male"),
	 *                     @OA\Property(property="occupation", type="string", example="Engineer")
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Unauthenticated: Token is missing or invalid")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=403,
	 *         description="Inactive user",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="User account is inactive")
	 *         )
	 *     )
	 * )
	 */


	public function personalDetails(Request $request)
	{
		try {

			// ✅ Sanctum authentication
			$authUser = auth('sanctum')->user();
			if (!$authUser) {
				return response()->json([
					'status' => false,
					'message' => 'Unauthenticated: Token is missing or invalid',
					'error' => 'token_missing_or_invalid'
				], 401);
			}

			// ✅ Active user check
			if (!$authUser->active_status) {
				$authUser->tokens()->delete();
				return response()->json([
					'status' => false,
					'message' => 'User account is inactive'
				], 403);
			}

			// ✅ Get client
			$client = Client::where('id', $authUser->id)->first();
			if (!$client) {
				return response()->json([
					'status' => false,
					'message' => 'Client not found'
				], 404);
			}


			// ✅ Response data
			$data = [				 
					'client_id' => $client->id,
					'sirName' => $client->sirName,
					'first_name' => $client->first_name,
					'middle_name' => $client->middle_name,
					'last_name' => $client->last_name,
					'dob' => date('Y-m-d',strtotime($client->dob)),
					'personal_email' => $client->personal_email,
					'marital' => $client->marital,
					'personal_phone' => $client->personal_phone,				 
					'personal_city_id' => $client->personal_city_id,
					'personal_city' => $client->personal_city,
					'personal_state_id' => $client->personal_state_id,
					'personal_state' => $client->personal_state,
					'country' => $client->country,
					'personal_area' => $client->personal_area,
					'personal_pincode' => $client->personal_pincode,
					'personal_zone' => $client->personal_zone,
					'gender' => $client->gender,
					'occupation' => $client->occupation,
				 
			];

			return response()->json([
				'status' => true,
				'message' => 'Personal details retrieved successfully',
				'data' => $data
			], 200);

		} catch (\Exception $e) {
			return response()->json([
				'status' => false,
				'message' => 'Something went wrong',
				'error' => $e->getMessage()
			], 500);
		}
	}

	/**
	 * @OA\Post(
	 *     path="/api/business/savePersonalDetails",
	 *     operationId="savePersonalDetails",
	 *     tags={"Profile"},
	 *     summary="Save or update personal details",
	 *     description="Save or update the personal details of the authenticated user",
	 *     security={{"bearerAuth":{}}},
	 *
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"sirName","first_name","dob","personal_email","mobile","city","state","country"},
	 *             @OA\Property(property="sirName", type="string", example="Mr"),
	 *             @OA\Property(property="first_name", type="string", example="John"),
	 *             @OA\Property(property="middle_name", type="string", example="A"),
	 *             @OA\Property(property="last_name", type="string", example="Doe"),
	 *             @OA\Property(property="dob", type="string", format="date", example="1990-01-01"),
	 *             @OA\Property(property="personal_email", type="string", format="email", example="john@example.com"),
	 *             @OA\Property(property="marital", type="string", example="Single"),
	 *             @OA\Property(property="personal_phone", type="string", example="9876543210"),
	 *           
	 *             @OA\Property(property="personal_city", type="integer", example=10),
	 *             @OA\Property(property="personal_state", type="integer", example=5),
	 *             @OA\Property(property="country", type="string", example="India"),
	 *             @OA\Property(property="personal_area", type="string", example="Connaught Place"),
	 *             @OA\Property(property="personal_address", type="string", example="Sector Delhi"),
	 *             @OA\Property(property="personal_pincode", type="string", example="110001"),
	 *             @OA\Property(property="occupation", type="string", example="Software Engineer"),
	 *             @OA\Property(property="gender", type="string", example="Male")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Personal details updated successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Personal details updated successfully")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation failed",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="errors", type="object")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Unauthenticated")
	 *         )
	 *     )
	 * )
	 */


	public function savePersonalDetails(Request $request)
	{
		try {

			// ✅ Sanctum authentication
			$authUser = auth('sanctum')->user();
			if (!$authUser) {
				return response()->json([
					'status' => false,
					'message' => 'Unauthenticated: Token is missing or invalid'
				], 401);
			}

			// ✅ Active user check
			if (!$authUser->active_status) {
				$authUser->tokens()->delete();
				return response()->json([
					'status' => false,
					'message' => 'User account is inactive'
				], 403);
			}

			// ✅ Client fetch (correct mapping)
			$client = Client::where('id', $authUser->id)->first();
			if (!$client) {
				return response()->json([
					'status' => false,
					'message' => 'Client not found'
				], 404);
			}

			// ✅ Validation
			$validator = Validator::make($request->all(), [
				'sirName' => 'required|string|max:15',
				'first_name' => 'required|string|min:3|max:255',
				'dob' => 'required|date|before:today',
				'personal_email' => 'required|email',
				'marital' => 'required|string',
				'personal_phone' => 'required|max:15',
				'personal_city' => 'required|integer|exists:citylists,id',
				'personal_state' => 'required|integer|exists:state,id',
				'country' => 'required|string|max:100',
				'middle_name' => 'nullable|string|max:255',
				'last_name' => 'nullable|string|max:255',				 
				'personal_area' => 'nullable|string|max:255',
				'personal_address' => 'nullable|string|max:255',
				'personal_pincode' => 'nullable|digits:6',
				'occupation' => 'nullable|string|max:255',
				'gender' => 'nullable|in:Male,Female,Other',
			]);

			if ($validator->fails()) {
				return response()->json([
					'status' => false,
					'message' => 'Validation failed',
					'errors' => $validator->errors()
				], 422);
			}

			// ✅ Lookups
			$city = Citieslists::find($request->personal_city);
			$state = State::find($request->personal_state);

			// ✅ Update client
			$client->update([
				'sirName' => $request->sirName,
				'first_name' => ucfirst($request->first_name),
				'middle_name' => $request->middle_name,
				'last_name' => $request->last_name,
				'dob' => $request->dob,
				'personal_email' => $request->personal_email,
				'marital' => $request->marital,
				'personal_phone' => $request->personal_phone,
				 
				'personal_city_id' => $city?->id,
				'personal_city' => $city?->city,
				'personal_state_id' => $state?->id,
				'personal_state' => $state?->name,
				'country' => $request->country,
				'personal_area' => $request->personal_area,
				'personal_zone' => $request->personal_zone,
				'personal_address' => $request->personal_address,
				'personal_pincode' => $request->personal_pincode,
				'occupation' => $request->occupation,
				'gender' => $request->gender,
			]);

			return response()->json([
				'status' => true,
				'message' => 'Personal details updated successfully',
				'data' => $client
			], 200);

		} catch (\Exception $e) {
			return response()->json([
				'status' => false,
				'message' => 'Something went wrong',
				'error' => $e->getMessage()
			], 500);
		}
	}




	/**
	 * @OA\Get(
	 *     path="/api/business/get-occupation",
	 *     tags={"Occupation"},
	 *     summary="Get Occupation",
	 *     description="Fetch the Occupation of the authenticated user",
	 * 	   security={{"bearerAuth":{}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="Occupation retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="object",
	 *                 @OA\Property(property="id", type="integer", example=101),
	 *                 @OA\Property(property="Occupation", type="string", example="Software engineer")
	 *                 
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Unauthorized access")
	 *         )
	 *     )
	 * )
	 */

	public function getOccupation(Request $request)
	{

		$occupations = Occupation::where('status', '1')->get();
		if (!empty($occupations)) {
			foreach ($occupations as $key => $val) {

				$occupations_list[$key] = array(
					'id' => $val->id,
					'name' => $val->name,


				);
			}
			$data['occupations'] = $occupations_list;
		}

		return response()->json([
			'status' => true,
			'data' => $data,

		], 200);
	}

}