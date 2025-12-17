<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Client\Client;
use Barryvdh\DomPDF\Facade\Pdf;
use DB;
use App\Models\PaymentHistory;
class InvoiceController extends Controller
{
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
	 *     path="/api/business/get-invoice-history",
	 *     tags={"Billing"},
	 *     summary="Get billing history",
	 *     description="Fetch a list of billing or invoice records with optional filters",
	 * 	   security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="page",
	 *         in="query",
	 *         description="Page number for pagination",
	 *         required=false,
	 *         @OA\Schema(type="integer", default=1)
	 *     ),
	 *     @OA\Parameter(
	 *         name="limit",
	 *         in="query",
	 *         description="Number of records per page",
	 *         required=false,
	 *         @OA\Schema(type="integer", default=20)
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Billing history retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=301),
	 *                     @OA\Property(property="invoice_number", type="string", example="INV-20250906-001"),
	 *                     @OA\Property(property="amount", type="number", format="float", example=2500.50),
	 *                     @OA\Property(property="status", type="string", example="paid"),
	 *                     @OA\Property(property="payment_date", type="string", format="date-time", example="2025-09-06T12:00:00Z")
	 *                 )
	 *             ),
	 *             @OA\Property(
	 *                 property="pagination",
	 *                 type="object",
	 *                 @OA\Property(property="page", type="integer", example=1),
	 *                 @OA\Property(property="limit", type="integer", example=20),
	 *                 @OA\Property(property="total", type="integer", example=45)
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

	public function getInvoiceHistory(Request $request)
	{
		if (!Auth::guard('sanctum')->check()) {
			return response()->json([
				'success' => false,
				'message' => 'Unauthenticated: Token is missing or invalid'
			], 401);
		}

		$user = auth('sanctum')->user();

		$page = (int) $request->input('page', 1);
		$limit = (int) $request->input('limit', 20);

		$payments = PaymentHistory::where('client_id', $user->id)
			->orderBy('created_at', 'desc')
			->paginate($limit, ['*'], 'page', $page);

		// ✅ Use map() properly
		$data = $payments->getCollection()->map(function ($payment) {
			return [
				'id' => $payment->id,
				'business_name' => $payment->business_name ?? null,
				'cost_per_lead' => $payment->cost_per_lead ?? null,
				'customer_name' => $payment->customer_name ?? null,
				'package_name' => $payment->package_name ?? null,
				'invoice_no' => $payment->order_number ?? null,
				'invoice_date' => $payment->order_date ?? null,
				'transactionid' => $payment->transactionid ?? null,
				'coins_amt' => $payment->coins_amt ?? null,
				'paid_amount' => $payment->paid_amount,
				'gst_tax' => $payment->gst_tax,
				'total_amount' => $payment->total_amount,
				'tds_amount' => $payment->tds_amount,
				'tds_status' => $payment->tds_status,
				'payment_mode' => $payment->payment_mode,
				'invoice_status' => $payment->invoice_status,
				'payment_date' => $payment->created_at->format('d M Y'),
			];
		});

		return response()->json([
			'success' => true,
			'page' => $payments->currentPage(),
			'limit' => $payments->perPage(),
			'total' => $payments->total(),
			'data' => $data
		], 200);
	}


	/**
	 * @OA\Get(
	 *     path="/api/business/download-invoice/{invoice_id}",
	 *     tags={"Billing"},
	 *     summary="Download invoice PDF",
	 *     description="Download invoice PDF by invoice ID",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="invoice_id",
	 *         in="path",
	 *         required=true,
	 *         description="Invoice ID",
	 *         @OA\Schema(type="integer", example=301)
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Invoice PDF downloaded successfully",
	 *         @OA\MediaType(
	 *             mediaType="application/pdf"
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Unauthenticated")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=404,
	 *         description="Invoice not found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Invoice not found")
	 *         )
	 *     )
	 * )
	 */
	public function downloadInvoicePdf(Request $request, $invoice_id)
	{
		if (!Auth::guard('sanctum')->check()) {
			return response()->json([
				'status' => false,
				'message' => 'Unauthenticated: Token is missing or invalid',
				'error' => 'token_missing_or_invalid'
			], 401);
		}

		 

		$paymentprint = PaymentHistory::find($invoice_id);

		if (!$paymentprint) {
			return response()->json([
				'success' => false,
				'message' => 'Invoice not found'
			], 200);
		}

		$client = Client::withTrashed()->find($paymentprint->client_id);

		$pdf = Pdf::loadView(
			'business.getInvoicePrintPdfSlip',
			compact('paymentprint', 'client')
		);
		return $pdf->download(
			'invoice_' . $invoice_id . '_' . date('d-m-Y_H-i-s') . '.pdf'
		);
	}

	/**
	 * @OA\Get(
	 *     path="/api/business/coinsHistory",
	 *     tags={"Coins"},
	 *     summary="Get coins transaction history",
	 *     description="Fetch a list of all coin transactions for the user with optional filters",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Parameter(
	 *         name="page",
	 *         in="query",
	 *         description="Page number for pagination",
	 *         required=false,
	 *         @OA\Schema(type="integer", default=1)
	 *     ),
	 *     @OA\Parameter(
	 *         name="limit",
	 *         in="query",
	 *         description="Number of records per page",
	 *         required=false,
	 *         @OA\Schema(type="integer", default=20)
	 *     ),
	 *     
	 *     @OA\Response(
	 *         response=200,
	 *         description="Coins history retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=401),
	 *                     @OA\Property(property="type", type="string", example="credit"),
	 *                     @OA\Property(property="amount", type="number", format="float", example=50),
	 *                     @OA\Property(property="description", type="string", example="Referral bonus"),
	 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-06T12:00:00Z")
	 *                 )
	 *             ),
	 *             @OA\Property(
	 *                 property="pagination",
	 *                 type="object",
	 *                 @OA\Property(property="page", type="integer", example=1),
	 *                 @OA\Property(property="limit", type="integer", example=20),
	 *                 @OA\Property(property="total", type="integer", example=75)
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

	public function coinsHistory(Request $request)
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

		// 🪙 Coins history with pagination
		$coinsLeads = DB::table('assigned_leads')
			->join('leads', 'leads.id', '=', 'assigned_leads.lead_id')
			->leftJoin('citylists', 'leads.city_id', '=', 'citylists.id')
			->leftJoin('keyword', 'assigned_leads.kw_id', '=', 'keyword.id')
			->where('assigned_leads.client_id', $user->id)
			->orderBy('assigned_leads.created_at', 'desc')
			->select(
				'assigned_leads.id',
				'assigned_leads.lead_id',
				'assigned_leads.coins',
				'assigned_leads.scrapLead',
				'assigned_leads.created_at',
				'leads.name as lead_name',
				'leads.email',
				'leads.mobile',
				'citylists.city as city_name',
				'keyword.keyword as keyword_name'
			)
			->paginate($limit, ['*'], 'page', $page);

		// ✅ map() properly
		$data = $coinsLeads->getCollection()->map(function ($lead) {
			return [
				'lead_id' => $lead->lead_id,
				'lead_name' => $lead->lead_name,
				'email' => $lead->email,
				'phone' => $lead->mobile,
				'city' => $lead->city_name,
				'keyword' => $lead->keyword_name,
				'coins' => $lead->coins,
				'scrap_lead' => (bool) $lead->scrapLead,
				'date' => date('d M Y', strtotime($lead->created_at)),
			];
		});

		return response()->json([
			'success' => true,
			'data' => $data,

			'page' => $coinsLeads->currentPage(),
			'limit' => $coinsLeads->perPage(),
			'total' => $coinsLeads->total(),

		], 200);
	}






}
