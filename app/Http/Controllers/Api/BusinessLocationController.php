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
use App\Models\AssignedLead;
use App\Models\AssigneddArea;
use App\Models\State;
use DB;

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
	 * @OA\Post(
	 *     path="/api/business/saveBusinessLocation",
	 *     tags={"Profile"},
	 *     summary="Update Business Location",
	 *     description="Update the authenticated user's business location.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"state_id","cityiesid"},
	 *             @OA\Property(property="state_id", type="integer", example="38"),
	 *             @OA\Property(property="cityiesid", type="string", example="961"),
	 *             @OA\Property(property="zone_id", type="string", example="1090 or Other"),
	 *             @OA\Property(property="other", type="string", example="zone name"),
	 *            
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

	public function saveBusinessLocation(Request $request)
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
					'state_id'   => 'required|integer|exists:state,id',
					'cityiesid'  => 'required|integer|exists:citylists,id',
					'other' => 'required|min:3|max:32|regex:/^(?!.*(.)\1{3,}).+$/',
				]);

			} else {
				$validator = Validator::make($request->all(), [
					//'city_id' 	=> 'required|max:35',
					//'zone_id' => 'required|max:35',
					'state_id' => 'required|max:32',
				]);
			}

			if ($validator->fails()) {
				$errorsBag = $validator->getMessageBag()->toArray();
				return response()->json(['status' => 1, 'errors' => $errorsBag], 400);
			}
			$client = Client::find($user->id);
			if (empty($request->input('zone_id')) && !empty($request->input('cityiesid')) && !empty($request->input('state_id'))) {

				$zones = Zone::where('city_id', $request->input('cityiesid'))->get();
				if (!empty($zones)) {
					foreach ($zones as $zone) {
						$assignedZone = new AssignedZone;
						$assignedZone->city_id = $request->input('cityiesid');
						$assignedZone->zone_id = $zone->id;
						$assignedZone->client_id = $client->id;
						$assignedZone->state_id = $request->input('state_id');
						$checkAssignedZone = AssignedZone::where('client_id', $client->id)->where('zone_id', $zone->id)->where('city_id', $request->input('cityiesid'))->where('state_id', $request->input('state_id'))->first();

						if (empty($checkAssignedZone)) {
							if ($assignedZone->save()) {

								$areas = DB::table('areas');
								$areas = $areas->where('areas.zone_id', '=', $zone->id);
								$areas = $areas->select('areas.id', 'areas.area');
								$areas = $areas->get();
								if (!empty($areas)) {
									foreach ($areas as $area) {
										$assigneddArea = new AssigneddArea;
										$assigneddArea->client_id = $client->id;
										$assigneddArea->state_id = $request->input('state_id');
										$assigneddArea->city_id = $request->input('cityiesid');
										$assigneddArea->assigned_zone_id = $zone->id;
										$assigneddArea->area_id = $area->id;
										$checkAssignedArea = AssigneddArea::where('client_id', $client->id)->where('assigned_zone_id', $zone->id)->where('city_id', $request->input('cityiesid'))->where('area_id', $area->id)->where('state_id', $request->input('state_id'))->first();
										if (empty($checkAssignedArea)) {
											$assigneddArea->save();
										} else {
											$already = 1;
										}

									}
								}
								$add = 1;
							}
						} else {
							$already = 1;

						}
					}
				}
				if (!empty($add)) {
					$status = true;
					$msg = 'Business Location add successfully';
					$code = 200;
				} else {
					if (!empty($already)) {
						$status = false;
						$msg = "Already exists all City, Please add right city !";
						$code = 400;
					} else {
						$status = false;
						$msg = 'City not assigned';
						$code = 400;
					}

				}




			} else if (empty($request->input('zone_id')) && empty($request->input('cityiesid')) && !empty($request->input('state_id'))) {

				//state
				$states = State::where('id', $request->input('state_id'))->first();
				$cities = Citieslists::where('state_id', $states->id)->get();
				if (!empty($cities)) {
					foreach ($cities as $citis) {

						$zones = Zone::where('city_id', $citis->id)->get();
						if (!empty($zones)) {
							foreach ($zones as $zone) {
								$assignedZone = new AssignedZone;
								$assignedZone->city_id = $citis->id;
								$assignedZone->zone_id = $zone->id;
								$assignedZone->client_id = $client->id;
								$assignedZone->state_id = $states->id;
								$checkAssignedZone = AssignedZone::where('client_id', $client->id)->where('zone_id', $zone->id)->where('city_id', $citis->id)->where('state_id', $states->id)->first();

								if (empty($checkAssignedZone)) {
									if ($assignedZone->save()) {
										$areas = DB::table('areas');
										$areas = $areas->where('areas.zone_id', '=', $zone->id);
										$areas = $areas->select('areas.id', 'areas.area');
										$areas = $areas->get();
										if (!empty($areas)) {
											foreach ($areas as $area) {
												$assigneddArea = new AssigneddArea;
												$assigneddArea->client_id = $client->id;
												$assigneddArea->state_id = $states->id;
												$assigneddArea->city_id = $citis->id;
												$assigneddArea->assigned_zone_id = $zone->id;
												$assigneddArea->area_id = $area->id;
												$checkAssignedArea = AssigneddArea::where('client_id', $client->id)->where('assigned_zone_id', $zone->id)->where('city_id', $citis->id)->where('area_id', $area->id)->where('state_id', $states->id)->first();
												if (empty($checkAssignedArea)) {
													$assigneddArea->save();
												}
											}
										}
									}
									$add = 1;
								} else {
									$already = 1;
								}
							}
						}
					}
				}
				if (!empty($add)) {
					$status = true;
					$msg = 'Business Location add successfully';
					$code = 200;
				} else {
					if (!empty($already)) {
						$status = false;
						$msg = "Already exists, Please add right city !";
						$code = 400;
					} else {
						$status = false;
						$msg = 'City not assigned';
						$code = 400;
					}
				}

			} elseif (!empty($request->input('zone_id')) && ($request->input('zone_id') != 'Other') && !empty($request->input('cityiesid')) && !empty($request->input('state_id'))) {
				//zone
				$assignedZone = new AssignedZone;
				$assignedZone->city_id = $request->input('cityiesid');
				$assignedZone->zone_id = $request->input('zone_id');
				$assignedZone->client_id = $user->id;
				$assignedZone->state_id = $request->input('state_id');
				$checkAssignedZone = AssignedZone::where('client_id', $user->id)->where('zone_id', $request->input('zone_id'))->where('city_id', $request->input('cityiesid'))->first();

				if (empty($checkAssignedZone)) {
					if ($assignedZone->save()) {
						$areas = DB::table('areas');
						$areas = $areas->where('areas.zone_id', '=', $request->input('zone_id'));
						$areas = $areas->select('areas.id', 'areas.area');
						$areas = $areas->get();
						if (!empty($areas)) {
							foreach ($areas as $area) {
								$assigneddArea = new AssigneddArea;
								$assigneddArea->client_id = $user->id;
								$assigneddArea->state_id = $request->input('state_id');
								$assigneddArea->city_id = $request->input('cityiesid');
								$assigneddArea->assigned_zone_id = $request->input('zone_id');
								$assigneddArea->area_id = $area->id;
								$checkAssignedArea = AssigneddArea::where('client_id', $user->id)->where('assigned_zone_id', $request->input('zone_id'))->where('city_id', $request->input('cityiesid'))->where('area_id', $area->id)->where('state_id', $request->input('state_id'))->first();
								if (empty($checkAssignedArea)) {
									$assigneddArea->save();

								}
							}
						}
						$add = 1;
					}
				} else {
					$already = 0;
				}


				if (!empty($add)) {
					$status = true;
					$msg = "Business Location updated successfully !";
				} else {

					if (!empty($already)) {
						$status = false;
						$msg = "Already exists, Please add right city !";
						$code = 400;
					} else {
						$status = false;
						$msg = "Business Location could not be successfully, Please try again !";
						$code = 400;
					}
				}
			} else if (!empty($request->input('zone_id') == 'Other') && !empty($request->input('cityiesid')) && !empty($request->input('state_id')) && !empty($request->input('other'))) {

				//Other
				$assignedZone = new AssignedZone;
				$assignedZone->city_id = $request->input('cityiesid');
				if ($request->input('zone_id') == "Other") {
					$checkZone = Zone::where('zone', $request->input('other'))->where('city_id', $request->input('cityiesid'))->first();
					if (empty($checkZone)) {
						$zone = new Zone;
						$zone->city_id = $request->input('cityiesid');
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
				$assignedZone->client_id = $user->id;
				$assignedZone->state_id = $request->input('state_id');
				$checkAssignedZone = AssignedZone::where('client_id', $user->id)->where('zone_id', $zone_id)->where('city_id', $request->input('cityiesid'))->where('state_id', $request->input('state_id'))->first();
				if (empty($checkAssignedZone)) {
					if ($assignedZone->save()) {
						$status = true;
						$msg = "Business Location updated successfully !";
					} else {
						$status = false;
						$msg = "Business Location could not be successfully, Please try again !";
					}
				} else {
					$status = false;
					$msg = "Already exists " . $request->input('other') . " Please add right zone !";
				}

			}

			$data['status'] = $status;
			$data['message'] = $msg;
			return response()->json([
			'data' => $data,
		], 200);
			
		

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
	 *             @OA\Property(property="zone", type="string", example="Delhi NCR"),
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
	 *                 @OA\Property(property="zone", type="string", example="Delhi NCR"),
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
		$state = State::find($request->state);
		$city = Citieslists::find($request->city);
		
		$client->business_name = $string;
		$client->address = $request->input('address');
		$client->landmark = $request->input('landmark');
		$client->state_id = $state?->id;
		$client->state = $state?->name;
		$client->city_id = $city?->id;
		$client->city = $city?->city;
		$client->country = $request->input('country');

		if ($client->save()) {
			$data['clientDetails'] = Client::find($id);
			$data['status'] = true;
			$data['message'] = "Location Information Updated Successfully";

		} else {
			$data['status'] = false;
			$data['message'] = "Location Information not assigned";

		}
		return response()->json([
			'data' => $data,
		], 200);

	}


	/**
	 * @OA\Get(
	 *     path="/api/business/get-business-location",
	 *     tags={"Profile"},
	 *     summary="Get business location with pagination",
	 *     description="Fetches paginated business location details of the authenticated user.",
	 *     security={{"bearerAuth":{}}},
	 * 
	 *     @OA\Parameter(
	 *         name="page",
	 *         in="query",
	 *         required=false,
	 *         description="Page number",
	 *         @OA\Schema(type="integer", example=1)
	 *     ),
	 *     @OA\Parameter(
	 *         name="per_page",
	 *         in="query",
	 *         required=false,
	 *         description="Items per page",
	 *         @OA\Schema(type="integer", example=10)
	 *     ),
	 *     @OA\Parameter(
	 *         name="search",
	 *         in="query",
	 *         required=false,
	 *         description="Search keyword",
	 *         @OA\Schema(type="string", example="sector 45")
	 *     ),
	 * 
	 *     @OA\Response(
	 *         response=200,
	 *         description="Business location retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Successfully"),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="records", type="array",
	 *                     @OA\Items(type="object",
	 *                         @OA\Property(property="business_name", type="string", example="Quick Dials Pvt Ltd"),
	 *                         @OA\Property(property="address", type="string", example="MG Road"),
	 *                         @OA\Property(property="city", type="string", example="Noida")
	 *                     )
	 *                 ),
	 *                 @OA\Property(property="pagination", type="object",
	 *                     @OA\Property(property="total", type="integer", example=100),
	 *                     @OA\Property(property="per_page", type="integer", example=10),
	 *                     @OA\Property(property="current_page", type="integer", example=1),
	 *                     @OA\Property(property="last_page", type="integer", example=10),
	 *                     @OA\Property(property="total_pages", type="integer", example=10)
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


	public function getBusinessLocationPagination(Request $request)
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
		$leads_list= [];
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
 * @OA\Delete(
 *     path="/api/business/business-location/{id}",
 *     tags={"Profile"},
 *     summary="Delete assigned business location",
 *     description="Delete a business location (city + zone) assigned to the authenticated client. Only the owner can delete it.",
 *     security={{"bearerAuth":{}}},
 * 
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID of the assigned zone",
 *         @OA\Schema(type="integer")
 *     ),
 * 
 *     @OA\Response(
 *         response=200,
 *         description="Business location deleted successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Business location deleted successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthenticated"
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden - You don't own this location"
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Assigned zone not found"
 *     )
 * )
 */
public function destroy($id)
{
    // Check authentication
    if (!Auth::guard('sanctum')->check()) {
        return response()->json([
            'status'  => false,
            'message' => 'Unauthenticated: Token is missing or invalid',
            'error'   => 'token_missing_or_invalid'
        ], 401);
    }

    $user = auth('sanctum')->user();
    $clientId = $user->id;

    try {
        // Find the assigned zone and ensure it belongs to the authenticated client
        $assign = AssignedZone::where('id', $id)
            ->where('client_id', $clientId)
            ->firstOrFail();
// dd($assign);
        // Perform soft delete (if using SoftDeletes) or hard delete
        $assign->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Business location deleted successfully'
        ], 200);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'status'  => false,
            'message' => 'Assigned zone not found or you do not have permission to delete it',
            'error'   => 'not_found_or_forbidden'
        ], 404);
    } catch (\Exception $e) {
        return response()->json([
            'status'  => false,
            'message' => 'Failed to delete business location',
            'error'   => 'delete_failed'
        ], 500);
    }
}

}
