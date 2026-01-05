<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Nnjeim\World\Models\Country;
use Nnjeim\World\Models\State;
use Illuminate\Support\Facades\Auth;
use App\Models\Client\Client;
use Illuminate\Support\Facades\Hash;
use DB;
class LeadBusinessController extends Controller
{
    public function dashboard(Request $request)
    {
        try {

            if (!Auth::guard('sanctum')->check()) {
                return response()->json([
                    'message' => 'Unauthenticated: Token is missing or invalid',
                    'error' => 'token_missing_or_invalid'
                ], 401);
            }

            $currentUser = auth('sanctum')->user();
            if (!$currentUser) {
                return response()->json([
                    'message' => 'Unauthenticated: Token is missing or invalid',
                    'error' => 'token_missing_or_invalid'
                ], 401);
            }

            if (!$currentUser->active_status) {
                $currentUser->tokens()->delete();
                return response()->json(['status' => false, 'message' => 'User account is inactive',], 403);
            }

        $rating = DB::table('comments')
			->where('comment_client_ID', $currentUser->id)
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
            // Fetch users with pagination
            $perPage = $request->query('per_page', 10); // Default to 15 users per page
            $leads = DB::table('leads')
                ->join('assigned_leads', 'leads.id', '=', 'assigned_leads.lead_id')
                ->leftjoin('citylists', 'leads.city_id', '=', 'citylists.id')
                ->leftjoin('areas', 'leads.area_id', '=', 'areas.id')
                ->leftjoin('zones', 'leads.zone_id', '=', 'zones.id')
                ->select('leads.*', 'assigned_leads.client_id', 'assigned_leads.lead_id', 'assigned_leads.created_at as created', 'areas.area', 'zones.zone')
                ->orderBy('assigned_leads.created_at', 'desc')
                ->where('assigned_leads.client_id', $currentUser->id)
                ->paginate($perPage);

            if (!empty($leads)) {
                foreach ($leads->items() as $key => $val) {
                    $coins= "";
                if(!empty($val->scrapLead)) { 
                $coins  = ['color'=>'green','coin'=>$val->coins]; 
                }else if($val->coins){ 
                $coins =  ['color'=>'red','coin'=>$val->coins]; 
                }  
                 
                $created = get_time(strtotime($val->created)) . ' ago';
                $businessName = !empty($currentUser->business_name) ? $currentUser->business_name : 'our company';
				$keyword = !empty($val->kw_text) ? $val->kw_text : 'your enquiry';
				$addressText = !empty($client->address) ? $currentUser->address : '';
				$mapText = !empty($client->business_map) ? '\n Directions: ' . $currentUser->business_map : '';
				$profile_url = 'https://www.quickdials.com/business-details/' . $currentUser->business_slug;

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

				$user_share = array(
					'address_share' => $address_data,
					'for_service' => $for_service,
					'for_review' => $for_review,

				);
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
                        'createdDate' => $created,
                   	    'coins' => $coins,
                   	    'user_share' => $user_share,
                    );
                }
                $data['leadslist'] = $leads_list;
            }
            return response()->json([
                'success' => true,
                'data' => $data,
                
                    'current_page' => $leads->currentPage(),
                    'per_page' => $leads->perPage(),
                    'total' => $leads->total(),
                    'last_page' => $leads->lastPage(),
            
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve users: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function enquiry(Request $request)
    {
        try {

            if (!Auth::guard('sanctum')->check()) {
                return response()->json([
                    'message' => 'Unauthenticated: Token is missing or invalid',
                    'error' => 'token_missing_or_invalid'
                ], 401);
            }

            $currentUser = auth('sanctum')->user();
            if (!$currentUser) {
                return response()->json([
                    'message' => 'Unauthenticated: Token is missing or invalid',
                    'error' => 'token_missing_or_invalid'
                ], 401);
            }
            

            if (!$currentUser->active_status) {
                $currentUser->tokens()->delete();
                return response()->json(['status' => false, 'message' => 'User account is inactive',], 403);
            }
             $rating = DB::table('comments')
			->where('comment_client_ID', $currentUser->id)
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
            // Fetch users with pagination
            // Fetch users with pagination
            $perPage = $request->query('per_page', 10); // Default to 15 users per page
            $leads = DB::table('leads')
                ->join('assigned_leads', 'leads.id', '=', 'assigned_leads.lead_id')
                ->leftjoin('citylists', 'leads.city_id', '=', 'citylists.id')
                ->leftjoin('areas', 'leads.area_id', '=', 'areas.id')
                ->leftjoin('zones', 'leads.zone_id', '=', 'zones.id')
                ->select('leads.*', 'assigned_leads.client_id', 'assigned_leads.lead_id', 'assigned_leads.created_at as created', 'areas.area', 'zones.zone')
                ->orderBy('assigned_leads.created_at', 'desc')
                ->where('assigned_leads.client_id', $currentUser->id)
                ->paginate($perPage);

            if (!empty($leads)) {
                foreach ($leads->items() as $key => $val) {
                    $coins= "";
                if(!empty($val->scrapLead)) { 
                $coins  = ['color'=>'green','coin'=>$val->coins]; 
                }else if($val->coins){ 
                $coins =  ['color'=>'red','coin'=>$val->coins]; 
                }  
                 
                $created = get_time(strtotime($val->created)) . ' ago';
                 $businessName = !empty($currentUser->business_name) ? $currentUser->business_name : 'our company';
				$keyword = !empty($val->kw_text) ? $val->kw_text : 'your enquiry';
				$addressText = !empty($client->address) ? $currentUser->address : '';
				$mapText = !empty($client->business_map) ? '\n Directions: ' . $currentUser->business_map : '';
				$profile_url = 'https://www.quickdials.com/business-details/' . $currentUser->business_slug;

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

				$user_share = array(
					'address_share' => $address_data,
					'for_service' => $for_service,
					'for_review' => $for_review,

				);
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
                        'createdDate' => $created,
                   	    'coins' => $coins,
                   	    'user_share' => $user_share,
                    );
                }
                $data['leadslist'] = $leads_list;
            }
            return response()->json([
                'status' => true,
                'data' => $data,
                 
                    'current_page' => $leads->currentPage(),
                    'per_page' => $leads->perPage(),
                    'total' => $leads->total(),
                    'last_page' => $leads->lastPage(),
                
            ], 200);

        } catch (\Exception $e) {

            $data['status'] = false;
            $data['message'] = 'Failed to : ' . $e->getMessage();
            $data['code'] = 500;
        }

        return response()->json([
            'status' => true,
            'message' => "Successfully",
            'data' => $data,

        ], 200);

    }



}
