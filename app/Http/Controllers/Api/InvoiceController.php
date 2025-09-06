<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Client\Client;
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
	 *     path="/api/business/billing-history",
	 *     tags={"Billing"},
	 *     summary="Get billing history",
	 *     description="Fetch a list of billing or invoice records with optional filters",
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

	public function billingHistory(Request $request)
	{
		$search = [];
		if ($request->has('search')) {
			$search = $request->input('search');
		}
		return view('business.billingHistory', ['search' => $search]);
	}



	/**
	 * @OA\Get(
	 *     path="/api/business/get-billing-history",
	 *     tags={"Billing"},
	 *     summary="Get billing history",
	 *     description="Fetch a list of billing or invoice records with optional filters",
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
	 *     @OA\Parameter(
	 *         name="status",
	 *         in="query",
	 *         description="Filter by payment status",
	 *         required=false,
	 *         @OA\Schema(type="string", enum={"paid","pending","failed"})
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
	 *                     @OA\Property(property="payment_date", type="string", format="date-time", example="2025-09-06T12:00:00Z"),
	 *                     @OA\Property(property="description", type="string", example="Monthly subscription")
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
	public function getBillingHistory(Request $request)
	{
		if ($request->ajax()) {
			$clientID = auth()->guard('clients')->user()->id;
			$payments = DB::table('payment_histories')
				->where('client_id', $clientID)
				->orderBy('created_at', 'desc')
				->paginate($request->input('length'));

			$returnLeads = $data = [];
			$returnLeads['draw'] = $request->input('draw');
			$returnLeads['recordsTotal'] = $payments->total();
			$returnLeads['recordsFiltered'] = $payments->total();
			foreach ($payments as $payment) {
				$action = '';
				$separator = '';
				if ($payment->invoice_status == '1') {
					$action .= $separator . '<a href="javascript:void(0)" data-toggle="popover" title="Invoice PDF" id="invoiceBillingPdf" data-trigger="hover" data-placement="left" data-sid="' . $payment->id . '"><i aria-hidden="true" class="bi bi-file-earmark-pdf"></i></a>';
				}

				$data[] = [
					date_format(date_create($payment->created_at), 'd M Y'),
					$payment->paid_amount,
					$payment->gst_tax,
					$payment->total_amount,
					$action,

				];
			}
			$returnLeads['data'] = $data;
			return response()->json($returnLeads);
		}
	}
	/**
	 * @OA\Get(
	 *     path="/api/business/getinvoiceBillingPrintPdf/{invoice_id}",
	 *     tags={"Billing"},
	 *     summary="Get invoice PDF",
	 *     description="Generate or fetch the PDF for a specific invoice",
	 *     @OA\Parameter(
	 *         name="invoice_id",
	 *         in="path",
	 *         description="ID of the invoice to generate PDF for",
	 *         required=true,
	 *         @OA\Schema(type="integer", example=301)
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Invoice PDF generated successfully",
	 *         @OA\MediaType(
	 *             mediaType="application/pdf"
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="Invoice not found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Invoice not found")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=400,
	 *         description="Invalid request",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Invalid invoice ID")
	 *         )
	 *     )
	 * )
	 */

	public function getinvoiceBillingPrintPdf(Request $request)
	{
		if (isset($_GET['pid'])) {
			if ($request->input('action') == 'getinvoicePrintPdf') {
				$paymnetid = $_GET['pid'];
				$paymentprint = PaymentHistory::find($paymnetid);
				$client = Client::withTrashed()->where('id', $paymentprint->client_id)->first();
				return response()->view("business.getInvoicePrintPdfSlip", ['paymentprint' => $paymentprint, 'client' => $client]);
				die;
			}
		}
	}
	/**
	 * @OA\Get(
	 *     path="/api/business/coinsHistory",
	 *     tags={"Coins"},
	 *     summary="Get coins transaction history",
	 *     description="Fetch a list of all coin transactions for the user with optional filters",
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
	 *     @OA\Parameter(
	 *         name="type",
	 *         in="query",
	 *         description="Filter by transaction type",
	 *         required=false,
	 *         @OA\Schema(type="string", enum={"credit","debit"})
	 *     ),
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
		$data['coinsLeads'] = DB::table('assigned_leads')
			->join('leads', 'leads.id', '=', 'assigned_leads.lead_id')
			->leftjoin('citylists', 'leads.city_id', '=', 'citylists.id')
			->leftjoin('keyword', 'assigned_leads.kw_id', '=', 'keyword.id')

			->select('leads.*', 'assigned_leads.client_id', 'assigned_leads.lead_id', 'assigned_leads.created_at as created', 'assigned_leads.coins', 'assigned_leads.scrapLead')

			->orderBy('assigned_leads.created_at', 'desc')
			->where('assigned_leads.client_id', $user->id)->get();

		$search = [];
		if ($request->has('search')) {
			$search = $request->input('search');
		}

		echo json_encode($data);

	 
	}



	/**
 * @OA\Get(
 *     path="/api/business/get-paginated-payment-history",
 *     tags={"Payment"},
 *     summary="Get paginated payment history",
 *     description="Fetch a paginated list of payment transactions with optional filters",
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
 *     @OA\Parameter(
 *         name="status",
 *         in="query",
 *         description="Filter by payment status",
 *         required=false,
 *         @OA\Schema(type="string", enum={"success","pending","failed"})
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Payment history retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer", example=501),
 *                     @OA\Property(property="transaction_id", type="string", example="TXN-20250906-001"),
 *                     @OA\Property(property="amount", type="number", format="float", example=1500.75),
 *                     @OA\Property(property="status", type="string", example="success"),
 *                     @OA\Property(property="payment_date", type="string", format="date-time", example="2025-09-06T12:00:00Z"),
 *                     @OA\Property(property="method", type="string", example="credit_card")
 *                 )
 *             ),
 *             @OA\Property(
 *                 property="pagination",
 *                 type="object",
 *                 @OA\Property(property="page", type="integer", example=1),
 *                 @OA\Property(property="limit", type="integer", example=20),
 *                 @OA\Property(property="total", type="integer", example=120)
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

	public function getPaginatedPaymentHistory(Request $request)
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

		 
		$payments = DB::table('payment_histories')
			->where('client_id', $user->id)
			->orderBy('created_at', 'desc')
			->paginate($request->input('length'));

		$returnLeads = $data = [];
		$returnLeads['draw'] = $request->input('draw');
		$returnLeads['recordsTotal'] = $payments->total();
		$returnLeads['recordsFiltered'] = $payments->total();
		foreach ($payments as $payment) {
			$action = '';
			$separator = '';
			$action .= $separator . '<a href="javascript:void(0)" data-toggle="popover" title="Invoice PDF" id="paymentPrint" data-trigger="hover" data-placement="left" data-sid="' . $payment->id . '"><i aria-hidden="true" class="fa fa-file-pdf-o"></i></a>';

			$data[] = [
				date_format(date_create($payment->created_at), 'd M Y'),
				$payment->paid_amount,
				$payment->gst_tax,
				$payment->total_amount,
				$payment->payment_mode,
			];
		}
		$returnLeads['data'] = $data;
		return response()->json($returnLeads);
	}
}
