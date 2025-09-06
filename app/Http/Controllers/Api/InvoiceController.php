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


	public function billingHistory(Request $request)
	{
		$search = [];
		if ($request->has('search')) {
			$search = $request->input('search');
		}
		return view('business.billingHistory', ['search' => $search]);
	}



	/**
	 * Get paginated leads.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
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
	 * Get paginated leads.
	 *
	 * @param  int  $id
	 * @return \Illuminate\Http\Response
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
