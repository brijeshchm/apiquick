<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Client\Client;
use Validator;
use DB;
use App\Models\Zone;
use App\Models\Keyword;
use App\Models\Citieslists;

use App\Models\KeywordSellCount;
use App\Models\Client\AssignedKWDS;
class BusinessKeywordController extends Controller
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
	 * @OA\Delete(
	 *     path="/api/business/assignKeyword/delete/{id}",
	 *     tags={"Assign Keyword"},
	 *     summary="Delete assigned keyword",
	 *     description="Delete a keyword assigned to the user or business by ID.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         description="ID of the assigned keyword to delete",
	 *         @OA\Schema(type="integer", example=15)
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Keyword deleted successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Assigned keyword deleted successfully.")
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
	 *         description="Keyword not found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Assigned keyword not found.")
	 *         )
	 *     )
	 * )
	 */

	public function assignKeywordDelete(Request $request, $id)
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

		$assignedKWDS = AssignedKWDS::findOrFail($id);
		if ($assignedKWDS->delete()) {
			$data['status'] = true;
			$data['message'] = "Assigned Keyword delete Successfully!";
		} else {
			$data['status'] = false;
			$data['message'] = "Assigned Keyword could not be Deleted!";
		}
		echo json_encode($data);

	}


	/**
	 * @OA\Get(
	 *     path="/api/business/get-paginated-assigned-keywords",
	 *     tags={"Assign Keyword"},
	 *     summary="Get paginated assigned keywords",
	 *     description="Retrieve a paginated list of assigned keywords for the authenticated user or business.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="page",
	 *         in="query",
	 *         required=false,
	 *         description="Page number for pagination",
	 *         @OA\Schema(type="integer", example=1)
	 *     ),
	 *     @OA\Parameter(
	 *         name="per_page",
	 *         in="query",
	 *         required=false,
	 *         description="Number of records per page",
	 *         @OA\Schema(type="integer", example=10)
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="List of assigned keywords with pagination",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="current_page", type="integer", example=1),
	 *                 @OA\Property(property="per_page", type="integer", example=10),
	 *                 @OA\Property(property="total", type="integer", example=42),
	 *                 @OA\Property(property="last_page", type="integer", example=5),
	 *                 @OA\Property(
	 *                     property="data",
	 *                     type="array",
	 *                     @OA\Items(
	 *                         type="object",
	 *                         @OA\Property(property="id", type="integer", example=15),
	 *                         @OA\Property(property="keyword", type="string", example="Digital Marketing"),
	 *                         @OA\Property(property="status", type="string", example="active"),
	 *                         @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-04T10:20:30Z")
	 *                     )
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


	public function getPaginatedAssignedKeywords(Request $request)
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

		// ✅ Default to 10 if `length` not provided
		$perPage = (int) $request->input('length', 10);

		$leads = DB::table('assigned_kwds')
			->join('parent_category', 'assigned_kwds.parent_cat_id', '=', 'parent_category.id')
			->join('child_category', 'assigned_kwds.child_cat_id', '=', 'child_category.id')
			->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
			->select(
				'assigned_kwds.id',
				'assigned_kwds.zone_id',
				'parent_category.parent_category',
				'child_category.child_category',
				'keyword.keyword',
				'assigned_kwds.created_at'
			)
			->where('assigned_kwds.client_id', $user->id)
			->orderBy('assigned_kwds.created_at', 'desc')
			->paginate($perPage);

		$data = [];

		foreach ($leads as $lead) {
			$zone = Zone::find($lead->zone_id);
			$zonename = $zone ? $zone->zone : "";



			$data[] = [
				'keyword' => $lead->keyword,
				'parent_category' => $lead->parent_category,
				'child_category' => $lead->child_category,
				'zone' => $zonename,
				'assign_kwd_id' => $lead->id,
			];
		}

		// ✅ Proper response for DataTables style
		return response()->json([
			'draw' => intval($request->input('draw')),
			'recordsTotal' => $leads->total(),
			'recordsFiltered' => $leads->total(),
			'data' => $data,
			'current_page' => $leads->currentPage(),
			'last_page' => $leads->lastPage(),
			'per_page' => $leads->perPage(),
		]);
	}
	/**
	 * @OA\Get(
	 *     path="/api/business/get-keywords",
	 *     tags={"Keywords"},
	 *     summary="Get all keywords",
	 *     description="Fetches a list of available keywords.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="Keywords retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=1),
	 *                     @OA\Property(property="keyword", type="string", example="Artificial Intelligence Training")
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

	public function getKeywords(Request $request)
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
		$search = [];
		if ($request->has('search')) {
			$search = $request->input('search');
		}
		$data['citylist'] = Citieslists::get();
		$data['keywordlist'] = Keyword::select('id', 'keyword', 'child_category_id', 'parent_category_id', 'city_id')->get();
		$data['clientID'] = $user->id;
		echo json_encode($data);
	}
	/**
	 * @OA\Post(
	 *     path="/api/business/saveKeywordAssign/{id}",
	 *     tags={"Keywords"},
	 *     summary="Assign a keyword to the authenticated user",
	 *     description="Assign a keyword to the authenticated user's account/business.",
	 *     security={{"bearerAuth":{}}}, 
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"kw_id","parent_cat_id","child_cat_id","zone_id"},
	 *             @OA\Property(property="kw_id", type="integer", example=5),
	 *             @OA\Property(property="parent_cat_id", type="integer", example=2),
	 *             @OA\Property(property="child_cat_id", type="integer", example=8),
	 *             @OA\Property(property="zone_id", type="integer", example=3)
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Keyword assigned successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Keyword assigned successfully."),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="id", type="integer", example=15),
	 *                 @OA\Property(property="kw_id", type="integer", example=5),
	 *                 @OA\Property(property="parent_cat_id", type="integer", example=2),
	 *                 @OA\Property(property="child_cat_id", type="integer", example=8),
	 *                 @OA\Property(property="zone_id", type="integer", example=3)
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
	 *                 @OA\Property(property="kw_id", type="array",
	 *                     @OA\Items(type="string", example="The kw_id field is required.")
	 *                 )
	 *             )
	 *         )
	 *     )
	 * )
	 */

	public function saveKeywordAssign(Request $request, $id)
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
		$client = Client::withTrashed()->where('id', $user->id)->first();


		$validator = Validator::make($request->all(), [
			//'city' => 'required',
			//'keyword' => 'required',
			//	'zone_id' => 'required',
			'keyword' => 'required|unique:assigned_kwds,kw_id,NULL,id,client_id,' . $client->id . ',kw_id,' . $request->input('keyword'),



		]);
		if ($validator->fails()) {
			$errorsBag = $validator->getMessageBag()->toArray();
			return response()->json(['status' => true, 'errors' => $errorsBag], 400);
		}




		$data = [];
		$keyw = Keyword::find($request->input('keyword'));

		if (!empty($keyw)) {

			$assignvalidation = AssignedKWDS::where('parent_cat_id', $keyw->parent_category_id)->where('child_cat_id', $keyw->child_category_id)->where('kw_id', $keyw->id)->where('client_id', $client->id)->get()->count();

			if ($assignvalidation == 0) {

				$assignedKWDS = new AssignedKWDS;
				$assignedKWDS->client_id = $client->id;
				//	$assignedKWDS->city_id = $request->input('city');
				//	$assignedKWDS->zone_id = $request->input('zone_id');
				$assignedKWDS->parent_cat_id = $keyw->parent_category_id;
				$assignedKWDS->child_cat_id = $keyw->child_category_id;
				$assignedKWDS->kw_id = $keyw->id;
				$assignedKWDS->sold_on_position = 'diamond';
				$keyword = Keyword::find($keyw->id);
				$keywordSellCount = KeywordSellCount::where('slug', 'diamond')->first();
				if (!empty($keywordSellCount)) {
					if ($keyword->category === 'Category 1') {
						$assignedKWDS->sold_on_price = $keywordSellCount->cat1_price;
					} else if ($keyword->category === 'Category 2') {
						$assignedKWDS->sold_on_price = $keywordSellCount->cat2_price;
					} else if ($keyword->category === 'Category 3') {
						$assignedKWDS->sold_on_price = $keywordSellCount->cat3_price;
					} elseif ($keyword->category === 'Category 4') {
						$assignedKWDS->sold_on_price = $keywordSellCount->cat4_price;
					} elseif ($keyword->category === 'Category 5') {
						$assignedKWDS->sold_on_price = $keywordSellCount->cat5_price;
					} elseif ($keyword->category === 'Category 6') {
						$assignedKWDS->sold_on_price = $keywordSellCount->cat6_price;
					} elseif ($keyword->category === 'Category 7') {
						$assignedKWDS->sold_on_price = $keywordSellCount->cat7_price;
					} elseif ($keyword->category === 'Category 8') {
						$assignedKWDS->sold_on_price = $keywordSellCount->cat8_price;
					} elseif ($keyword->category === 'Category 9') {
						$assignedKWDS->sold_on_price = $keywordSellCount->cat9_price;
					} elseif ($keyword->category === 'Category 10') {
						$assignedKWDS->sold_on_price = $keywordSellCount->cat10_price;
					} else {
						$assignedKWDS->sold_on_price = '220';
					}
				}

				if ($assignedKWDS->save()) {
					$keyword->diamond_pos_sold = $keyword->diamond_pos_sold + 1;
				}


				if ($keyword->save()) {
					$data['status'] = true;
					$data['message'] = "Keyword Assign add successfully !";
				} else {
					$data['status'] = false;
					$data['message'] = "Keyword Assign could not be successfully, Please try again !";
				}
			} else {
				$data['status'] = false;
				$data['message'] = "Already exist Keyword, Please try again !";
			}


		} else {
			$data['status'] = false;
			$data['message'] = "Keyword not found, Please try again !";
		}
		echo json_encode($data);
	}
}
