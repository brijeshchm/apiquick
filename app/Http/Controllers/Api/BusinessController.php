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
use Illuminate\Support\Facades\Cache;
 
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
	 *     tags={"Business-location"},
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
// dd($request->all())
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
		$perPage = $request->query('length', 10);
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

		$searchcity = trim($request->input('city'));	 
		$cityData = collect();
		if ($searchcity !== '') {

			$cityData = DB::table('citylists')
				->where('city', 'LIKE', $searchcity . '%')
				->select('id as city_id', 'city')
				->orderBy('city')
				->limit(20)
				->get();
		}
 
		if ($cityData->isEmpty()) {

			$cityList = [
				'Hyderabad','Patna','Gorakhpur','Faridabad','Delhi','Noida',
				'Ghaziabad','Mumbai','Pune','Meerut','Bangalore','Indore',
				'Kanpur','Chennai','Kolkata','Coimbatore','Prayagraj'
			];

			$cityData = DB::table('citylists')
				->whereIn('city', $cityList)
				->select(
					'id as city_id',
					'city'
				)
				->orderBy('city')
				->get();
		}

		return response()->json([
			'status'  => true,
			'message' => 'Successfully',
			'data'    => $cityData
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

		$validator = Validator::make($request->all(), [
			'state_id' => 'required|integer|exists:state,id',    
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'message' => 'Validation failed',
				'errors' => $validator->errors(),
			], 422);
		}

		$stateId = $request->integer('state_id');

		$cities = Citieslists::where('state_id', $stateId)
			->select('id as city_id', 'city', 'state_id')
			->get()
			->map(fn($city) => [
				'city_id' => $city->city_id,
				'city' => $city->city,
				'state_id' => $city->state_id,
			])
			->values();

		return response()->json([
			'status' => true,
			'message' => $cities->isNotEmpty() ? 'Successfully' : 'No cities found for this state',
			'data' => $cities,
		], $cities->isEmpty() ? 200 : 200);

 
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
	 * @OA\Get(
	 *     path="/api/business/status/get-status",
	 *     tags={"Status"},
	 *     summary="Get Status",
	 *     description="Fetch a list of Status.",
	 *     security={{"bearerAuth":{}}},	 *   
	 *     @OA\Response(
	 *         response=200,
	 *         description="Status retrieved successfully",
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

	public function getStatus(Request $request)
	{

		$statuslists = Status::where('lead_filter', '1')->get();
		if ($statuslists) {
			foreach ($statuslists as $status) {
				$data[] = [
					'status_id' => $status->id,
					'status' => $status->name,

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

		$states = State::where('country_id', $cid)
			->pluck('name', 'id')
			->map(fn($name, $id) => [
				'state_id' => $id,
				'state' => $name,
				'country_id' => $cid,
			])
			->values();

		return response()->json([
			'status' => true,
			'message' => $states->isEmpty() ? "State not Found" : "Successfully",
			'data' => $states,
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
	 *         required=false,
	 *         description="Search by city id,city and pincode ",
	 *         @OA\Schema(type="string", example=278)
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

		$cid = trim($request->input('city_id'));
		 
		$data = [];
		if(!empty($cid)){
		$zoneslist = DB::table('zones')
			->join('citylists', 'citylists.id', '=', 'zones.city_id')
			->when($cid, function ($query) use ($cid) {
				$query->where('zones.zone', 'LIKE', "{$cid}%")
					->orWhere('citylists.city', 'LIKE', "{$cid}%")
					->orWhere('zones.city_id', $cid)
					->orWhere('zones.pincode', 'LIKE', "{$cid}%");
			})
			->select(
				'zones.id as zone_id',
				'zones.zone',
				'citylists.city as cityName',
				'zones.city_id',
				'zones.pincode'
			)
			 ->limit(50) 
			->distinct()
			->get();

		}else{
 
			$cityList = [
				'Hyderabad',
				'Patna',
				'Gorakhpur',
				'Faridabad',
				'Delhi',
				'Noida',
				'Ghaziabad',
				'Mumbai',
				'Pune',
				'Meerut',
				'Bangalore',
				'Indore',
				'Kanpur',
				'Chennai',
				'Kolkata',
				'Coimbatore',
				'Prayagraj'
			];
			$zoneslist = DB::table('zones')
				->join('citylists', 'citylists.id', '=', 'zones.city_id')
				->whereIn('citylists.city', $cityList)
				->select(
					DB::raw('MIN(zones.id) as zone_id'),
					DB::raw('MIN(zones.zone) as zone'),
					'citylists.id as city_id',
					'citylists.city as cityName'
				)
				->groupBy('citylists.id', 'citylists.city')
				->orderBy('citylists.city')
				->get();

		}


		foreach ($zoneslist as $zone) {

			$zoneText = '';

			if (!empty($zone->zone)) {
				$zoneText .= $zone->zone;
			}

			if (!empty($zone->cityName)) {
				$zoneText .= ($zoneText ? ', ' : '') . $zone->cityName;
			}

			if (!empty($zone->pincode)) {
				$zoneText .= ($zoneText ? ' - ' : '') . $zone->pincode;
			}

			$data[] = [
				'zone_id' => $zone->zone_id,
				'zone' => $zoneText
			];
		}

		// $data[] = [
		// 	'zone_id' => 'Other',
		// 	'zone' => 'Other'
		// ];

		return response()->json([
			'status' => true,
			'message' => 'Successfully',
			'data' => $data
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
		$statelists = State::where('country_id', '101')
			->pluck('id', 'name')
			->map(function ($name, $id) {
				return [
					'state_id' => $id,
					'state' => $name
				];
			})->values();
		return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $statelists,

		], 200);
	}

	/**
	 * @OA\Get(
	 *     path="/api/business/help",
	 *     tags={"Help"},
	 *     summary="Get Help Page content",
	 *     description="Fetches the Help / FAQ page content for the application.",
	 * 		security={{"bearerAuth":{}}}, 
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
		$business_Details = array(
			'business_name' => 'Quick Dials Pvt Ltd',
			'corporate_office' => 'G-13, Sector-3 Noida, U.P, India',
			'registered_office' => 'UNIT 101 OXFORD TOWERS, 139/88 HAL OLD AIRPORT RD, H.A.L II Stage, Bangalore North, Bangalore- 560008, Karnataka',
			'phone_no' => '+91-75-5943-5943',
			'whatsapp' => '917559435943',
			'email' => 'info@quickdials.com',
			'website' => 'www.quickdials.com',
			'GSTIN' => '',
			'pan_no' => 'AABCQ2259D',
			'TAN' => 'BLRQ01951F',
			'CIN_No' => 'U63112KA2026PTC215594'

		);
		return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $business_Details,

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
