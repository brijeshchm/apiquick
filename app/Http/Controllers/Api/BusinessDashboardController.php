<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Support\Facades\Auth;
class BusinessDashboardController extends Controller
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
     *     path="/api/business/dashboard",
     *     tags={"Dashboard"},
     *     summary="Get dashboard data",
     *     description="Fetch user/business dashboard overview. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_ads", type="integer", example=15),
     *                 @OA\Property(property="active_ads", type="integer", example=10),
     *                 @OA\Property(property="pending_ads", type="integer", example=3),
     *                 @OA\Property(property="messages", type="integer", example=5),
     *                 @OA\Property(property="profile_completed", type="boolean", example=true),
     *                 @OA\Property(property="last_login", type="string", format="date-time", example="2025-09-04T10:20:30Z")
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
	public function dashboard(Request $request)
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
		
		$perPage = $request->query('per_page', 10);
		$leads = DB::table('leads')
			->join('assigned_leads', 'leads.id', '=', 'assigned_leads.lead_id')
			->leftjoin('citylists', 'leads.city_id', '=', 'citylists.id')
			->leftjoin('areas', 'leads.area_id', '=', 'areas.id')
			->leftjoin('zones', 'leads.zone_id', '=', 'zones.id')
			->select('leads.*','assigned_leads.*','assigned_leads.client_id', 'assigned_leads.lead_id', 'assigned_leads.created_at as created', 'areas.area', 'zones.zone')

			->orderBy('assigned_leads.created_at', 'desc')
			->where('assigned_leads.client_id', $user->id)
			->paginate($perPage);
   			if (!empty($leads)) {
                foreach ($leads->items() as $key => $val) {
					if (!empty($val->zone)) {
						$zonename = $val->zone;
					} else {
						$zonename = "";
					}					 
                    $leads_list[$key] = array(
                        'lead_id' => $val->lead_id,
                        'name' => $val->name,
                        'mobile' => $val->mobile,
                        'email' => $val->email,
                        'city_id' => $val->city_id,
                        'cityName' => $val->city_name,
                        'area_id' => $val->area_id,
                        'area' => $val->area,
                        'zone_id' => $val->zone_id,
                        'zone' => $val->zone,
                        'kw_id' => $val->kw_id,
                        'kw_text' => $val->kw_text,
                        'client_id' => $val->client_id,
                        'createdDate' => $val->created,
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
}
