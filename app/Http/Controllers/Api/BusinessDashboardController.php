<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Helpers;
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
     *
     *     @OA\Response(
     *         response=200,
     *         description="Dashboard data fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="total_ads", type="integer", example=15),
     *                 @OA\Property(property="active_ads", type="integer", example=10),
     *                 @OA\Property(property="pending_ads", type="integer", example=3),
     *                 @OA\Property(property="messages", type="integer", example=5),
     *                 @OA\Property(property="profile_completed", type="boolean", example=true),
     *                 @OA\Property(
     *                     property="last_login",
     *                     type="string",
     *                     format="date-time",
     *                     example="2025-09-04T10:20:30Z"
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated - Invalid or missing token",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
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

        $client = DB::table('clients')->where('id', $user->id)->first();
        $rating = DB::table('comments')
			->where('comment_client_ID', $user->id)
			->selectRaw('
				COUNT(*) as comment_count,
				COALESCE(SUM(rating),0) as total_rating
			')
			->first();
		if (!empty($rating)) {
			$avgRating = ($rating->comment_count > 0)
                ? round($rating->total_rating / $rating->comment_count, 1)
                : 0;
			$ratingCount = $rating->comment_count;
		} else {
			$avgRating = 0;
			$ratingCount = 0;
		}
        $perPage = $request->query('per_page', 10);
        $leads = DB::table('leads')
            ->join('assigned_leads', 'leads.id', '=', 'assigned_leads.lead_id')
            ->leftjoin('citylists', 'leads.city_id', '=', 'citylists.id')
            ->leftjoin('areas', 'leads.area_id', '=', 'areas.id')
            ->leftjoin('zones', 'leads.zone_id', '=', 'zones.id')
            ->select('leads.*', 'assigned_leads.*', 'assigned_leads.client_id', 'assigned_leads.lead_id', 'assigned_leads.created_at as created', 'areas.area', 'zones.zone', 'assigned_leads.id as assignId')

            ->orderBy('assigned_leads.created_at', 'desc')
            ->where('assigned_leads.client_id', $user->id)
            ->paginate($perPage);
            $leads_list=[];
        if (!empty($leads)) {
            foreach ($leads->items() as $key => $val) {
                if (!empty($val->zone)) {
                    $zonename = $val->zone;
                } else {
                    $zonename = "";
                }
                 $coins= "";
                if(!empty($val->scrapLead)) { 
                $coins  = ['color'=>'green','coin'=>$val->coins]; 
                }else if($val->coins){ 
                $coins =  ['color'=>'red','coin'=>$val->coins]; 
                }  
                 
                $created = get_time(strtotime($val->created)) . ' ago';
                
				$businessName = !empty($client->business_name) ? $client->business_name : 'our company';
				$keyword = !empty($val->kw_text) ? $val->kw_text : 'your enquiry';
				$addressText = !empty($client->address) ? $client->address : '';
				$mapText = !empty($client->business_map) ? '\n Directions: ' . $client->business_map : '';
				$profile_url = 'https://www.quickdials.com/businessdetails/' . $client->business_slug;

				$address_data = "Greetings from {$businessName},\n"
					. "We’re following up on your enquiry made on Quickdials for {$keyword}.\n"
					. "For more information"
					. (!empty($addressText) ? ", you can visit us at {$addressText}" : "")
					. "{$mapText}";

				$for_service = "Greetings from {$businessName},\n"
					. "We’re following up on your enquiry made on Quickdials for {$keyword}.\n"
					. "For more information of the services offered by our business please refer "
					. (!empty($addressText) ? ", you can visit us at {$addressText}" : "")
					. ", Or {$profile_url}";
				$for_review = "Greetings from {$businessName}, Rated {$avgRating} Rating out of {$ratingCount} Votes.\n"
					. "We’re following up on your enquiry made on Quickdials for {$keyword}.\n"
					. "For more information about the services offered by our business"
					. (!empty($addressText) ? ", you can visit us at {$addressText}" : "")
					. ". Or visit our profile: {$profile_url}";

                $share_lead = 
					'Name: ' . (trim($val->name ?? '') ?: '') . ', ' .
					'Mobile: ' . (trim($val->mobile ?? '') ?: '') . ', ' .
					'Email: ' . (trim($val->email ?? '') ?: '') . ', ' .
					'Service: ' . (trim($val->kw_text ?? '') ?: '') . ', ' .
					'Location: ' . (
						!empty($val->city_name)
							? trim($val->city_name . (!empty($val->zone) ? ', ' . $val->zone : ''))
							: ''
					);
				$user_share = array(
					'address_share' => $address_data,
					'for_service' => $for_service,
					'for_review' => $for_review,
					'share_lead' => $share_lead,
				);

                $frmcheckText = '';
					if (!empty($val->frmcheck) && is_array($val->frmcheck)) {
						$frmcheckText = implode(', ', $val->frmcheck);
					}
					$parts = array_filter([
						$val->kw_text ? "Interested in " . trim($val->kw_text) : '',
						$frmcheckText ? "Mode of " . trim($frmcheckText) : '',
						$val->zone ? "Location " . trim($val->zone) : '',
						$val->plan ? "Plan " . trim($val->plan) : '',
						$val->age ? "Age " . trim($val->age) : '',
						$val->experience ? "with experience " . trim($val->experience) : '',
					]);

					$main = implode(" • ", $parts);

					$remark = $main;

					if (!empty($val->remark)) {
						$remark = $remark . " " . trim($val->remark);
					}
                $leads_list[$key] = array(
                    'lead_id' => $val->lead_id,
                    'assignId' => $val->assignId,
                    'favorite_lead' => $val->favorite_lead,
                    'readLead' => $val->readLead,
                    'scrapLead' => $val->scrapLead,
                    'scrapPay' => $val->scrapPay,
                    'scrapValue' => $val->scrapValue,
                    'primeLead' => $val->primeLead,
                    'name' => $val->name,
                    'mobile' => $val->mobile,
                    'email' => $val->email,                     
                    'remark' => !empty($remark) ? trim($remark) : null,
					'cityName' => !empty($val->city_name)
							? trim($val->city_name . (!empty($val->zone) ? ', ' . $val->zone : ''))
							: null,              
                    'kw_text' => $val->kw_text,
                    'client_id' => $val->client_id,
                    'createdDate' => $created,
                    'coins' => $coins,                    
                    'user_share' => $user_share,                    
                );
            }
            $data['leadslist'] = $leads_list;
        }
      


        $lead_count = DB::table('leads')
        ->join('assigned_leads', 'leads.id', '=', 'assigned_leads.lead_id')
        ->where('assigned_leads.readLead', '0')
        ->where('assigned_leads.client_id', $client->id)
        ->count();
        $data['clientDetails'] = [
            'business_id'     => $client->id,
            'remaining_cons'  => $client->coins_amt,
            'package'         => ucfirst($client->client_type),
            'expired_date'    => !empty($client->expired_on)
                                    ? date('d M, Y', strtotime($client->expired_on))
                                    : null,
            'lead_count'      => $lead_count,
        ];
        return response()->json([
            'status' => true,
            'data' => $data,                  
            'per_page' => $leads->perPage(),
            'total' => $leads->total(),
            'last_page' => $leads->lastPage(),
            
        ], 200);
      

    }
}
