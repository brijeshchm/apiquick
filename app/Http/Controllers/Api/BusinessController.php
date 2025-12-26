<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Client\Client; //model
use Validator;
use Illuminate\Support\Facades\Input;
use Image;
use DB;
use Mail;
use Excel;
use session;
use App\Http\Controllers\SitemapsController as SMC;
use App\Models\PaymentHistory;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Models\Zone;
use App\Models\Country;
use App\Models\Area;
use App\Models\Lead;
use App\Models\User;
use App\Models\Keyword;
use App\Models\LeadFollowUp;
use App\Models\Status;
use App\Models\AssignedLead;
use App\Models\State;

use App\Models\Occupation;
use App\Models\Citieslists;
use App\Models\AssignedZone;
use App\Models\KeywordSellCount;
use App\Models\Client\AssignedKWDS;

class BusinessController extends Controller
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
	 *     path="/api/business/get-assigned-zones",
	 *     tags={"business-location"},
	 *     summary="Get paginated assigned zones",
	 *     description="Fetch a paginated list of zones assigned to the authenticated user.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="page",
	 *         in="query",
	 *         required=false,
	 *         description="Page number for pagination",
	 *         @OA\Schema(type="integer", example=1)
	 *     ),
	 *     @OA\Parameter(
	 *         name="length",
	 *         in="query",
	 *         required=false,
	 *         description="Number of records per page",
	 *         @OA\Schema(type="integer", example=10)
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Paginated zones list retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="draw", type="integer", example=1),
	 *             @OA\Property(property="recordsTotal", type="integer", example=50),
	 *             @OA\Property(property="recordsFiltered", type="integer", example=50),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=1),
	 *                     @OA\Property(property="zone", type="string", example="North Zone"),
	 *                     @OA\Property(property="created_at", type="string", example="2025-09-04 10:30:00")
	 *                 )
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

	public function getAssignedZonesPagination(Request $request)
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
		$perPage = $request->query('per_page', 10);
		$leads = DB::table('assigned_zones')
			->join('zones', 'assigned_zones.zone_id', '=', 'zones.id')
			->join('citylists', 'assigned_zones.city_id', '=', 'citylists.id')
			->join('state', 'assigned_zones.state_id', '=', 'state.id')
			->select('assigned_zones.*', 'citylists.city', 'zones.zone', 'assigned_zones.id as assign_id', 'state.*', 'state.name as state_name')
			->orderBy('assigned_zones.id', 'desc')
			->where('assigned_zones.client_id', $user->id)
			//->paginate($request->input('length'));
			->paginate($perPage);
		$leads_list = [];
		if (!empty($leads)) {
			foreach ($leads->items() as $key => $val) {
				if (!empty($val->zone)) {
					$zonename = $val->zone;
				} else {
					$zonename = "";
				}

				$leads_list[$key] = array(
					'assignZone_id' => $val->assign_id,
					'city_id' => $val->city_id,
					'city' => $val->city,
					'zone_id' => $val->zone_id,
					'zone' => $zonename,
					'state_id' => $val->state_id,
					'state' => $val->state_name,

				);
			}
			$data = $leads_list;
		}
		return response()->json([
			'status' => true,
			'current_page' => $leads->currentPage(),
			'per_page' => $leads->perPage(),
			'total' => $leads->total(),
			'last_page' => $leads->lastPage(),
			'data' => $data,



		], 200);

	}

	/**
	 * @OA\Get(
	 *     path="/api/business/cities/get-cities",
	 *     tags={"Cities"},
	 *     summary="Get cities by state",
	 *     description="Fetch a list of cities dynamically based on state_id (used for AJAX calls in dropdowns).",
	 *     security={{"bearerAuth":{}}},
	 * 		@OA\Parameter(
	 *         name="city",
	 *         in="query",
	 *         required=false,
	 *         description="Search city",
	 *         @OA\Schema(type="string", example="noida")
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Cities retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="city", type="string", example="Noida")
	 *                 )
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
	 *         response=404,
	 *         description="No cities found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No cities found for this state.")
	 *         )
	 *     )
	 * )
	 */

	public function getCities(Request $request)
	{
		// Validate input
		$request->validate([
			'city' => 'nullable|string|min:1|max:100',
		]);

		$city = trim($request->input('city'));

		$query = DB::table('citylists')
			->select('id as city_id', 'city')
			->orderBy('city', 'asc');

		// Default cities when search is empty
		if ($city === null || $city === '') {
			$query->whereIn('id', [
				278,
				596,
				961,
				428,
				29,
				1100,
				1003,
				1002,
				917,
				874,
				758,
				643
			]);
		} else {
			$query->where('city', 'LIKE', '%' . $city . '%');
		}

		$locations = $query->limit(20)->get();

		return response()->json([
			'status' => true,
			'message' => 'Successfully',
			'data' => $locations, // empty array allowed
		], 200);
	}

	/**
	 * @OA\Post(
	 *     path="/api/business/city/get-city-by-state",
	 *     tags={"Cities"},
	 *     summary="Get cities by state",
	 *     description="Fetch a list of cities dynamically based on state_id (AJAX dropdown support).",
	 *     security={{"bearerAuth":{}}},
	 *
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"state_id"},
	 *             @OA\Property(
	 *                 property="state_id",
	 *                 type="integer",
	 *                 example=10,
	 *                 description="ID of the state"
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Cities retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="city_id", type="integer", example=101),
	 *                     @OA\Property(property="city", type="string", example="Noida")
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=404,
	 *         description="No cities found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No cities found for this state.")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation Error",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
	 *             @OA\Property(property="errors", type="object")
	 *         )
	 *     )
	 * )
	 */
	public function getCityByState(Request $request)
	{
		// ✅ Validation
		$validator = Validator::make($request->all(), [
			'state_id' => 'required|integer|exists:states,id',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'message' => 'Validation failed',
				'errors' => $validator->errors(),
			], 422);
		}

		$stateId = $request->input('state_id');

		// ✅ Fetch cities
		$cities = Citieslists::where('state_id', $stateId)
			->select('id as city_id', 'city', 'state_id')
			->get();

		// ✅ Empty check (correct way)
		if ($cities->isEmpty()) {
			return response()->json([
				'status' => false,
				'message' => 'City not found',
				'data' => [],
			], 404);
		}

		return response()->json([
			'status' => true,
			'message' => 'Successfully',
			'data' => $cities,
		], 200);
	}

	/**
	 * @OA\Get(
	 *     path="/api/business/country/get-country",
	 *     tags={"Country"},
	 *     summary="Get cities by state",
	 *     description="Fetch a list of cities dynamically based on state_id (used for AJAX calls in dropdowns).",
	 *     security={{"bearerAuth":{}}},	 *   
	 *     @OA\Response(
	 *         response=200,
	 *         description="Cities retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="city", type="string", example="Noida")
	 *                 )
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
	 *         response=404,
	 *         description="No cities found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No cities found for this state.")
	 *         )
	 *     )
	 * )
	 */

	public function getCountry(Request $request)
	{

		$countrylists = Country::get();
		if ($countrylists) {
			foreach ($countrylists as $country) {
				$data[] = [
					'country_id' => $country->id,
					'country' => $country->name,

				];
			}
		}
		return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $data,

		], 200);
	}


	/**
	 * @OA\Post(
	 *     path="/api/business/state/get-state-by-country",
	 *     tags={"State"},
	 *     summary="Get state by Coutry",
	 *     description="Fetch a list of state  by country.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="country_id",
	 *         in="query",
	 *         required=true,
	 *         description="ID of the country",
	 *         @OA\Schema(type="integer", example=101)
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Cities retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="country", type="string", example="india")
	 *                 )
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
	 *         response=404,
	 *         description="No country found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No cities found for this state.")
	 *         )
	 *     )
	 * )
	 */
	public function getStateByCountry(Request $request)
	{
		$cid = $request->input('country_id');

		$data = [];
		$statelist = State::where('country_id', $cid)->get();

		if (!$statelist) {
			return response()->json([
				'status' => true,
				'message' => "State not Found",
				'data' => '',

			], 200);
		}
		if ($statelist) {
			foreach ($statelist as $state) {
				$data[] = [
					'state_id' => $state->id,
					'state' => $state->name,
					'coutry_id' => $state->country_id,

				];
			}
		}
		return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $data,

		], 200);
	}
	/**
	 * @OA\Get(
	 *     path="/api/business/zones/get-zones",
	 *     tags={"Zones"},
	 *     summary="Get zones list",
	 *     description="Fetch a list of zones .",
	 *     security={{"bearerAuth":{}}},   
	 *     @OA\Response(
	 *         response=200,
	 *         description="Zones retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=4),
	 *                     @OA\Property(property="zones", type="string", example="South Delhi")
	 *                 )
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
	 *         response=404,
	 *         description="No zones found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No cities found for this state.")
	 *         )
	 *     )
	 * )
	 */

	public function getZones(Request $request)
	{

		$zonelists = Zone::get();
		if ($zonelists) {
			foreach ($zonelists as $zone) {
				$data[] = [
					'zone_id' => $zone->id,
					'zone' => $zone->zone,

				];
			}
		}
		return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $data,

		], 200);
	}


	/**
	 * @OA\Post(
	 *     path="/api/business/zones/get-zone-by-city",
	 *     tags={"Zones"},
	 *     summary="Get zone by city",
	 *     description="Fetch a list of zones dynamically based on city_id (used for AJAX calls in dropdowns).",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="city_id",
	 *         in="query",
	 *         required=true,
	 *         description="ID of the city",
	 *         @OA\Schema(type="integer", example=278)
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Zones retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="city", type="string", example="Noida")
	 *                 )
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
	 *         response=404,
	 *         description="No cities found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No cities found for this state.")
	 *         )
	 *     )
	 * )
	 */
	public function getZoneByCity(Request $request)
	{
		$cid = $request->input('city_id');
		$zid = $request->input('zid');
		$data = [];
		$zoneslist = Zone::where('city_id', $cid)->get();

		if (!$zoneslist) {
			return response()->json([
				'status' => true,
				'message' => "Zone not Found",
				'data' => '',

			], 200);
		}
		if ($zoneslist) {
			foreach ($zoneslist as $zone) {
				$data[] = [
					'zone_id' => $zone->id,
					'zone' => $zone->zone,
					'city_id' => $zone->city_id,

				];
			}
		}
		$data[] = [
			'zone_id' => 'Other',
			'zone' => 'Other',
		];
		return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $data,

		], 200);
	}

	/**
	 * @OA\Get(
	 *     path="/api/business/area/get-area",
	 *     tags={"Area"},
	 *     summary="Get Area list",
	 *     description="Fetch a list of areas .",
	 *     security={{"bearerAuth":{}}},   
	 *     @OA\Response(
	 *         response=200,
	 *         description="Area retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=9),
	 *                     @OA\Property(property="area", type="string", example="Sector-2")
	 *                 )
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
	 *         response=404,
	 *         description="No area found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No cities found for this state.")
	 *         )
	 *     )
	 * )
	 */

	public function getArea(Request $request)
	{

		$arealists = Area::get();
		if ($arealists) {
			foreach ($arealists as $area) {
				$data[] = [
					'area_id' => $area->id,
					'area' => $area->area,

				];
			}
		}
		return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $data,

		], 200);
	}


	/**
	 * @OA\Post(
	 *     path="/api/business/area/get-area-by-zone",
	 *     tags={"Area"},
	 *     summary="Get area by zone",
	 *     description="Fetch a list of area.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="zone_id",
	 *         in="query",
	 *         required=true,
	 *         description="ID of the zone",
	 *         @OA\Schema(type="integer", example=12)
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Area retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=9),
	 *                     @OA\Property(property="area", type="string", example="Sector-2")
	 *                 )
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
	 *         response=404,
	 *         description="No area found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No cities found for this state.")
	 *         )
	 *     )
	 * )
	 */
	public function getAreaByZone(Request $request)
	{
		$zid = $request->input('zone_id');

		$data = [];
		$areaslist = Area::where('zone_id', $zid)->get();

		if (!$areaslist) {
			return response()->json([
				'status' => true,
				'message' => "Area not Found",
				'data' => '',

			], 200);
		}
		if ($areaslist) {
			foreach ($areaslist as $area) {
				$data[] = [
					'area_id' => $area->id,
					'area' => $area->area,
					'zone_id' => $area->zone_id,

				];
			}
		}
		return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $data,

		], 200);
	}

	/**
	 * @OA\Get(
	 *     path="/api/business/state/get-state",
	 *     tags={"State"},
	 *     summary="Get states",
	 *     description="Fetch a list of all available states (used for dropdowns and selections).",
	 *     security={{"bearerAuth":{}}}, 
	 *     @OA\Response(
	 *         response=200,
	 *         description="States retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=9),
	 *                     @OA\Property(property="state", type="string", example="Uttar Pradesh")
	 *                 )
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
	 *         response=404,
	 *         description="No states found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No states found.")
	 *         )
	 *     )
	 * )
	 */
	public function getState(Request $request)
	{
		$statelists = State::where('country_id', '101')->get();
		if ($statelists) {
			foreach ($statelists as $state) {
				$data[] = [
					'state_id' => $state->id,
					'state' => $state->name,

				];
			}
		}
		return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $data,

		], 200);
	}

	/**
	 * @OA\Get(
	 *     path="/api/business/help",
	 *     tags={"Help"},
	 *     summary="Get Help Page content",
	 *     description="Fetches the Help / FAQ page content for the application.",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Help content retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="title", type="string", example="Help & Support"),
	 *                 @OA\Property(property="content", type="string", example="This is the help page content where users can find FAQs and support information."),
	 *                 @OA\Property(property="contact_email", type="string", example="support@quickdials.com"),
	 *                 @OA\Property(property="contact_phone", type="string", example="+91-9876543210")
	 *             )
	 *         )
	 *     )
	 * )
	 */

	public function help(Request $request)
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
	 * Export assigned leads.
	 */
	public function getLeadsExcel(Request $request)
	{
		$clientID = auth()->guard('clients')->user()->id;

		$assignedKWDS = DB::table('leads')
			->join('assigned_leads', 'leads.id', '=', 'assigned_leads.lead_id')
			->join('cities', 'leads.city_id', '=', 'cities.id')
			->select('leads.*', 'assigned_leads.client_id', 'assigned_leads.lead_id', 'cities.city')
			->orderBy('leads.created_at', 'desc')
			->where('assigned_leads.client_id', $clientID)
			->get();

		$arr = [];
		foreach ($assignedKWDS as $assKWDS) {
			$arr[] = [
				'Name' => $assKWDS->name,
				'Mobile' => $assKWDS->mobile,
				'Email' => $assKWDS->email,
				'Course' => $assKWDS->kw_text,
				'City' => $assKWDS->city,
				'Date' => date_format(date_create($assKWDS->created_at), 'd M, Y H:i:s'),
			];
		}
		$excel = \App::make('excel');
		Excel::create('assigned_leads', function ($excel) use ($arr) {
			$excel->sheet('Sheet 1', function ($sheet) use ($arr) {
				$sheet->fromArray($arr);
			});
		})->export('xls');
	}

}
