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
	 * @OA\Get(
	 *     path="/api/business/business-location",
	 *     tags={"Business Location"},
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


	public function businessLocation(Request $request)
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
		return response()->json([
			'success' => true,
			'data' => $data,
		], 200);


	}
	/**
	 * @OA\Post(
	 *     path="/api/business/saveBusinessLocation",
	 *     tags={"Business Location"},
	 *     summary="Update Business Location",
	 *     description="Update the authenticated user's business location.",
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
		return response()->json([
			'data' => $data,
		], 200);

	}


	/**
	 * @OA\Get(
	 *     path="/api/business/get-business-location-pagination",
	 *     tags={"Business Location"},
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
		$clientID = $user->id;
		$search = $request->input('search');
		$length = (int) $request->input('length', 10); // per page

		$query = DB::table('assigned_zones')
			->join('zones', 'assigned_zones.zone_id', '=', 'zones.id')
			->join('citylists', 'assigned_zones.city_id', '=', 'citylists.id')
			->select(
				'assigned_zones.id as assign_id',
				'assigned_zones.*',
				'citylists.city',
				'zones.zone'
			)
			->where('assigned_zones.client_id', $clientID);

		// Apply search if provided
		if (!empty($search)) {
			$query->where(function ($q) use ($search) {
				$q->where('citylists.city', 'LIKE', "%{$search}%")
					->orWhere('zones.zone', 'LIKE', "%{$search}%");
			});
		}

		$searchableColumns = ['id', 'city', 'zone'];
		$sortableColumns = array_merge($searchableColumns, ['created_at']);


		$sortBy = in_array($request->input('sort_by'), $sortableColumns) ? $request->input('sort_by') : 'created_at';
		$sortDir = strtolower($request->input('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
		$perPage = min($request->get('per_page', 15), 100); // Limit max per_page to 100
		$page = max($request->get('page', 1), 1); // Ensure page is at least 1

		// Get total count BEFORE applying pagination
		$totalRecords = (clone $query)->count();
		$totalPages = $perPage > 0 ? (int) ceil($totalRecords / $perPage) : 1;

		// Adjust page if it exceeds total pages
		if ($page > $totalPages && $totalPages > 0) {
			$page = $totalPages; // Go to last page instead of first page
		}

		// Apply sorting and pagination
		$leads = $query->orderBy($sortBy, $sortDir)
			->offset(($page - 1) * $perPage)
			->limit($perPage)
			->get();
		// Transform collection for DataTables
		$data = $leads->map(function ($lead) {
			$checkbox = $lead->id;
			$cityName = $lead->city ?? 'N/A';
			$zoneName = $lead->zone ?? '';



			return [
				$checkbox,
				e($cityName),
				e($zoneName),

			];
		})->all();


		return response()->json([

			'status' => true,
			'message' => 'Success',

			'total_pages' => $totalPages,
			'total_records' => $totalRecords,
			'data' => $data,
		], 200);
	}


	/**
 * @OA\Delete(
 *     path="/api/business/business-location/{id}",
 *     tags={"Business Location"},
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
