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
                'success' => true,
                'data' => $data,
                'pagination' => [
                        'current_page' => $leads->currentPage(),
                        'per_page' => $leads->perPage(),
                        'total' => $leads->total(),
                        'last_page' => $leads->lastPage(),
                    ],
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

        } catch (\Exception $e) {
            
            $data['status'] = false;
            $data['message'] = 'Failed to : ' . $e->getMessage();
            $data['code'] =  500;
        }


        echo json_encode($data);
    }



}
