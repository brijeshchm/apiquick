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
	 *     tags={"Zones"},
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
					'zone_name' => $zonename,
					'state_id' => $val->state_id,
					'state_name' => $val->state_name,

				);
			}
			$data['leadslist'] = $leads_list;
		}
		return response()->json([
			'status' => true,
			'data' => $data,
			'pagination' => [
				'current_page' => $leads->currentPage(),
				'per_page' => $leads->perPage(),
				'total' => $leads->total(),
				'last_page' => $leads->lastPage(),
			],
		], 200);

	}
	/**
	 * @OA\Delete(
	 *     path="/api/business/assignZone/delete/{id}",
	 *     tags={"Zones"},
	 *     summary="Delete assigned zone",
	 *     description="Delete a specific zone assigned to the authenticated user by ID.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         description="ID of the assigned zone to delete",
	 *         @OA\Schema(type="integer", example=5)
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Zone deleted successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Zone deleted successfully.")
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
	 *         description="Zone not found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Zone not found or already deleted.")
	 *         )
	 *     )
	 * )
	 */

	public function assignZoneDelete(Request $request, $id)
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
		$assignedZone = AssignedZone::findOrFail($id);
		if ($assignedZone->delete()) {
			$data['status'] = true;
			$data['message'] = "Assigned Zone Successfully!";
		} else {
			$data['status'] = false;
			$data['message'] = "Assigned Zone could not be Deleted!";
		}
		echo json_encode($data);
	}



	/**
	 * @OA\Get(
	 *     path="/api/business/cities/get-cities",
	 *     tags={"Cities"},
	 *     summary="Get cities by state",
	 *     description="Fetch a list of cities dynamically based on state_id (used for AJAX calls in dropdowns).",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="state_id",
	 *         in="query",
	 *         required=true,
	 *         description="ID of the state",
	 *         @OA\Schema(type="integer", example=9)
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

		$citieslists = Citieslists::get();
		if ($citieslists) {
			foreach ($citieslists as $city) {
				$data[] = [
					'city_id' => $city->id,
					'city_name' => $city->city,

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
	 *     path="/api/business/city/get-city-by-state",
	 *     tags={"Cities"},
	 *     summary="Get cities by state",
	 *     description="Fetch a list of cities dynamically based on state_id (used for AJAX calls in dropdowns).",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="state_id",
	 *         in="query",
	 *         required=true,
	 *         description="ID of the state",
	 *         @OA\Schema(type="integer", example=9)
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
	public function getCityByState(Request $request)
	{
		$sid = $request->input('state_id');
		$cid = $request->input('cid');
		$data = [];
		$cityslist = Citieslists::where('state_id', $sid)->get();

		if (!$cityslist) {
			return response()->json([
				'status' => true,
				'message' => "City not Found",
				'data' => '',

			], 200);
		}
		if ($cityslist) {
			foreach ($cityslist as $city) {
				$data[] = [
					'city_id' => $city->id,
					'city_name' => $city->city,
					'state_id' => $city->state_id,

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
					'state_name' => $state->name,

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
		echo json_encode($data);
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
