<?php

namespace App\Http\Controllers\Api;

use Illuminate\Broadcasting\Broadcasters\NullBroadcaster;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\Client\Client;
use Validator;
use DB;
use App\Exports\EnquiryExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Lead;
use Illuminate\Support\Facades\Auth;
use App\Models\LeadFollowUp;
use App\Models\Status;
use App\Models\AssignedLead;
use Illuminate\Validation\Rule;


class EnquiryController extends Controller
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
	 *     path="/api/business/{id}/follow-up",
	 *     summary="Get lead details with follow-up history",
	 *     description="Returns lead information, follow-up history with pagination, and available statuses",
	 *     tags={"Leads"},
	 *      security={{"bearerAuth":{}}},
	 *
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         description="Lead ID",
	 *         @OA\Schema(type="integer", example=12)
	 *     ),
	 *
	 *     @OA\Parameter(
	 *         name="length",
	 *         in="query",
	 *         required=false,
	 *         description="Pagination length",
	 *         @OA\Schema(type="integer", example=10)
	 *     ),
	 *
	 *     @OA\Parameter(
	 *         name="count",
	 *         in="query",
	 *         required=false,
	 *         description="Limit records count or use 'all'",
	 *         @OA\Schema(type="string", example="all")
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Successful response",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="object",
	 *
	 *                 @OA\Property(
	 *                     property="lead",
	 *                     type="object",
	 *                     @OA\Property(property="lead_id", type="integer", example=12),
	 *                     @OA\Property(property="assignId", type="integer", example=45),
	 *                     @OA\Property(property="name", type="string", example="John Doe"),
	 *                     @OA\Property(property="email", type="string", example="john@example.com"),
	 *                     @OA\Property(property="city", type="string", example="Delhi"),
	 *                     @OA\Property(property="city_id", type="integer", example=3),
	 *                     @OA\Property(property="kw_id", type="integer", example=8),
	 *                     @OA\Property(property="kw_text", type="string", example="Web Development"),
	 *                     @OA\Property(property="status_id", type="integer", example=2),
	 *                     @OA\Property(property="status", type="string", example="Open"),
	 *                     @OA\Property(property="created", type="string", example="2025-01-15")
	 *                 ),
	 *
	 *                 @OA\Property(
	 *                     property="lead_follow_up",
	 *                     type="array",
	 *                     @OA\Items(
	 *                         type="object",
	 *                         @OA\Property(property="id", type="integer", example=101),
	 *                         @OA\Property(property="lead_id", type="integer", example=12),
	 *                         @OA\Property(property="status", type="string", example="Follow Up"),
	 *                         @OA\Property(property="remarks", type="string", example="Client interested"),
	 *                         @OA\Property(property="follow_up_date", type="string", example="25 Jan 2025"),
	 *                         @OA\Property(property="created_at", type="string", example="25 Jan 2025 04:30 PM")
	 *                     )
	 *                 ),
	 *
	 *                 @OA\Property(
	 *                     property="statuses",
	 *                     type="array",
	 *                     @OA\Items(
	 *                         type="object",
	 *                         @OA\Property(property="id", type="integer", example=1),
	 *                         @OA\Property(property="name", type="string", example="Open")
	 *                     )
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Unauthenticated: Token is missing or invalid"),
	 *             @OA\Property(property="error", type="string", example="token_missing_or_invalid")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=404,
	 *         description="Client not found"
	 *     )
	 * )
	 */


	public function getFollowUps(Request $request, $id)
	{

		if (!Auth::guard('sanctum')->check()) {
			return response()->json([
				'status' => false,
				'message' => 'Unauthenticated: Token is missing or invalid',
				'error' => 'token_missing_or_invalid'
			], 401);
		}

		// Check if user is active
		$user = auth('sanctum')->user();
		if (!$user) {
			return response()->json([
				'status' => false,
				'message' => 'Unauthenticated: Token is missing or invalid',
				'error' => 'token_missing_or_invalid'
			], 401);
		}

		// ✅ Validation
		$validator = Validator::make(
			['lead_id' => $id],
			[
				'lead_id' => [
					'required',
					'integer',
					Rule::exists('assigned_leads', 'lead_id')
						->where(function ($query) use ($user) {
							$query->where('client_id', $user->id);
						}),
				],
			]
		);


		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'message' => 'Validation failed',
				'errors' => $validator->errors()
			], 422);
		}


		$client = Client::find($user->id);
		if (!$client) {
			$data['status'] = false;
			$data['message'] = 'Client not found';
		}
		$clientID = $client->id;
		$lead = DB::table('leads')
			->join('assigned_leads', 'leads.id', '=', 'assigned_leads.lead_id')
			->select('leads.*', 'assigned_leads.*', 'assigned_leads.client_id', 'assigned_leads.lead_id', 'assigned_leads.created_at as created', 'assigned_leads.id as assignId')
			->orderBy('assigned_leads.created_at', 'desc')
			->where('assigned_leads.client_id', $clientID)->where('leads.id', $id)->first();
		if (!$lead) {
			return response()->json([
				'status' => false,
				'message' => 'Lead not found'
			], 404);
		}
		$data['lead'] = [
			'lead_id' => $lead->id,
			'assignId' => $lead->assignId,
			'favorite_lead' => $lead->favorite_lead,
			'readLead' => $lead->readLead,
			'scrapLead' => $lead->scrapLead,
			'scrapPay' => $lead->scrapPay,
			'scrapValue' => $lead->scrapValue,
			'primeLead' => $lead->primeLead,
			'name' => $lead->name,
			'email' => $lead->email,
			'city' => $lead->city_name,		 
			'kw_text' => $lead->kw_text,
			'status_id' => $lead->status_id,
			'status' => $lead->status_name,
			'created' => date('Y-m-d', strtotime($lead->created)),
		];


		$leadLastFollowUp = DB::table('lead_follow_ups as lfu')
			->join('status as s', 's.id', '=', 'lfu.status')
			->where('lfu.lead_id', $id)
			->where('lfu.client_id', $user->id)
			->select(
				'lfu.*',
				's.name as status_name'
			)
			->orderByDesc('lfu.id');

		/* ---------- PAGINATION ---------- */
		$length = (int) $request->input('length', 10);

		$leads = $leadLastFollowUp->paginate($length);

		/* ---------- OPTIONAL LIMIT ---------- */
		if ($request->input('count') !== 'all') {
			$leads->getCollection()->splice(
				(int) $request->input('count')
			);
		}

		/* ---------- MAP DATA ---------- */
		$leadFollowUp = $leads->getCollection()->transform(function ($item) {

			return [
				'follow_id' => $item->id,
				'lead_id' => $item->lead_id,
				'client_id' => $item->client_id,
				'status' => $item->status_name,
				'remarks' => $item->remark ?? '',
				'follow_up_date' => $item->expected_date_time
					? date('d M Y', strtotime($item->expected_date_time))
					: null,
				'created_at' => date('d M Y h:i A', strtotime($item->created_at)),
			];
		});


		$data['lead_follow_up'] = $leadFollowUp;
		$data['statuses'] = DB::table('status')
			->where('lead_follow_up', '1')
			->select('id', 'name')
			->get();

		return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $data,

		], 200);


	}

	/**
	 * @OA\Post(
	 *     path="/api/business/{id}/save-follow-up",
	 *     summary="Create lead follow-up",
	 *     description="Store a new follow-up entry for a lead",
	 *     tags={"Leads"},
	 *       security={{"bearerAuth":{}}},
	 *
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         required=true,
	 *         description="Lead ID",
	 *         @OA\Schema(type="integer", example=15)
	 *     ),
	 *
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"status","remark"},
	 *             type="object",
	 *
	 *             @OA\Property(
	 *                 property="status",
	 *                 type="integer",
	 *                 description="Status ID",
	 *                 example=3
	 *             ),
	 *
	 *             @OA\Property(
	 *                 property="remark",
	 *                 type="string",
	 *                 description="Follow-up remarks",
	 *                 example="Client asked for a call back"
	 *             ),
	 *
	 *             @OA\Property(
	 *                 property="expected_date_time",
	 *                 type="string",
	 *                 format="date-time",
	 *                 nullable=true,
	 *                 example="2025-01-25 15:30:00",
	 *                 description="Required if selected status expects date/time"
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Follow-up stored successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Successfully")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=400,
	 *         description="Validation error",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="status", type="integer", example=1),
	 *             @OA\Property(
	 *                 property="errors",
	 *                 type="object",
	 *                 example={
	 *                     "status": {"The status field is required."},
	 *                     "remark": {"The remark field is required."}
	 *                 }
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Unauthenticated: Token is missing or invalid"),
	 *             @OA\Property(property="error", type="string", example="token_missing_or_invalid")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=404,
	 *         description="Lead not found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Enquiry not found")
	 *         )
	 *     )
	 * )
	 */

	public function storeFollowUp(Request $request, $id)
	{
		if (!Auth::guard('sanctum')->check()) {
			return response()->json([
				'status' => false,
				'message' => 'Unauthenticated: Token is missing or invalid',
				'error' => 'token_missing_or_invalid'
			], 401);
		}

		// Check if user is active
		$user = auth('sanctum')->user();
		if (!$user) {
			return response()->json([
				'status' => false,
				'message' => 'Unauthenticated: Token is missing or invalid',
				'error' => 'token_missing_or_invalid'
			], 401);
		}

		$validator = Validator::make($request->all(), [

			'status' => 'required|integer',
			'remark' => 'required',

		]);
		if ($validator->fails()) {
			$errorsBag = $validator->getMessageBag()->toArray();
			return response()->json(['status' => 1, 'errors' => $errorsBag], 400);
		}

		// check now expected date and time if status is not - not interested/location issue
		$statusModel = Status::find($request->input('status'));

		if ($statusModel->show_exp_date) {
			$validator = Validator::make($request->all(), [
				'expected_date_time' => 'required',
			]);
			if ($validator->fails()) {
				$errorsBag = $validator->getMessageBag()->toArray();
				return response()->json(['status' => 1, 'errors' => $errorsBag], 400);
			}
		}

		$lead = Lead::find($id);
		if (!empty($lead)) {
			$leadFollowUp = new LeadFollowUp;
			$status = Status::findorFail($request->input('status'));
			if (!strcasecmp($status->name, 'npup')) {
				$npupCount = LeadFollowUp::where('lead_id', $id)->where('status', $status->id)->count();
				if ($npupCount >= 15) {
					$status = Status::where('name', 'LIKE', 'Not Interested')->first();
					$leadFollowUp->status = $status->id;
				} else {
					$leadFollowUp->status = $request->input('status');
				}
			} else {
				$leadFollowUp->status = $request->input('status');
			}


			$leadFollowUp->remark = trim($request->input('remark'));
			$leadFollowUp->lead_id = $id;
			$leadFollowUp->client_id = $user->id;
			$leadFollowUp->expected_date_time = NULL;
			if ($request->input('expected_date_time') != '') {
				$leadFollowUp->expected_date_time = date('Y-m-d H:i:s', strtotime($request->input('expected_date_time')));
			}
			if ($leadFollowUp->save()) {


				return response()->json([
					'status' => true,
					'message' => "Successfully",
				], 200);
			}
		} else {

			return response()->json([
				'status' => false,
				'message' => "Enquiry not found",
			], 200);
		}

	}




	/**
	 * @OA\Post(
	 *     path="/api/business/pause-lead",
	 *     tags={"Leads"},
	 *     summary="Pause lead",
	 *     description="Pause a lead for the authenticated business user",
	 *     security={{"bearerAuth":{}}},
	 *@OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"pauseLead"},
	 *             @OA\Property(property="pauseLead", type="boolean", enum={"true","false"}, example=true)
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Lead paused successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Lead paused successfully")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation error",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="The given data was invalid.")
	 *         )
	 *     )
	 * )
	 */
	public function pauseLead(Request $request)
	{
		if (!Auth::guard('sanctum')->check()) {
			return response()->json([
				'status' => false,
				'message' => 'Unauthenticated: Token is missing or invalid',
				'error' => 'token_missing_or_invalid'
			], 401);
		}

		// Check if user is active
		$user = auth('sanctum')->user();
		if (!$user) {
			return response()->json([
				'status' => false,
				'message' => 'Unauthenticated: Token is missing or invalid',
				'error' => 'token_missing_or_invalid'
			], 401);
		}

		$client = Client::find($user->id);
		if (!$client) {
			$data['status'] = false;
			$data['message'] = 'Client not found';
		}

		if ($request->pauseLead == 'true') {
			$client->pauseLead = '1';
		} else {
			$client->pauseLead = '0';
		}
		if ($client->save()) {
			$data['status'] = true;
			$data['message'] = 'Pause lead updated';

		} else {
			$data['status'] = false;
			$data['message'] = 'Not Pause lead';
		}

		return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $data,

		], 200);
	}

	/**
	 * @OA\Post(
	 *     path="/api/business/save-scrap-lead",
	 *     tags={"Leads"},
	 *     summary="Save scrap lead",
	 *     description="Scrap a lead and refund coins if all businesses scrap the lead",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"assignId","clientId","scrapValue"},
	 *             @OA\Property(property="assignId", type="integer", example=101),
	 *             @OA\Property(property="clientId", type="integer", example=1748),
	 *             @OA\Property(property="scrapValue", type="integer", example=3)
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Scrap saved successfully"
	 *     ),
	 *     @OA\Response(response=401, description="Unauthenticated"),
	 *     @OA\Response(response=422, description="Validation error")
	 * )
	 */

	public function saveScrapLead(Request $request)
	{
		// Auth check
		if (!Auth::guard('sanctum')->check()) {
			return response()->json([
				'status' => false,
				'message' => 'Unauthenticated',
			], 401);
		}

		$user = auth('sanctum')->user();

		// Validation
		$validator = Validator::make($request->all(), [
			'assignId' => 'required|integer|exists:assigned_leads,id',
			'clientId' => 'required|integer|exists:clients,id',
			'scrapValue' => 'required|integer|between:1,8',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'message' => 'Validation failed',
				'errors' => $validator->errors()
			], 422);
		}

		// Fetch assigned lead
		$assignedLead = AssignedLead::where('id', $request->assignId)
			->where('client_id', $request->clientId)
			->first();

		if (!$assignedLead) {
			return response()->json([
				'status' => false,
				'message' => 'Assigned lead not found'
			], 404);
		}

		DB::beginTransaction();

		try {
			// All leads for same enquiry
			$coinsLeads = AssignedLead::where('lead_id', $assignedLead->lead_id)
				->where('scrapPay', '0')
				->get();

			$scrapCount = AssignedLead::where('lead_id', $assignedLead->lead_id)
				->where('scrapLead', '1')
				->count();

			// Refund coins when last business scraps
			if ($coinsLeads->count() === ($scrapCount + 1)) {
				foreach ($coinsLeads as $coinsLead) {
					$client = Client::find($coinsLead->client_id);
					if ($client) {
						$client->increment('coins_amt', $coinsLead->coins);
					}

					$coinsLead->update(['scrapPay' => '1']);
				}
			}

			// Update scrap status
			$assignedLead->update([
				'scrapLead' => '1',
				'scrapValue' => $request->scrapValue,
			]);

			DB::commit();

			return response()->json([
				'status' => true,
				'message' => 'Lead scrapped successfully',
				'data' => [
					'assignId' => $assignedLead->id,
					'lead_id' => $assignedLead->lead_id,
					'scrapValue' => $request->scrapValue
				]
			], 200);

		} catch (\Exception $e) {
			DB::rollBack();

			return response()->json([
				'status' => false,
				'message' => 'Something went wrong',
				'error' => $e->getMessage()
			], 500);
		}
	}

	/**
	 * @OA\Get(
	 *     path="/api/business/get-scrap/{assignId}",
	 *     tags={"Leads"},
	 *     summary="Get scrap lead details",
	 *     description="Fetch scrap lead details by assigned lead ID",
	 *     security={{"bearerAuth":{}}},
	 *
	 *     @OA\Parameter(
	 *         name="assignId",
	 *         in="path",
	 *         required=true,
	 *         description="Assigned lead id",
	 *         @OA\Schema(type="integer", example=101)
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Scrap lead fetched successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Scrap lead fetched successfully"),
	 *             @OA\Property(property="data", type="object")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated"
	 *     )
	 * )
	 */
	public function getScrapLead(Request $request, $assignId)
	{
		// Auth check
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
				'message' => 'Unauthenticated',
			], 401);
		}

		// Validate PATH parameter
		$validator = Validator::make(
			['assignId' => $assignId],
			['assignId' => 'required|integer|exists:assigned_leads,id']
		);

		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'errors' => $validator->errors()
			], 422);
		}

		// Fetch assigned lead
		$assignedLead = AssignedLead::find($assignId);

		if (!$assignedLead) {
			return response()->json([
				'status' => false,
				'message' => 'Assigned lead not found'
			], 404);
		}

		// Scrap reasons
		$data = [
			[
				'classId' => 'gridRadios1',
				'name' => 'scrapLead',
				'clientid' => $assignedLead->client_id,
				'assignId' => $assignedLead->id,
				'lead_id' => $assignedLead->lead_id,
				'scrapName' => 'Student is just exploring and not planning to hire any tutor',
				'value' => '1',
			],
			[
				'classId' => 'gridRadios2',
				'name' => 'scrapLead',
				'clientid' => $assignedLead->client_id,
				'assignId' => $assignedLead->id,
				'lead_id' => $assignedLead->lead_id,
				'scrapName' => 'Enquiry posted by a tutor, institute, or agency',
				'value' => '2',
			],
			[
				'classId' => 'gridRadios3',
				'name' => 'scrapLead',
				'clientid' => $assignedLead->client_id,
				'assignId' => $assignedLead->id,
				'lead_id' => $assignedLead->lead_id,
				'scrapName' => 'Student has selected wrong category',
				'value' => '3',
			],
			[
				'classId' => 'gridRadios4',
				'name' => 'scrapLead',
				'clientid' => $assignedLead->client_id,
				'assignId' => $assignedLead->id,
				'lead_id' => $assignedLead->lead_id,
				'scrapName' => 'Student selected wrong locality',
				'value' => '4',
			],
			[
				'classId' => 'gridRadios5',
				'name' => 'scrapLead',
				'clientid' => $assignedLead->client_id,
				'assignId' => $assignedLead->id,
				'lead_id' => $assignedLead->lead_id,
				'scrapName' => 'Student asking only for male/female tutor',
				'value' => '5',
			],
			[
				'classId' => 'gridRadios6',
				'name' => 'scrapLead',
				'clientid' => $assignedLead->client_id,
				'assignId' => $assignedLead->id,
				'lead_id' => $assignedLead->lead_id,
				'scrapName' => 'Student phone number invalid or not reachable',
				'value' => '6',
			],
			[
				'classId' => 'gridRadios7',
				'name' => 'scrapLead',
				'clientid' => $assignedLead->client_id,
				'assignId' => $assignedLead->id,
				'lead_id' => $assignedLead->lead_id,
				'scrapName' => 'Student already hired a tutor',
				'value' => '7',
			],
			[
				'classId' => 'gridRadios8',
				'name' => 'scrapLead',
				'clientid' => $assignedLead->client_id,
				'assignId' => $assignedLead->id,
				'lead_id' => $assignedLead->lead_id,
				'scrapName' => 'Student is showing suspicious behaviour, with an intention to do a payment scam or any other misuse.',
				'value' => '8',
			],
		];

		return response()->json([
			'status' => true,
			'message' => 'Successfully',
			'data' => $data
		], 200);
	}

	/**
	 * @OA\Post(
	 *     path="/api/business/save-readLead",
	 *     tags={"Leads"},
	 *     summary="Save read lead",
	 *     description="Mark a lead as favorite for the authenticated user.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"assignId"},
	 *             @OA\Property(property="assignId", type="integer", example=11),
	 *            
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Lead added to favorites successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Lead marked as favorite."),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="id", type="integer", example=5),
	 *                 @OA\Property(property="user_id", type="integer", example=1),
	 *                 @OA\Property(property="assignId", type="integer", example=101)
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
	 *                 @OA\Property(property="lead_id", type="array",
	 *                     @OA\Items(type="string", example="The lead_id field is required.")
	 *                 )
	 *             )
	 *         )
	 *     )
	 * )
	 */


	public function readLead(Request $request)
	{

		if (!Auth::guard('sanctum')->check()) {
			return response()->json([
				'status' => false,
				'message' => 'Unauthenticated',
				'error' => 'token_missing_or_invalid'
			], 401);
		}

		$user = auth('sanctum')->user();


		$validator = Validator::make($request->all(), [
			'assignId' => 'required|integer|exists:assigned_leads,id'
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'message' => 'Validation failed',
				'errors' => $validator->errors()
			], 422);
		}

		/* ---------- AUTHORIZE ---------- */
		$assignedLead = AssignedLead::where('id', $request->assignId)
			->where('client_id', $user->id)
			->first();

		if (!$assignedLead) {
			return response()->json([
				'status' => false,
				'message' => 'Assigned lead not found'
			], 200);
		}

		/* ---------- UPDATE ---------- */
		$assignedLead->readLead = '1';
		$assignedLead->save();

		return response()->json([
			'status' => true,
			'message' => 'Lead marked as read'
		], 200);
	}

	/**
	 * @OA\Post(
	 *     path="/api/business/save-favorite",
	 *     tags={"Leads"},
	 *     summary="Save favorite lead",
	 *     description="Mark a lead as favorite for the authenticated user.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"assignId"},
	 *             @OA\Property(property="assignId", type="integer", example=101)
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Lead added to favorites successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Lead marked as favorite."),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="id", type="integer", example=5),
	 *                 @OA\Property(property="user_id", type="integer", example=1),
	 *                 @OA\Property(property="assignId", type="integer", example=101)
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
	 *                 @OA\Property(property="lead_id", type="array",
	 *                     @OA\Items(type="string", example="The lead_id field is required.")
	 *                 )
	 *             )
	 *         )
	 *     )
	 * )
	 */

	public function saveFavoritleads(Request $request)
	{
		// Auth check
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

		// Validate request
		$request->validate([
			'assignId' => 'required|integer|exists:assigned_leads,id',
		]);

		// Fetch lead
		$assignedLead = AssignedLead::find($request->assignId);

		if (!$assignedLead) {
			return response()->json([
				'status' => false,
				'message' => 'Assigned lead not found'
			], 404);
		}

		// Update favorite status
		$assignedLead->favorite_lead = '1';

		if (!$assignedLead->save()) {
			return response()->json([
				'status' => false,
				'message' => 'Favorite lead not updated'
			], 500);
		}

		return response()->json([
			'status' => true,
			'message' => 'Favorite lead updated successfully',
			'data' => $assignedLead
		], 200);
	}

	/**
	 * @OA\Post(
	 *     path="/api/business/un-favorite",
	 *     tags={"Leads"},
	 *     summary="Remove lead from favorites",
	 *     description="Unmark a previously favorited lead for the authenticated client.",
	 *     security={{"bearerAuth":{}}},
	 *
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"assignId"},
	 *             @OA\Property(
	 *                 property="assignId",
	 *                 type="integer",
	 *                 example=101,
	 *                 description="Assigned lead ID"
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Lead removed from favorites successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Lead removed from favorites."),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="object",
	 *                 @OA\Property(property="assignId", type="integer", example=101),
	 *                 @OA\Property(property="favoriteLead", type="integer", example=0)
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Unauthenticated: Token is missing or invalid")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation error",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Validation failed"),
	 *             @OA\Property(
	 *                 property="errors",
	 *                 type="object",
	 *                 @OA\Property(
	 *                     property="assignId",
	 *                     type="array",
	 *                     @OA\Items(type="string", example="The assignId field is required.")
	 *                 )
	 *             )
	 *         )
	 *     )
	 * )
	 */


	public function unFavoritleads(Request $request)
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
				'message' => 'Unauthenticated',
			], 401);
		}


		$validator = Validator::make($request->all(), [
			'assignId' => 'required|integer|exists:assigned_leads,id',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'errors' => $validator->errors()
			], 422);
		}


		$assignedLead = AssignedLead::where('id', $request->assignId)
			->where('client_id', $user->id)
			->first();

		if (!$assignedLead) {
			return response()->json([
				'status' => false,
				'message' => 'Assigned lead not found'
			], 404);
		}


		if ((int) $assignedLead->scrapPay === 1) {
			return response()->json([
				'status' => false,
				'message' => 'Already scrap paid this lead'
			], 403);
		}


		$assignedLead->favorite_lead = '0';
		$assignedLead->save();

		return response()->json([
			'status' => true,
			'message' => 'Lead unfavorited successfully',
			'data' => [
				'assignId' => $assignedLead->id,
				'favorite_lead' => $assignedLead->favorite_lead
			]
		], 200);
	}


	/**
	 * @OA\Get(
	 *     path="/api/business/get-leads",
	 *     tags={"Leads"},
	 *     summary="Get all leads",
	 *     description="Fetch list of leads for the authenticated user/business. Requires Bearer token.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="List of leads",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     type="object",
	 *                     @OA\Property(property="id", type="integer", example=1),
	 *                     @OA\Property(property="name", type="string", example="Rahul Sharma"),
	 *                     @OA\Property(property="email", type="string", example="rahul@example.com"),
	 *                     @OA\Property(property="phone", type="string", example="+91-9876543210"),
	 *                     @OA\Property(property="message", type="string", example="I am interested in your services."),
	 *                     @OA\Property(property="status", type="string", example="new"),
	 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-04T12:45:00Z")
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
	public function getLeads(Request $request)
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

		$client = Client::select('id', 'address', 'business_name', 'business_slug')->where('id', $user->id)->first();

		if ($client->address) {
			$address = urlencode($client->address);
			$url = "https://nominatim.openstreetmap.org/search?q={$address}&format=json&limit=1";

			$options = [
				"http" => [
					"header" => "User-Agent: MyWebsite/1.0 (contact@mywebsite.com)\r\n"
				]
			];

			$context = stream_context_create($options);
			$response = file_get_contents($url, false, $context);
			$geodata = json_decode($response, true);

			if (!empty($geodata[0])) {
				$latitude = $geodata[0]['lat'];
				$longitude = $geodata[0]['lon'];
				$map = 'https://www.google.com/maps?q=' . $latitude . ',' . $longitude;
			}


		} else {
			$map = "";
		}


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
			->select('leads.*', 'assigned_leads.*', 'assigned_leads.client_id', 'assigned_leads.lead_id', 'assigned_leads.created_at as created', 'assigned_leads.id as assignId')
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
				$coins = "";
				if (!empty($val->scrapLead)) {
					$coins = ['color' => 'green', 'coin' => $val->coins];
				} else if ($val->coins) {
					$coins = ['color' => 'red', 'coin' => $val->coins];
				}

				$created = get_time(strtotime($val->created)) . ' ago';

				$businessName = !empty($client->business_name) ? $client->business_name : 'our company';
				$keyword = !empty($val->kw_text) ? $val->kw_text : 'your enquiry';
				$addressText = !empty($client->address) ? $client->address : '';
				$mapText = !empty($client->business_map) ? '\n Directions: ' . $client->business_map : '';
				$profile_url = 'https://www.quickdials.com/business-details/' . $client->business_slug;

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
				$frmcheckText = '';
				if (!empty($val->frmcheck) && is_array($val->frmcheck)) {
					$frmcheckText = implode(', ', $val->frmcheck);
				}
				$parts = array_filter([
					$val->kw_text ? "Interested in " . trim($val->kw_text) : '',
					$frmcheckText ? "Mode of " . trim($frmcheckText) : '',
					$val->zone ? "location " . trim($val->zone) : '',
					$val->plan ? "plan " . trim($val->plan) : '',
					$val->age ? "age " . trim($val->age) : '',
					$val->experience ? "with experience " . trim($val->experience) : '',
				]);

				$main = implode(" • ", $parts);

				$remark = $main;

				if (!empty($val->remark)) {
					$remark = $remark . " " . trim($val->remark);
				}


				$leads_list[$key] = [
					'lead_id' => $val->lead_id ?? null,
					'assignId' => $val->assignId ?? null,
					'favorite_lead' => $val->favorite_lead ?? 0,
					'readLead' => $val->readLead ?? 0,
					'scrapLead' => $val->scrapLead ?? 0,
					'scrapPay' => $val->scrapPay ?? 0,
					'scrapValue' => $val->scrapValue ?? 0,
					'primeLead' => $val->primeLead ?? 0,
					'name' => trim($val->name ?? '') ?: null,
					'mobile' => trim($val->mobile ?? '') ?: null,
					'email' => trim($val->email ?? '') ?: null,
					'remark' => trim($remark ?? '') ?: null,
			 

					'cityName' => !empty($val->city_name)
						? trim($val->city_name . (!empty($val->zone) ? ', ' . $val->zone : ''))
						: null,

			 
					'kw_text' => trim($val->kw_text ?? '') ?: null,

					'client_id' => $val->client_id ?? null,

					'createdDate' => $created ?? null,
					'coins' => $coins ?? 0,
					'user_share' => $user_share ?? 0,
				];
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
	}

	/**
	 * @OA\Get(
	 *     path="/api/business/get-enquiry",
	 *     tags={"Enquiries"},
	 *     summary="Get all enquiries",
	 *     description="Fetch all enquiries received by the authenticated business. Requires Bearer token.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="List of enquiries",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     type="object",
	 *                     @OA\Property(property="id", type="integer", example=1),
	 *                     @OA\Property(property="name", type="string", example="Ravi Kumar"),
	 *                     @OA\Property(property="email", type="string", example="ravi@example.com"),
	 *                     @OA\Property(property="phone", type="string", example="+91-9876543210"),
	 *                     @OA\Property(property="message", type="string", example="I want to know more about your services."),
	 *                     @OA\Property(property="status", type="string", example="new"),
	 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-04T14:20:00Z")
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
	public function getEnquiry(Request $request)
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
			$client = Client::select('id', 'address', 'business_name', 'business_slug')->where('id', $currentUser->id)->first();

			if ($client->address) {
				$address = urlencode($client->address);
				$url = "https://nominatim.openstreetmap.org/search?q={$address}&format=json&limit=1";

				$options = [
					"http" => [
						"header" => "User-Agent: MyWebsite/1.0 (contact@mywebsite.com)\r\n"
					]
				];

				$context = stream_context_create($options);
				$response = file_get_contents($url, false, $context);
				$geodata = json_decode($response, true);

				if (!empty($geodata[0])) {
					$latitude = $geodata[0]['lat'];
					$longitude = $geodata[0]['lon'];
					$map = 'https://www.google.com/maps?q=' . $latitude . ',' . $longitude;
				}


			} else {
				$map = "";
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
				->select('leads.*', 'assigned_leads.*', 'assigned_leads.client_id', 'assigned_leads.lead_id', 'assigned_leads.created_at as created', 'areas.area', 'zones.zone', 'assigned_leads.id as assignId')
				->orderBy('assigned_leads.created_at', 'desc')
				->where('assigned_leads.client_id', $currentUser->id)
				->paginate($perPage);

			if (!empty($leads)) {
				foreach ($leads as $key => $val) {

					$coins = "";

					if (!empty($val->scrapLead)) {
						$coins = ['color' => 'green', 'coin' => $val->coins];
					} else if ($val->coins) {
						$coins = ['color' => 'red', 'coin' => $val->coins];
					}

					$created = get_time(strtotime($val->created)) . ' ago';
					$businessName = !empty($client->business_name) ? $client->business_name : 'our company';
					$keyword = !empty($val->kw_text) ? $val->kw_text : 'your enquiry';
					$addressText = !empty($client->address) ? $client->address : '';
					$mapText = !empty($client->business_map) ? '\n Directions: ' . $client->business_map : '';
					$profile_url = 'https://www.quickdials.com/business-details/' . $client->business_slug;

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

					$frmcheckText = '';
					if (!empty($val->frmcheck) && is_array($val->frmcheck)) {
						$frmcheckText = implode(', ', $val->frmcheck);
					}
					$parts = array_filter([
						$val->kw_text ? "Interested in " . trim($val->kw_text) : '',
						$frmcheckText ? "Mode of " . trim($frmcheckText) : '',
						$val->zone ? "location " . trim($val->zone) : '',
						$val->plan ? "plan " . trim($val->plan) : '',
						$val->age ? "age " . trim($val->age) : '',
						$val->experience ? "with experience " . trim($val->experience) : '',
					]);

					$main = implode(" • ", $parts);

					$remark = $main;

					if (!empty($val->remark)) {
						$remark = $remark . " " . trim($val->remark);
					}

					$leads_list[$key] = array(
						'lead_id' => $val->lead_id ?? null,
						'assignId' => $val->assignId ?? null,
						'favorite_lead' => $val->favorite_lead ?? 0,
						'readLead' => $val->readLead ?? 0,
						'scrapLead' => $val->scrapLead ?? 0,
						'scrapPay' => $val->scrapPay ?? 0,
						'scrapValue' => $val->scrapValue ?? 0,
						'primeLead' => $val->primeLead ?? 0,

						'name' => !empty($val->name) ? trim($val->name) : null,
						'mobile' => !empty($val->mobile) ? trim($val->mobile) : null,
						'email' => !empty($val->email) ? trim($val->email) : null,

						'remark' => !empty($remark) ? trim($remark) : null,


						'cityName' => !empty($val->city_name)
							? trim($val->city_name . (!empty($val->zone) ? ', ' . $val->zone : ''))
							: null,

					 
						'kw_text' => !empty($val->kw_text) ? trim($val->kw_text) : null,

						'createdDate' => $created ?? null,
						'coins' => $coins ?? 0,
						'user_share' => $user_share ?? null,
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
			return response()->json([
				'status' => false,
				'message' => 'Failed to retrieve users: ' . $e->getMessage(),
			], 500);
		}

	}


	/**
	 * @OA\Get(
	 *     path="/api/business/get-new-enquiry",
	 *     tags={"Leads"},
	 *     summary="Get new enquiry",
	 *     description="Fetch a list of all leads with optional filters",
	 * 	   security={{"bearerAuth":{}}},
	 *     
	 *     @OA\Response(
	 *         response=200,
	 *         description="List of leads",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="John Doe"),
	 *                     @OA\Property(property="email", type="string", example="john@example.com"),
	 *                     @OA\Property(property="phone", type="string", example="+911234567890"),
	 *                     @OA\Property(property="status", type="string", example="new"),
	 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-06T12:00:00Z")
	 *                 )
	 *             ),
	 *             @OA\Property(
	 *                 property="pagination",
	 *                 type="object",
	 *                 @OA\Property(property="page", type="integer", example=1),
	 *                 @OA\Property(property="limit", type="integer", example=20),
	 *                 @OA\Property(property="total", type="integer", example=100)
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=400,
	 *         description="Invalid request",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Invalid parameters")
	 *         )
	 *     )
	 * )
	 */

	public function getNewEnquiry(Request $request)
	{

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

		$client = Client::select('id', 'address', 'business_name', 'business_slug')->where('id', $currentUser->id)->first();

		if ($client->address) {
			$address = urlencode($client->address);
			$url = "https://nominatim.openstreetmap.org/search?q={$address}&format=json&limit=1";

			$options = [
				"http" => [
					"header" => "User-Agent: MyWebsite/1.0 (contact@mywebsite.com)\r\n"
				]
			];

			$context = stream_context_create($options);
			$response = file_get_contents($url, false, $context);
			$geodata = json_decode($response, true);

			if (!empty($geodata[0])) {
				$latitude = $geodata[0]['lat'];
				$longitude = $geodata[0]['lon'];
				$map = 'https://www.google.com/maps?q=' . $latitude . ',' . $longitude;
			}


		} else {
			$map = "";
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
		$leads = DB::table('leads')
			->join('assigned_leads', 'leads.id', '=', 'assigned_leads.lead_id')
			->leftjoin('citylists', 'leads.city_id', '=', 'citylists.id')
			->leftjoin('areas', 'leads.area_id', '=', 'areas.id')
			->leftjoin('zones', 'leads.zone_id', '=', 'zones.id')
			->select('leads.*', 'assigned_leads.*', 'assigned_leads.client_id as clientId', 'assigned_leads.lead_id', 'assigned_leads.id as assignId', 'assigned_leads.created_at as created', 'areas.area', 'zones.zone', 'assigned_leads.id as assignId')
			->orderBy('assigned_leads.created_at', 'desc')
			->where('assigned_leads.readLead', '0')
			->where('assigned_leads.client_id', $currentUser->id)->get();
		if (!$leads) {
			return response()->json([
				'status' => false,
				'message' => 'Lead not found'
			], 200);
		}
		if (!empty($leads)) {
			$leads_list = [];
			foreach ($leads as $key => $val) {
				$coins = "";
				if (!empty($val->scrapLead)) {
					$coins = ['color' => 'green', 'coin' => $val->coins];
				} else if ($val->coins) {
					$coins = ['color' => 'red', 'coin' => $val->coins];
				}

				$created = get_time(strtotime($val->created)) . ' ago';
				$businessName = !empty($client->business_name) ? $client->business_name : 'our company';
				$keyword = !empty($val->kw_text) ? $val->kw_text : 'your enquiry';
				$addressText = !empty($client->address) ? $client->address : '';
				$mapText = !empty($client->business_map) ? '\n Directions: ' . $client->business_map : '';
				$profile_url = 'https://www.quickdials.com/business-details/' . $client->business_slug;

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


				$frmcheckText = '';
				if (!empty($val->frmcheck) && is_array($val->frmcheck)) {
					$frmcheckText = implode(', ', $val->frmcheck);
				}
				$parts = array_filter([
					$val->kw_text ? "Interested in " . trim($val->kw_text) : '',
					$frmcheckText ? "Mode of " . trim($frmcheckText) : '',
					$val->zone ? "location " . trim($val->zone) : '',
					$val->plan ? "plan " . trim($val->plan) : '',
					$val->age ? "age " . trim($val->age) : '',
					$val->experience ? "with experience " . trim($val->experience) : '',
				]);

				$main = implode(" • ", $parts);

				$remark = $main;

				if (!empty($val->remark)) {
					$remark = $remark . " " . trim($val->remark);
				}

				$leads_list[$key] = array(
					'lead_id' => $val->lead_id ?? null,
					'assignId' => $val->assignId ?? null,
					'favorite_lead' => $val->favorite_lead ?? 0,
					'readLead' => $val->readLead ?? 0,
					'scrapLead' => $val->scrapLead ?? 0,
					'scrapPay' => $val->scrapPay ?? 0,
					'scrapValue' => $val->scrapValue ?? 0,
					'primeLead' => $val->primeLead ?? 0,

					'name' => !empty($val->name) ? trim($val->name) : null,
					'mobile' => !empty($val->mobile) ? trim($val->mobile) : null,
					'email' => !empty($val->email) ? trim($val->email) : null,

					'remark' => !empty($remark) ? trim($remark) : null,


					'cityName' => !empty($val->city_name)
						? trim($val->city_name . (!empty($val->zone) ? ', ' . $val->zone : ''))
						: null,

				 
					'kw_text' => !empty($val->kw_text) ? trim($val->kw_text) : null,

					'createdDate' => $created ?? null,
					'coins' => $coins ?? 0,
					'user_share' => $user_share ?? null,
				);
			}
			$data['leadslist'] = $leads_list;
		}
		return response()->json([
			'status' => true,
			'data' => $data,



		], 200);


	}

	/**
	 * @OA\Get(
	 *     path="/api/business/get-myLead",
	 *     tags={"Leads"},
	 *     summary="Get myLead",
	 *     description="Fetch a list of all leads with optional filters",
	 *     security={{"bearerAuth":{}}},
	 *      
	 *     @OA\Response(
	 *         response=200,
	 *         description="List of leads",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="John Doe"),
	 *                     @OA\Property(property="email", type="string", example="john@example.com"),
	 *                     @OA\Property(property="phone", type="string", example="+911234567890"),
	 *                     @OA\Property(property="status", type="string", example="new"),
	 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-06T12:00:00Z")
	 *                 )
	 *             ),
	 *             @OA\Property(
	 *                 property="pagination",
	 *                 type="object",
	 *                 @OA\Property(property="page", type="integer", example=1),
	 *                 @OA\Property(property="limit", type="integer", example=20),
	 *                 @OA\Property(property="total", type="integer", example=100)
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=400,
	 *         description="Invalid request",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Invalid parameters")
	 *         )
	 *     )
	 * )
	 */

	public function getMyLead(Request $request)
	{

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

		$client = Client::select('id', 'address', 'business_name', 'business_slug')->where('id', $currentUser->id)->first();

		if ($client->address) {
			$address = urlencode($client->address);
			$url = "https://nominatim.openstreetmap.org/search?q={$address}&format=json&limit=1";

			$options = [
				"http" => [
					"header" => "User-Agent: MyWebsite/1.0 (contact@mywebsite.com)\r\n"
				]
			];

			$context = stream_context_create($options);
			$response = file_get_contents($url, false, $context);
			$geodata = json_decode($response, true);

			if (!empty($geodata[0])) {
				$latitude = $geodata[0]['lat'];
				$longitude = $geodata[0]['lon'];
				$map = 'https://www.google.com/maps?q=' . $latitude . ',' . $longitude;
			}


		} else {
			$map = "";
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
		$leads = DB::table('leads')
			->join('assigned_leads', 'leads.id', '=', 'assigned_leads.lead_id')
			->leftjoin('citylists', 'leads.city_id', '=', 'citylists.id')
			->leftjoin('areas', 'leads.area_id', '=', 'areas.id')
			->leftjoin('zones', 'leads.zone_id', '=', 'zones.id')
			->select('leads.*', 'assigned_leads.*', 'assigned_leads.client_id as clientId', 'assigned_leads.lead_id', 'assigned_leads.id as assignId', 'assigned_leads.created_at as created', 'areas.area', 'zones.zone', 'assigned_leads.id as assignId')

			->orderBy('assigned_leads.created_at', 'desc')
			->where('assigned_leads.favorite_lead', '!=', '1')

			->where('assigned_leads.client_id', $currentUser->id)->get();

		if (!$leads) {
			return response()->json([
				'status' => false,
				'message' => 'Lead not found'
			], 200);
		}
		if (!empty($leads)) {
			$leads_list = [];
			foreach ($leads as $key => $val) {
				$coins = "";
				if (!empty($val->scrapLead)) {
					$coins = ['color' => 'green', 'coin' => $val->coins];
				} else if ($val->coins) {
					$coins = ['color' => 'red', 'coin' => $val->coins];
				}

				$created = get_time(strtotime($val->created)) . ' ago';
				$businessName = !empty($client->business_name) ? $client->business_name : 'our company';
				$keyword = !empty($val->kw_text) ? $val->kw_text : 'your enquiry';
				$addressText = !empty($client->address) ? $client->address : '';
				$mapText = !empty($client->business_map) ? '\n Directions: ' . $client->business_map : '';
				$profile_url = 'https://www.quickdials.com/business-details/' . $client->business_slug;

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


				$frmcheckText = '';
				if (!empty($val->frmcheck) && is_array($val->frmcheck)) {
					$frmcheckText = implode(', ', $val->frmcheck);
				}
				$parts = array_filter([
					$val->kw_text ? "Interested in " . trim($val->kw_text) : '',
					$frmcheckText ? "Mode of " . trim($frmcheckText) : '',
					$val->zone ? "location " . trim($val->zone) : '',
					$val->plan ? "plan " . trim($val->plan) : '',
					$val->age ? "age " . trim($val->age) : '',
					$val->experience ? "with experience " . trim($val->experience) : '',
				]);

				$main = implode(" • ", $parts);

				$remark = $main;

				if (!empty($val->remark)) {
					$remark = $remark . " " . trim($val->remark);
				}

				$leads_list[$key] = array(
					'lead_id' => $val->lead_id ?? null,
					'assignId' => $val->assignId ?? null,
					'favorite_lead' => $val->favorite_lead ?? 0,
					'readLead' => $val->readLead ?? 0,
					'scrapLead' => $val->scrapLead ?? 0,
					'scrapPay' => $val->scrapPay ?? 0,
					'scrapValue' => $val->scrapValue ?? 0,
					'primeLead' => $val->primeLead ?? 0,

					'name' => !empty($val->name) ? trim($val->name) : null,
					'mobile' => !empty($val->mobile) ? trim($val->mobile) : null,
					'email' => !empty($val->email) ? trim($val->email) : null,

					'remark' => !empty($remark) ? trim($remark) : null,


					'cityName' => !empty($val->city_name)
						? trim($val->city_name . (!empty($val->zone) ? ', ' . $val->zone : ''))
						: null,

				 
					'kw_text' => !empty($val->kw_text) ? trim($val->kw_text) : null,
					'client_id' => $val->client_id ?? Null,
					'createdDate' => $created ?? null,
					'coins' => $coins ?? 0,
					'user_share' => $user_share ?? null,
				);
			}
			$data['leadslist'] = $leads_list;
		}


		return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $data,

		], 200);

	}

	/**
	 * @OA\Get(
	 *     path="/api/business/get-favorite-enquiry",
	 *     tags={"Leads"},
	 *     summary="Get myLead",
	 *     description="Fetch a list of all leads with optional filters",
	 * 	   security={{"bearerAuth":{}}},	 *      
	 *     @OA\Response(
	 *         response=200,
	 *         description="List of leads",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="John Doe"),
	 *                     @OA\Property(property="email", type="string", example="john@example.com"),
	 *                     @OA\Property(property="phone", type="string", example="+911234567890"),
	 *                     @OA\Property(property="status", type="string", example="new"),
	 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-06T12:00:00Z")
	 *                 )
	 *             ),
	 *             @OA\Property(
	 *                 property="pagination",
	 *                 type="object",
	 *                 @OA\Property(property="page", type="integer", example=1),
	 *                 @OA\Property(property="limit", type="integer", example=20),
	 *                 @OA\Property(property="total", type="integer", example=100)
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=400,
	 *         description="Invalid request",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Invalid parameters")
	 *         )
	 *     )
	 * )
	 */
	public function getFavoriteEnquiry(Request $request)
	{
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

		$client = Client::select('id', 'address', 'business_name', 'business_slug')->where('id', $currentUser->id)->first();

		if ($client->address) {
			$address = urlencode($client->address);
			$url = "https://nominatim.openstreetmap.org/search?q={$address}&format=json&limit=1";

			$options = [
				"http" => [
					"header" => "User-Agent: MyWebsite/1.0 (contact@mywebsite.com)\r\n"
				]
			];

			$context = stream_context_create($options);
			$response = file_get_contents($url, false, $context);
			$geodata = json_decode($response, true);

			if (!empty($geodata[0])) {
				$latitude = $geodata[0]['lat'];
				$longitude = $geodata[0]['lon'];
				$map = 'https://www.google.com/maps?q=' . $latitude . ',' . $longitude;
			}


		} else {
			$map = "";
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
		$leads = DB::table('leads')
			->join('assigned_leads', 'leads.id', '=', 'assigned_leads.lead_id')
			->leftjoin('citylists', 'leads.city_id', '=', 'citylists.id')
			->leftjoin('areas', 'leads.area_id', '=', 'areas.id')
			->leftjoin('zones', 'leads.zone_id', '=', 'zones.id')
			->select('leads.*', 'assigned_leads.*', 'assigned_leads.client_id as clientId', 'assigned_leads.lead_id', 'assigned_leads.id as assignId', 'assigned_leads.created_at as created', 'areas.area', 'zones.zone', 'assigned_leads.id as assignId')

			->orderBy('assigned_leads.created_at', 'desc')
			->where('assigned_leads.favorite_lead', '1')
			->where('assigned_leads.client_id', $currentUser->id)->get();
		if (!$leads) {
			return response()->json([
				'status' => false,
				'message' => "Lead not found",
			], 200);
		}

		if (!empty($leads)) {
			$leads_list = [];
			foreach ($leads as $key => $val) {
				$coins = "";
				if (!empty($val->scrapLead)) {
					$coins = ['color' => 'green', 'coin' => $val->coins];
				} else if ($val->coins) {
					$coins = ['color' => 'red', 'coin' => $val->coins];
				}

				$created = get_time(strtotime($val->created)) . ' ago';
				$businessName = !empty($client->business_name) ? $client->business_name : 'our company';
				$keyword = !empty($val->kw_text) ? $val->kw_text : 'your enquiry';
				$addressText = !empty($client->address) ? $client->address : '';
				$mapText = !empty($client->business_map) ? '\n Directions: ' . $client->business_map : '';
				$profile_url = 'https://www.quickdials.com/business-details/' . $client->business_slug;

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
				$frmcheckText = '';
				if (!empty($val->frmcheck) && is_array($val->frmcheck)) {
					$frmcheckText = implode(', ', $val->frmcheck);
				}
				$parts = array_filter([
					$val->kw_text ? "Interested in " . trim($val->kw_text) : '',
					$frmcheckText ? "Mode of " . trim($frmcheckText) : '',
					$val->zone ? "location " . trim($val->zone) : '',
					$val->plan ? "plan " . trim($val->plan) : '',
					$val->age ? "age " . trim($val->age) : '',
					$val->experience ? "with experience " . trim($val->experience) : '',
				]);

				$main = implode(" • ", $parts);

				$remark = $main;

				if (!empty($val->remark)) {
					$remark = $remark . " " . trim($val->remark);
				}

				$leads_list[$key] = array(
					'lead_id' => $val->lead_id ?? null,
					'assignId' => $val->assignId ?? null,
					'favorite_lead' => $val->favorite_lead ?? 0,
					'readLead' => $val->readLead ?? 0,
					'scrapLead' => $val->scrapLead ?? 0,
					'scrapPay' => $val->scrapPay ?? 0,
					'scrapValue' => $val->scrapValue ?? 0,
					'primeLead' => $val->primeLead ?? 0,

					'name' => !empty($val->name) ? trim($val->name) : null,
					'mobile' => !empty($val->mobile) ? trim($val->mobile) : null,
					'email' => !empty($val->email) ? trim($val->email) : null,

					'remark' => !empty($remark) ? trim($remark) : null,


					'cityName' => !empty($val->city_name)
						? trim($val->city_name . (!empty($val->zone) ? ', ' . $val->zone : ''))
						: null,

				 
					'kw_text' => !empty($val->kw_text) ? trim($val->kw_text) : null,
					'client_id' => $val->client_id ?? Null,
					'createdDate' => $created ?? null,
					'coins' => $coins ?? 0,
					'user_share' => $user_share ?? null,
				);
			}
			$data['leadslist'] = $leads_list;
		}


		return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $data,

		], 200);
	}

	/**
	 * @OA\Get(
	 *     path="/api/business/get-lead-details/{id}",
	 *     tags={"Leads"},
	 *     summary="Get myLead",
	 *     description="Fetch lead details",
	 *     security={{"bearerAuth":{}}},
	 *
	 *     @OA\Parameter(
	 *         name="id",
	 *         in="path",
	 *         description="Lead ID",
	 *         required=true,
	 *         @OA\Schema(type="integer", example=1)
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Lead details",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="object",
	 *                 @OA\Property(property="id", type="integer", example=101),
	 *                 @OA\Property(property="name", type="string", example="John Doe"),
	 *                 @OA\Property(property="email", type="string", example="john@example.com"),
	 *                 @OA\Property(property="phone", type="string", example="+911234567890"),
	 *                 @OA\Property(property="status", type="string", example="new"),
	 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-06T12:00:00Z")
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=400,
	 *         description="Invalid request",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Invalid parameters")
	 *         )
	 *     )
	 * )
	 */

	public function getLeadDetails(Request $request, $id)
	{
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

		$client = Client::select('id', 'address', 'business_name', 'business_slug')->where('id', $currentUser->id)->first();

		if ($client->address) {
			$address = urlencode($client->address);
			$url = "https://nominatim.openstreetmap.org/search?q={$address}&format=json&limit=1";

			$options = [
				"http" => [
					"header" => "User-Agent: MyWebsite/1.0 (contact@mywebsite.com)\r\n"
				]
			];

			$context = stream_context_create($options);
			$response = file_get_contents($url, false, $context);
			$geodata = json_decode($response, true);

			if (!empty($geodata[0])) {
				$latitude = $geodata[0]['lat'];
				$longitude = $geodata[0]['lon'];
				$map = 'https://www.google.com/maps?q=' . $latitude . ',' . $longitude;
			}


		} else {
			$map = "";
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
		$leads = DB::table('leads')
			->join('assigned_leads', 'leads.id', '=', 'assigned_leads.lead_id')
			->leftjoin('citylists', 'leads.city_id', '=', 'citylists.id')
			->leftjoin('areas', 'leads.area_id', '=', 'areas.id')
			->leftjoin('zones', 'leads.zone_id', '=', 'zones.id')
			->select('leads.*', 'assigned_leads.*', 'assigned_leads.client_id as clientId', 'assigned_leads.lead_id', 'assigned_leads.created_at as created', 'areas.area', 'zones.zone', 'assigned_leads.id as assignId')

			->orderBy('assigned_leads.created_at', 'desc')

			->where('assigned_leads.client_id', $currentUser->id)
			->where('assigned_leads.lead_id', $id)
			->first();
		if (!$leads) {
			return response()->json([
				'status' => false,
				'message' => "Lead not found",
			], 200);

		}
		$coins = "";
		if (!empty($leads->scrapLead)) {
			$coins = ['color' => 'green', 'coin' => $leads->coins];
		} else if ($leads->coins) {
			$coins = ['color' => 'red', 'coin' => $leads->coins];
		}

		$created = get_time(strtotime($leads->created)) . ' ago';
		$businessName = !empty($client->business_name) ? $client->business_name : 'our company';
		$keyword = !empty($leads->kw_text) ? $leads->kw_text : 'your enquiry';
		$addressText = !empty($client->address) ? $client->address : '';
		$mapText = !empty($client->business_map) ? '\n Directions: ' . $client->business_map : '';
		$profile_url = 'https://www.quickdials.com/business-details/' . $client->business_slug;

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

			$frmcheckText = '';
				if (!empty($leads->frmcheck) && is_array($leads->frmcheck)) {
					$frmcheckText = implode(', ', $leads->frmcheck);
				}
				$parts = array_filter([
					$leads->kw_text ? "Interested in " . trim($leads->kw_text) : '',
					$frmcheckText ? "Mode of " . trim($frmcheckText) : '',
					$leads->zone ? "location " . trim($leads->zone) : '',
					$leads->plan ? "plan " . trim($leads->plan) : '',
					$leads->age ? "age " . trim($leads->age) : '',
					$leads->experience ? "with experience " . trim($leads->experience) : '',
				]);

				$main = implode(" • ", $parts);

				$remark = $main;

				if (!empty($leads->remark)) {
					$remark = $remark . " " . trim($leads->remark);
				}
		$data['lead'] = [

			'lead_id' => $leads->lead_id,
			'name' => $leads->name,
			'assignId' => $leads->assignId,
			'favorite_lead' => $leads->favorite_lead,
			'readLead' => $leads->readLead,
			'scrapLead' => $leads->scrapLead,
			'scrapPay' => $leads->scrapPay,
			'scrapValue' => $leads->scrapValue,
			'primeLead' => $leads->primeLead,
			'email' => $leads->email,
			'kw_text' => $leads->kw_text,
			'createdDate' => $created,
			'coins' => $coins,
			'remark' => !empty($remark) ? trim($remark) : null,


			'cityName' => !empty($leads->city_name)
						? trim($leads->city_name . (!empty($leads->zone) ? ', ' . $leads->zone : ''))
						: null,
 
			'mobile' => $leads->mobile,
			'status_name' => $leads->status_name,		 
			'user_share' => $user_share,
		];

		$data['statuses'] = DB::table('status')
			->where('lead_follow_up', '1')
			->select('id', 'name')
			->get();

		return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $data,

		], 200);
	}

	/**
	 * @OA\Get(
	 *     path="/api/business/manage-enquiry",
	 *     tags={"Enquiries"},
	 *     summary="Get all enquiries",
	 *     description="Fetch a paginated list of enquiries with optional date filters",
	 *     security={{"bearerAuth":{}}},
	 *
	 *     @OA\Parameter(
	 *         name="page",
	 *         in="query",
	 *         description="Page number",
	 *         required=false,
	 *         @OA\Schema(type="integer", example=1)
	 *     ),
	 *
	 *     @OA\Parameter(
	 *         name="limit",
	 *         in="query",
	 *         description="Records per page",
	 *         required=false,
	 *         @OA\Schema(type="integer", example=20)
	 *     ),
	 *
	 *     @OA\Parameter(
	 *         name="date_from",
	 *         in="query",
	 *         description="Start date (YYYY-MM-DD)",
	 *         required=false,
	 *         @OA\Schema(type="string", format="date", example="2025-01-01")
	 *     ),
	 *
	 *     @OA\Parameter(
	 *         name="date_to",
	 *         in="query",
	 *         description="End date (YYYY-MM-DD)",
	 *         required=false,
	 *         @OA\Schema(type="string", format="date", example="2025-01-31")
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Enquiries fetched successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=201),
	 *                     @OA\Property(property="name", type="string", example="Jane Smith"),
	 *                     @OA\Property(property="email", type="string", example="jane@example.com"),
	 *                     @OA\Property(property="mobile", type="string", example="+911234567891"),
	 *                     @OA\Property(property="area", type="string", example="Andheri"),
	 *                     @OA\Property(property="zone", type="string", example="West"),
	 *                     @OA\Property(property="created_at", type="string", example="2025-09-06")
	 *                 )
	 *             ),
	 *             @OA\Property(property="pagination", type="object",
	 *                 @OA\Property(property="page", type="integer", example=1),
	 *                 @OA\Property(property="limit", type="integer", example=20),
	 *                 @OA\Property(property="total", type="integer", example=50)
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated"
	 *     )
	 * )
	 */


	public function manageEnquiry(Request $request)
	{
		// 🔐 Auth check
		if (!Auth::guard('sanctum')->check()) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthenticated'
			], 401);
		}

		$user = auth('sanctum')->user();

		$page = (int) $request->input('page', 1);
		$limit = (int) $request->input('limit', 20);

		$query = DB::table('leads')
			->join('assigned_leads', 'leads.id', '=', 'assigned_leads.lead_id')
			->leftJoin('citylists', 'leads.city_id', '=', 'citylists.id')
			->leftJoin('areas', 'leads.area_id', '=', 'areas.id')
			->leftJoin('zones', 'leads.zone_id', '=', 'zones.id')
			->where('assigned_leads.client_id', $user->id)
			->select(
				'leads.id',
				'leads.name',
				'leads.email',
				'leads.mobile',
				'areas.area',
				'zones.zone',
				'leads.kw_text',
				'leads.city_name',
				'assigned_leads.created_at',
				'assigned_leads.id as assignId'
			)
			->orderBy('assigned_leads.created_at', 'desc');

		// 📅 Date filters
		if ($request->filled('date_from')) {
			$query->whereDate('assigned_leads.created_at', '>=', $request->date_from);
		}

		if ($request->filled('date_to')) {
			$query->whereDate('assigned_leads.created_at', '<=', $request->date_to);
		}

		$enquiries = $query->paginate($limit, ['*'], 'page', $page);

		return response()->json([
			'success' => true,
			'data' => $enquiries->items(),
			'page' => $enquiries->currentPage(),
			'limit' => $enquiries->perPage(),
			'total' => $enquiries->total()

		], 200);
	}

	/**
	 * @OA\Get(
	 *     path="/api/business/export-enquiry",
	 *     tags={"Enquiries"},
	 *     summary="Get all export enquiries",
	 *     description="Fetch a paginated list of enquiries with optional date filters",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="date_from",
	 *         in="query",
	 *         description="Start date (YYYY-MM-DD)",
	 *         required=false,
	 *         @OA\Schema(type="string", format="date", example="2025-01-01")
	 *     ),
	 *
	 *     @OA\Parameter(
	 *         name="date_to",
	 *         in="query",
	 *         description="End date (YYYY-MM-DD)",
	 *         required=false,
	 *         @OA\Schema(type="string", format="date", example="2025-01-31")
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Enquiries fetched successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=201),
	 *                     @OA\Property(property="name", type="string", example="Jane Smith"),
	 *                     @OA\Property(property="email", type="string", example="jane@example.com"),
	 *                     @OA\Property(property="mobile", type="string", example="+911234567891"),
	 *                     @OA\Property(property="area", type="string", example="Andheri"),
	 *                     @OA\Property(property="zone", type="string", example="West"),
	 *                     @OA\Property(property="created_at", type="string", example="2025-09-06")
	 *                 )
	 *             ),
	 *             @OA\Property(property="pagination", type="object",
	 *                 @OA\Property(property="page", type="integer", example=1),
	 *                 @OA\Property(property="limit", type="integer", example=20),
	 *                 @OA\Property(property="total", type="integer", example=50)
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated"
	 *     )
	 * )
	 */
	public function exportEnquiry(Request $request)
	{
		// 🔐 Auth check
		if (!Auth::guard('sanctum')->check()) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthenticated'
			], 401);
		}

		$user = auth('sanctum')->user();

		$query = DB::table('leads')
			->join('assigned_leads', 'leads.id', '=', 'assigned_leads.lead_id')
			->leftJoin('citylists', 'leads.city_id', '=', 'citylists.id')
			->leftJoin('areas', 'leads.area_id', '=', 'areas.id')
			->leftJoin('zones', 'leads.zone_id', '=', 'zones.id')
			->where('assigned_leads.client_id', $user->id)
			->select(
				'leads.name',
				'leads.email',
				'leads.mobile',
				'leads.kw_text',
				'leads.city_name',
				'assigned_leads.created_at'
			)
			->orderBy('assigned_leads.created_at', 'desc');

		// 📅 Date filters
		if ($request->filled('date_from')) {
			$query->whereDate('assigned_leads.created_at', '>=', $request->date_from);
		}

		if ($request->filled('date_to')) {
			$query->whereDate('assigned_leads.created_at', '<=', $request->date_to);
		}

		$enquiries = $query->get();

		// 🧾 Prepare Excel rows
		$rows = [];
		foreach ($enquiries as $row) {
			$rows[] = [
				'Name' => $row->name,
				'Mobile' => $row->mobile,
				'Email' => $row->email,
				'Course' => $row->kw_text,
				'City' => $row->city_name,
				'Date' => date('d M, Y H:i:s', strtotime($row->created_at)),
			];
		}

		return response()->json([
			'success' => true,
			'data' => $rows,
		], 200);

	}
	/**
	 * @OA\Get(
	 *     path="/api/business/export-enquiry-download",
	 *     tags={"Enquiries"},
	 *     summary="Get all export enquiries",
	 *     description="Fetch a paginated list of enquiries with optional date filters",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="date_from",
	 *         in="query",
	 *         description="Start date (YYYY-MM-DD)",
	 *         required=false,
	 *         @OA\Schema(type="string", format="date", example="2025-01-01")
	 *     ),
	 *
	 *     @OA\Parameter(
	 *         name="date_to",
	 *         in="query",
	 *         description="End date (YYYY-MM-DD)",
	 *         required=false,
	 *         @OA\Schema(type="string", format="date", example="2025-01-31")
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Enquiries fetched successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=201),
	 *                     @OA\Property(property="name", type="string", example="Jane Smith"),
	 *                     @OA\Property(property="email", type="string", example="jane@example.com"),
	 *                     @OA\Property(property="mobile", type="string", example="+911234567891"),
	 *                     @OA\Property(property="area", type="string", example="Andheri"),
	 *                     @OA\Property(property="zone", type="string", example="West"),
	 *                     @OA\Property(property="created_at", type="string", example="2025-09-06")
	 *                 )
	 *             ),
	 *             @OA\Property(property="pagination", type="object",
	 *                 @OA\Property(property="page", type="integer", example=1),
	 *                 @OA\Property(property="limit", type="integer", example=20),
	 *                 @OA\Property(property="total", type="integer", example=50)
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated"
	 *     )
	 * )
	 */
	public function exportEnquiryDownload(Request $request)
	{
		// 🔐 Auth check
		if (!Auth::guard('sanctum')->check()) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthenticated'
			], 401);
		}

		$user = auth('sanctum')->user();

		$query = DB::table('leads')
			->join('assigned_leads', 'leads.id', '=', 'assigned_leads.lead_id')
			->leftJoin('citylists', 'leads.city_id', '=', 'citylists.id')
			->leftJoin('areas', 'leads.area_id', '=', 'areas.id')
			->leftJoin('zones', 'leads.zone_id', '=', 'zones.id')
			->where('assigned_leads.client_id', $user->id)
			->select(
				'leads.name',
				'leads.email',
				'leads.mobile',
				'leads.kw_text',
				'leads.city_name',
				'assigned_leads.created_at'
			)
			->orderBy('assigned_leads.created_at', 'desc');

		// 📅 Date filters
		if ($request->filled('date_from')) {
			$query->whereDate('assigned_leads.created_at', '>=', $request->date_from);
		}

		if ($request->filled('date_to')) {
			$query->whereDate('assigned_leads.created_at', '<=', $request->date_to);
		}

		$enquiries = $query->get();

		// 🧾 Prepare Excel rows
		$rows = [];
		foreach ($enquiries as $row) {
			$rows[] = [
				'Name' => $row->name,
				'Mobile' => $row->mobile,
				'Email' => $row->email,
				'Course' => $row->kw_text,
				'City' => $row->city_name,
				'Date' => date('d M, Y H:i:s', strtotime($row->created_at)),
			];
		}

		// 📥 Download Excel
		return Excel::download(
			new EnquiryExport($rows),
			'enquiries_' . date('d_m_Y_His') . '.xlsx'
		);
	}




}
