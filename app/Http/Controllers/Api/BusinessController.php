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

use App\Models\Occupation;
use App\Models\Citieslists;
use App\Models\AssignedZone;
use App\Models\KeywordSellCount;
use App\Models\Client\AssignedKWDS;
class BusinessController extends Controller
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
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index()
	{
		return view('client.business-owners');
	}


	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function dashboard()
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

		 
		$data['clientDetails'] = DB::table('clients')->where('id', $user->id)->first();
		$data['leads'] = DB::table('leads')
			->join('assigned_leads', 'leads.id', '=', 'assigned_leads.lead_id')
			->leftjoin('citylists', 'leads.city_id', '=', 'citylists.id')
			->leftjoin('areas', 'leads.area_id', '=', 'areas.id')
			->leftjoin('zones', 'leads.zone_id', '=', 'zones.id')
			->select('leads.*', 'assigned_leads.client_id', 'assigned_leads.lead_id', 'assigned_leads.created_at as created', 'areas.area', 'zones.zone')
			->orderBy('assigned_leads.created_at', 'desc')
			->where('assigned_leads.client_id', $user->id)->get();

			echo json_encode($data);
	}


  


	/**
	 * Return Paginated Assigned Keywords
	 *
	 * @param $request - Request class instance
	 * @param $id - ClientID
	 * @return JSON object containing payload
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
				->select('assigned_zones.*', 'citylists.city', 'zones.zone', 'assigned_zones.id as assign_id')
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

					$action = '<a href="javascript:businessController.assignZoneDelete(' . $val->id . ')" title="Delete" class="btn btn-danger"><i class="bi bi-trash" aria-hidden="true"></i></a>';
                    $leads_list[$key] = array(
                        'city' => $val->city,
                        'zonename' => $zonename,
                        'action' => $action,
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
		echo json_encode($data);
	}
	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
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




	public function getAjaxCities(Request $request)
	{
		$sid = $request->input('sid');
		$cid = $request->input('cid');
		$citys = DB::table('citylists')->where('state', $sid)->get();

		if ($citys) {
			echo '<option value="">Select City</option>';
			foreach ($citys as $city) {
				$selected = ($cid == $city->city) ? "selected" : '';

				echo '<option value="' . $city->city . '" ' . $selected . ' >' . $city->city . '</option>';

			}
		} else {
			echo '<option value="">No record found</option>';
		}		
	}

	public function getAjaxZone(Request $request)
	{

		$cid = $request->input('city');
		$zid = $request->input('zone');
		$zones = DB::table('zones')->where('city_id', $cid)->get();

		if ($zones) {
			echo '<option value="">Select zone</option>';
			foreach ($zones as $zone) {
				$selected = ($zid == $zone->zone) ? "selected" : '';

				echo '<option value="' . $zone->id . '" ' . $selected . ' >' . $zone->zone . '</option>';

			}
			echo '<option value="Other">Other</option>';
		} else {
			echo '<option value="">No record found</option>';
		}


	}

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


	/**
	 * Handling client remark
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
	 */
	public function discussion(Request $request)
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
			$admin_id = $request->input('admin-id');
			$client_id = $request->input('client-id');
			$discussion = $request->input('clientremark');
			$add_data = array(
				'client_id' => $client_id,
				'admin_id' => $admin_id,
				'name' => auth()->guard('clients')->user()->business_name,
				'discussion' => $discussion,
			);
			$add = DB::table('client_discussion')->insert($add_data);
			if ($add) {

				 
				$data['status'] = true;
				$data['message'] = 'Discussion Information Successfully';
			} else {
				$data['status'] = true;
				$data['message'] = 'discussion Information not assigned';				 
			}

			echo json_encode($data);
	}
}
