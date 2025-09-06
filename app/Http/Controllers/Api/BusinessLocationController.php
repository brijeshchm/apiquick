<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Client\Client;
use Validator;
use App\Models\Zone;
use App\Models\Citieslists;
use App\Models\AssignedZone;
use App\Models\State;

class BusinessLocationController extends Controller
{
	protected $danger_message = '';
	protected $success_message = '';
	protected $warning_message = '';
	protected $info_message = '';
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
	 *     path="/api/business/location-information",
	 *     tags={"Location Information"},
	 *     summary="Get location information",
	 *     description="Fetch the location information of the authenticated user's account or business.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="Location information retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="address", type="string", example="123 MG Road, Sector 45"),
	 *                 @OA\Property(property="city", type="string", example="Noida"),
	 *                 @OA\Property(property="state", type="string", example="Uttar Pradesh"),
	 *                 @OA\Property(property="country", type="string", example="India"),
	 *                 @OA\Property(property="pincode", type="string", example="201301"),
	 *                 @OA\Property(property="latitude", type="string", example="28.5355"),
	 *                 @OA\Property(property="longitude", type="string", example="77.3910")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */


	public function locationInformation(Request $request)
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
	 
		$data['clientDetails'] = Client::find($user->id);

		$search = [];
		if ($request->has('search')) {
			$data['search'] = $request->input('search');
		}
	echo json_encode($data);	

		 
	}
	/**
	 * @OA\Post(
	 *     path="/api/business/saveBusinessLocation",
	 *     tags={"Location Information"},
	 *     summary="Update location information",
	 *     description="Update the authenticated user's business or account location.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"address","city","state","country","pincode"},
	 *             @OA\Property(property="address", type="string", example="123 MG Road, Sector 45"),
	 *             @OA\Property(property="city", type="string", example="Noida"),
	 *             @OA\Property(property="state", type="string", example="Uttar Pradesh"),
	 *             @OA\Property(property="country", type="string", example="India"),
	 *             @OA\Property(property="pincode", type="string", example="201301"),
	 *             @OA\Property(property="latitude", type="string", example="28.5355"),
	 *             @OA\Property(property="longitude", type="string", example="77.3910")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Location updated successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Location updated successfully.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation error",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
	 *             @OA\Property(property="errors", type="object",
	 *                 @OA\Property(property="address", type="array",
	 *                     @OA\Items(type="string", example="The address field is required.")
	 *                 )
	 *             )
	 *         )
	 *     )
	 * )
	 */
	public function saveBusinessLocation(Request $request, $id)
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

			if ($request->input('zone_id') == "Other") {


				$validator = Validator::make($request->all(), [
					'city_id' => 'required|max:25',
					//'other' 	=> 'required|regex:/^[\pL\s\-]+$/u|min:3|max:32',	
					'other' => 'required|min:3|max:32|regex:/^(?!.*(.)\1{3,}).+$/',
				]);


			} else {
				$validator = Validator::make($request->all(), [
					'city_id' => 'required|max:255',
					'zone_id' => 'required|max:255',

				]);
			}

			if ($validator->fails()) {
				$errorsBag = $validator->getMessageBag()->toArray();
				return response()->json(['status' => 1, 'errors' => $errorsBag], 400);
			}
			$assignedZone = new AssignedZone;
			$assignedZone->city_id = $request->input('city_id');
			if ($request->input('zone_id') == "Other") {
				$checkZone = Zone::where('zone', $request->input('other'))->where('city_id', $request->input('city_id'))->first();
				if (empty($checkZone)) {
					$zone = new Zone;
					$zone->city_id = $request->input('city_id');
					$zone->zone = ucfirst($request->input('other'));
					$zone->save();
					$zone_id = $zone->id;
				} else {
					$zone_id = $checkZone->id;
				}

			} else {
				$zone_id = $request->input('zone_id');
			}
			$assignedZone->zone_id = $zone_id;
			$assignedZone->client_id = $request->input('client_id');

			$checkAssignedZone = AssignedZone::where('client_id', $request->input('client_id'))->where('zone_id', $zone_id)->where('city_id', $request->input('city_id'))->first();

			if (empty($checkAssignedZone)) {
				if ($assignedZone->save()) {

					$data['status'] = true;
					$data['message'] = "Business Location updated successfully !";
				} else {
					$data['status'] = 0;
					$data['message'] = "Business Location could not be successfully, Please try again !";
				}
			} else {
				$data['status'] = 0;
				$data['message'] = "Already exists <strong>" . $request->input('other') . "</strong> Please add right zone !";
			}

		echo json_encode($data);	

	}

		
	/**
	 * @OA\Post(
	 *     path="/api/business/saveLocationInformation",
	 *     tags={"Location Information"},
	 *     summary="Save or update business location information",
	 *     description="Save or update the authenticated user's business or account location details.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"business_name","address","city","state","country"},
	 *             @OA\Property(property="business_name", type="string", example="ABC Enterprises"),
	 *             @OA\Property(property="landmark", type="string", example="Near Sector 18 Metro Station"),
	 *             @OA\Property(property="address", type="string", example="123 MG Road, Sector 45"),
	 *             @OA\Property(property="city", type="string", example="Noida"),
	 *             @OA\Property(property="business_city", type="string", example="Delhi NCR"),
	 *             @OA\Property(property="state", type="string", example="Uttar Pradesh"),
	 *             @OA\Property(property="country", type="string", example="India"),
	 *             @OA\Property(property="pincode", type="string", example="201301"),
	 *             @OA\Property(property="latitude", type="string", example="28.5355"),
	 *             @OA\Property(property="longitude", type="string", example="77.3910")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Location information saved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Location updated successfully."),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="business_name", type="string", example="ABC Enterprises"),
	 *                 @OA\Property(property="landmark", type="string", example="Near Sector 18 Metro Station"),
	 *                 @OA\Property(property="address", type="string", example="123 MG Road, Sector 45"),
	 *                 @OA\Property(property="city", type="string", example="Noida"),
	 *                 @OA\Property(property="business_city", type="string", example="Delhi NCR"),
	 *                 @OA\Property(property="state", type="string", example="Uttar Pradesh"),
	 *                 @OA\Property(property="country", type="string", example="India"),
	 *                 @OA\Property(property="pincode", type="string", example="201301"),
	 *                 @OA\Property(property="latitude", type="string", example="28.5355"),
	 *                 @OA\Property(property="longitude", type="string", example="77.3910")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation error",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
	 *             @OA\Property(property="errors", type="object",
	 *                 @OA\Property(
	 *                     property="business_name",
	 *                     type="array",
	 *                     @OA\Items(type="string", example="The business name field is required.")
	 *                 )
	 *             )
	 *         )
	 *     )
	 * )
	 */
	public function saveLocationInformation(Request $request)
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

			$client = Client::find($request->input('business_id'));
			$id = $request->input('business_id');
			$messages = ['mobile.regex' => 'Mobile number cannot start with 0.'];
			$validator = Validator::make($request->all(), [
				'business_name' => 'required|unique:clients,business_name,' . $id . ',id,city,' . $request->input('city'),
				'landmark' => 'regex:/[a-zA-z ]$/',
				'city' => 'required|regex:/[a-zA-z ]+$/',
				'state' => 'required|regex:/[a-zA-z ()]+$/',
				'country' => 'required|regex:/[a-zA-z ]+$/',

			]);
			if ($validator->fails()) {
				$errorsBag = $validator->getMessageBag()->toArray();
				return response()->json(['status' => true, 'errors' => $errorsBag], 400);
			}
			$string = $request->input('business_name');
			$string = filter_var($string, FILTER_SANITIZE_STRING);
			$string = preg_replace('/[^A-Za-z0-9]/', ' ', $string);
			$string = preg_replace('/\s+/', ' ', str_replace('&', '', trim($string)));

			$client->business_name = $string;
			$client->address = $request->input('address');
			$client->landmark = $request->input('landmark');
			$client->business_city = $request->input('city');
			$client->state = $request->input('state');
			$client->country = $request->input('country');

			if ($client->save()) {
				$data['clientDetails'] = Client::find($id);
				$data['status'] = true;
				$data['message'] = "Location Information Updated Successfully";
				 
			} else {
				$data['status'] = false;
				$data['message'] = "Location Information not assigned";
				 
			}
			echo json_encode($data);	


	}


	/**
	 * @OA\Get(
	 *     path="/api/business/get-business-location",
	 *     tags={"Business Location"},
	 *     summary="Get business location",
	 *     description="Fetches the saved business location details of the authenticated user.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="Business location retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="business_name", type="string", example="Quick Dials Pvt Ltd"),
	 *                 @OA\Property(property="address", type="string", example="123 MG Road, Sector 45"),
	 *                 @OA\Property(property="landmark", type="string", example="Near Metro Station"),
	 *                 @OA\Property(property="business_city", type="string", example="Noida"),
	 *                 @OA\Property(property="state", type="string", example="Uttar Pradesh"),
	 *                 @OA\Property(property="country", type="string", example="India"),
	 *                 @OA\Property(property="pincode", type="string", example="201301"),
	 *                 @OA\Property(property="latitude", type="string", example="28.5355"),
	 *                 @OA\Property(property="longitude", type="string", example="77.3910")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */


	public function getBusinessLocation(Request $request)
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
		 
		$data['clientDetails'] = Client::find($user->id);
		$data['search'] = [];
		if ($request->has('search')) {
			$data['search'] = $request->input('search');
		}
		$data['statesis'] = State::get();
		echo json_encode($data);	

	}
}
