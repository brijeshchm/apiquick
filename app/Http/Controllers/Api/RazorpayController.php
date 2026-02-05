<?php

namespace App\Http\Controllers\Api;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Session;
use App\Models\Citieslists;
use App\State;
use Validator;
use Illuminate\Support\Facades\Auth;

use App\Models\RazorpayHistory;
use App\Models\PaymentHistory;
use App\Models\Client\Client;
use Illuminate\Support\Str;
use Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\DB;
class RazorpayController extends Controller
{
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct(Request $request)
	{

		//testing Key  brijeshchauhansit@gmail.com
		define('RAZOR_KEY_ID', 'rzp_test_W4vtkmMCBlCwIo');
		define('RAZOR_KEY_SECRET', 'sYeslbCFPinBjmHBRNpz806I');

	}

	public function validation_input($data)
	{
		$data = trim($data);
		$data = stripslashes($data);
		$data = htmlspecialchars($data);
		return $data;
	}

	public function dataEncodeJsonBase64($o)
	{
		$o = json_encode($o);
		$o = base64_encode($o);
		return $o;
	}


	function dataDecodeJsonBase64($o)
	{
		$o = base64_decode($o);
		$o = json_decode($o);

		return $o;
	}
	public function payDeposit(Request $request)
	{
		if (isset($_GET['status'], $_GET['o']) && !empty($_GET['o'])) {
			$o = base64_decode($_GET['o'], $strict = false);
			$data = json_decode($o);
			$status = $_GET['status'];
		} else {
			$data = array();
		}
		return view('business.razorpay.pay-checkout', ['data' => $data]);

	}

	/**
	 * @OA\Post(
	 *     path="/api/business/razorpay/get-initial-payment-key",
	 *     summary="get Razorpay Payment key",
	 *     description="get a customized Razorpay payment key", 
	 *     tags={"Razorpay"},
	 *	   security={{"bearerAuth":{}}},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"encrypt"},
	 *             @OA\Property(
	 *                 property="encrypt",
	 *                 type="string",
	 *                 example="U2FsdGVkX19kXzEyMzQ1Ng==",
	 *                 description="Encrypted payment payload"
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Payment initiated successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Payment initiated successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="object",
	 *                 @OA\Property(property="payment_id", type="string", example="pay_NXJH123456"),
	 *                 @OA\Property(property="order_id", type="string", example="ORD123456789"),
	 *                 @OA\Property(property="amount", type="number", example=100.50),
	 *                 @OA\Property(property="currency", type="string", example="INR")
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation failed",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Validation failed"),
	 *             @OA\Property(property="errors", type="object")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=500,
	 *         description="Server error",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Failed to initiate payment")
	 *         )
	 *     )
	 * )
	 */



	public function getInitialPaymentKey(Request $request)
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
		$validator = Validator::make($request->all(), [
			'encrypt' => 'required|string',
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		$data = $this->dataDecodeJsonBase64($request->encrypt);
		$orderRef = 'QD' . strtoupper(Str::random(8));
		$data->razorpay_order_id = $orderRef;

		try {
			$api = new Api(
				config('services.razorpay.key'),
				config('services.razorpay.secret')
			);




			$client = Client::withTrashed()->where('id', $data->client_id)->first();


			$paymenthistory = new PaymentHistory;
			$paymenthistory->client_id = $client->id;
			$paymenthistory->customer_name = $client->first_name . ' ' . $client->last_name;
			$paymenthistory->business_name = trim($client->business_name);
			$paymenthistory->mobile = $client->mobile;
			$paymenthistory->email = $client->email;
			$paymenthistory->package_name = $client->client_type;
			$paymenthistory->coins_amt = $data->coins;
			$paymenthistory->paymentcollect = $user->id;
			$paymenthistory->leads_count = '0';
			$paymenthistory->cost_per_lead = '0';

			$paymenthistory->paid_amount = '0';
			$paymenthistory->gst_status = $data->gst_status;
			$paymenthistory->gst_tax = $data->gst_tax;
			$paymenthistory->gst_total_amount = $data->gst_total_amount;
			$paymenthistory->tds_amount = '0';
			$paymenthistory->total_amount = $data->total_amount;
			$paymenthistory->currency = 'INR';
			$paymenthistory->payment_url = '';
			$paymenthistory->payment_link_id = '';
			$paymenthistory->order_number = $orderRef;
			$paymenthistory->save();


			return response()->json([
				'success' => true,
				'message' => 'Payment order create successfully',
				'data' => [
					'payment_data' => $data,
					'encrypts' => $this->dataEncodeJsonBase64($data),
					'key' => config('services.razorpay.key'),
					'secret' => config('services.razorpay.secret'),
					'method_get' => 'https://api.quickdials.com/api/razorpay/verify?encrypt=',
					'razorpay_payment_status' => '',
					'razorpay_order_id' => '',
					'razorpay_payment_id' => '',
					'razorpay_signature' => '',

				]
			], 200);



		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to generate payment link',
				'error' => $e->getMessage()
			], 500);
		}


	}



	public function createPaymentLink(Request $request)
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
		$validator = Validator::make($request->all(), [
			'encrypt' => 'required|string',
		]);

		if ($validator->fails()) {
			return response()->json([
				'success' => false,
				'errors' => $validator->errors()
			], 422);
		}

		$data = $this->dataDecodeJsonBase64($request->encrypt);
		//  dd($data);

		$orderRef = 'QD' . strtoupper(Str::random(8));
		$data->razorpay_order_id = $orderRef;
		$encrypt = $this->dataEncodeJsonBase64($data);

		try {
			$api = new Api(
				config('services.razorpay.key'),
				config('services.razorpay.secret')
			);

			$paymentLink = $api->paymentLink->create([
				'amount' => $data->total_amount * 100,
				'currency' => 'INR',
				'accept_partial' => false,
				'reference_id' => $orderRef,
				'description' => 'Payment for Order',
				'customer' => [
					'name' => $data->customer_name,
					'email' => $data->email,
					'contact' => $data->phone,
					'client_id' => $data->client_id,
				],
				'notify' => [
					'sms' => false,
					'email' => false,
				],
				'notes' => [
					'order_reference' => $orderRef,
					'source' => 'API',
				],
				'callback_url' => url('/api/razorpay/verify?encrypt=' . $encrypt),
				'callback_method' => 'get',

			]);

			if ($paymentLink['status'] == 'created') {
				$client = Client::withTrashed()->where('id', $data->client_id)->first();


				$paymenthistory = new PaymentHistory;
				$paymenthistory->client_id = $client->id;
				$paymenthistory->customer_name = $client->first_name . ' ' . $client->last_name;
				$paymenthistory->business_name = trim($client->business_name);
				$paymenthistory->mobile = $client->mobile;
				$paymenthistory->email = $client->email;
				$paymenthistory->package_name = $client->client_type;
				$paymenthistory->coins_amt = $data->coins;
				$paymenthistory->paymentcollect = $user->id;
				$paymenthistory->leads_count = '0';
				$paymenthistory->cost_per_lead = '0';
				$paymenthistory->cost_per_lead = '0';
				$paymenthistory->paid_amount = '0';
				$paymenthistory->gst_status = $data->gst_status;
				$paymenthistory->gst_tax = $data->gst_tax;
				$paymenthistory->gst_total_amount = $data->gst_total_amount;
				$paymenthistory->tds_amount = '0';
				$paymenthistory->total_amount = $data->total_amount;
				$paymenthistory->currency = 'INR';
				$paymenthistory->payment_url = $paymentLink['short_url'];
				$paymenthistory->payment_link_id = $paymentLink['id'];
				$paymenthistory->order_number = $paymentLink['notes']['order_reference'];
				$paymenthistory->save();
			}

			return response()->json([
				'success' => true,
				'message' => 'Payment link generated successfully',
				'data' => [
					'payment_link_id' => $paymentLink['id'],
					'payment_url' => $paymentLink['short_url'],
					'key' => config('services.razorpay.key'),
					'secret' => config('services.razorpay.secret'),
					'status' => $paymentLink['status'],
				]
			], 200);



		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to generate payment link',
				'error' => $e->getMessage()
			], 500);
		}


	}


	public function verifyPayment(Request $request)
	{

		if ($request->encrypt) {

			$data = $this->dataDecodeJsonBase64($request->encrypt);
			try {

				$api = new Api(
					config('services.razorpay.key'),
					config('services.razorpay.secret')
				);


				if ($request->razorpay_payment_link_status == 'paid') {

					$paymentHistory = PaymentHistory::where('payment_link_id', $request->razorpay_payment_link_id)->first();

					$payment = PaymentHistory::find($paymentHistory->id);

					$payment->selectproofid = '';
					$payment->proofid = '';
					$payment->paid_amount = $data->total_amount;
					$payment->expired_from = date('Y-m-d');
					$payment->transactionid = $request->razorpay_payment_id;
					$payment->paymentcollect = $data->client_id;
					$payment->invoice_status = '1';
					$client = Client::find($data->client_id);
					$client->coins_amt += $data->coins;
					$client->expired_from = date('Y-m-d');
					$packageDays = [
						1000 => 180,
						2000 => 180,
						3000 => 180,
						4000 => 180,
						5000 => 365,
						10000 => 365,
						20000 => 730,   // 365 * 2
						40000 => 1460,  // 365 * 4
						50000 => 1460,  // 365 * 5
						100000 => 1825,  // 365 * 5
					];
					$baseAmount = (int) round($data->total_amount / 1.18);
					$days = $packageDays[$baseAmount] ?? 365;
					$today = Carbon::today();
					$expiredOn = Carbon::parse($client->expired_on);
					$startDate = $expiredOn->greaterThan($today) ? $expiredOn : $today;
					$newExpiry = $startDate->addDays($days);
					$client->expired_on = $newExpiry->format('Y-m-d');
					$payment->expired_on = $newExpiry->format('Y-m-d');

					$payment->save();
					$client->save();

				}



				return response()->json([
					'success' => true,
					'message' => 'Payment verified successfully'
				], 200);

			} catch (\Exception $e) {
				return response()->json([
					'success' => false,
					'message' => 'Payment verification failed',
					'error' => $e->getMessage()
				], 400);
			}
		}
	}

	public function webhook(Request $request)
	{
		Log::info('Payment request data', [
			'request' => $request->all()
		]);
		$signature = $request->header('X-Razorpay-Signature');
		$secret = env('RAZORPAY_WEBHOOK_SECRET');

		$generated = hash_hmac('sha256', $request->getContent(), $secret);

		if ($generated === $signature) {


			// Payment success / failed / captured
			return response()->json(['status' => 'ok']);
		}

		return response()->json(['status' => 'invalid'], 403);
	}
	/**
	 * Return the specified resource from storage.
	 *
	 * @param  obj  Request object
	 * @param  int  $id
	 * @return Json Response
	 */
	public function saveProcessing(Request $request)
	{



		if ($request->isMethod('post') && $request->input('checkout') == "CheckOut") {

			$this->validate($request, [
				'name' => 'required|string|regex:/^[\pL\s\-]+$/u|min:3|max:32',
				'email' => 'required|regex:/^[^\s()<>@,;:\/]+@\w[\w\.-]+\.[a-z]{2,}$/i',
				//	'pincode' 	=> 'required|numeric',					
				'phone' => 'required|numeric',
				'amount' => 'required|numeric',
				'course' => 'required|string|min:3|max:32',
				'country' => 'required|numeric',
				'state' => 'required|numeric',
				'city' => 'required',
				//	'address' 	=> 'required',						 					
			]);



			$data['name'] = $this->validation_input($request->input('name'));
			$data['email'] = $this->validation_input($request->input('email'));
			$data['course'] = $this->validation_input($request->input('course'));
			//	$data['pincode'] = $this->validation_input($request->input('pincode'));
			$data['amt'] = $this->validation_input($request->input('amount'));
			$data['phone'] = $this->validation_input($request->input('phone'));
			//	$data['add'] = $this->validation_input($request->input('address'));		
			//	$cityname =City::where('city_id',$request->input('city'))->first()->city_name;
			$statename = State::where('state_id', $request->input('state'))->first()->state_name;
			$countryname = Country::where('country_id', $request->input('country'))->first()->country_name;

			$data['city'] = $this->validation_input($request->input('city'));
			$data['state'] = $this->validation_input($statename);
			$data['country'] = $this->validation_input($countryname);

			$d = time();
			$traisaction = "QI_" . rand(10, 99) . '_' . $d;
			if (!empty($data)) {

				$s = 1;

			} else {
				$s = 0;
			}



			Session::put('buyerFirstName', $request->input('name'));
			Session::put('buyerEmail', $request->input('email'));
			Session::put('clientName', $request->input('course'));
			Session::put('amount', $request->input('amount'));
			Session::put('buyerPhone', $request->input('phone'));
			Session::put('buyerState', $statename);
			Session::put('buyerCountry', $countryname);
			Session::put('buyerCity', $request->input('city'));
			Session::put('buyerPinCode', $request->input('pincode'));
			Session::put('course', $request->input('course'));

			if ($s == 1) {
				$inv = $this->dataEncodeJsonBase64($data);
				$inv = "&inv=" . $inv;
				$o = $this->dataEncodeJsonBase64($data);

				return redirect('/pay-checkout?status=incompete&o=' . $o . $inv);
				exit;

			} else {
				return redirect('/pay-deposit');
			}


			return view('business.razorpay.pay-deposit');
		}
	}




	/**
	 * Return the specified resource from storage.
	 *
	 * @param  obj  Request object
	 * @param  int  $id
	 * @return Json Response
	 */
	public function checkOut(Request $request)
	{
		$data = $this->dataDecodeJsonBase64($_GET['o']);
		return view('business.razorpay.pay-checkout', ['data' => $data]);
	}





	function get_curl_handle($payment_id, $data)
	{
		$url = 'https://api.razorpay.com/v1/payments/' . $payment_id . '/capture';
		$key_id = RAZOR_KEY_ID;
		$key_secret = RAZOR_KEY_SECRET;
		$params = http_build_query($data);
		//cURL Request
		$ch = curl_init();
		//set the url, number of POST vars, POST data
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERPWD, $key_id . ':' . $key_secret);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		return $ch;
	}

	public function razorPayCheckout(Request $request)
	{
		if (!empty($_POST['razorpay_payment_id']) && !empty($_POST['merchant_order_id'])) {

			$json = array();
			$razorpay_payment_id = $_POST['razorpay_payment_id'];
			$merchant_order_id = $_POST['merchant_order_id'];
			$currency_code = $_POST['currency_code_id'];

			$dataFlesh = array(
				'card_holder_name' => $_POST['card_holder_name_id'],
				'merchant_amount' => $_POST['merchant_amount'],
				'merchant_total' => $_POST['merchant_total'],
				'surl' => $_POST['merchant_surl_id'],
				'furl' => $_POST['merchant_furl_id'],
				'currency_code' => $currency_code,
				'order_id' => $_POST['merchant_order_id'],
				'razorpay_payment_id' => $_POST['razorpay_payment_id'],
				'pay_to' => $_POST['pay'],
				'coins' => $_POST['coins'],
				'gst_tax' => $_POST['gst_tax'],
				'client_id' => $_POST['client_id'],
				'username' => $_POST['username'],
				'paid_amount' => $_POST['paid_amount'],

				'email' => $_POST['email'],
				'phone' => $_POST['phone'],
				// 'address' => $_POST['address'],
				'city' => $_POST['city'],
				'billing_country' => $_POST['billing_country'],
				'billing_state' => $_POST['billing_state'],
				'getpay' => 1,
			);

			$paymentInfo = $dataFlesh;
			$order_info = array('order_status_id' => $_POST['merchant_order_id']);
			$amount = $_POST['merchant_total'];
			$currency_code = $_POST['currency_code_id'];
			// bind amount and currecy code
			$data = array(
				'amount' => $amount,
				'currency' => $currency_code,
			);
			$success = false;
			$error = '';
			try {
				$ch = $this->get_curl_handle($razorpay_payment_id, $data);
				//execute post
				$result = curl_exec($ch);


				$data = json_decode($result);

				$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

				if ($result === false) {
					$success = false;
					$error = 'Curl error: ' . curl_error($ch);
				} else {
					$response_array = json_decode($result, true);
					//Check success response
					if ($http_status === 200 and isset($response_array['error']) === false) {
						$success = true;
					} else {
						$success = false;
						if (!empty($response_array['error']['code'])) {
							$error = $response_array['error']['code'] . ':' . $response_array['error']['description'];
						} else {
							$error = 'Invalid Response <br/>' . $result;
						}
					}
				}
				//close connection
				curl_close($ch);
			} catch (Exception $e) {
				$success = false;
				$error = $e->getMessage();
			}
			if ($success === true) {
				if (!$order_info['order_status_id']) {

					$json['data'] = json_encode($paymentInfo);
					$json['redirectURL'] = $_POST['merchant_surl_id'];
				} else {

					$feesHisoty = new RazorpayHistory;
					$feesHisoty->name = $paymentInfo['card_holder_name'];
					$feesHisoty->email = $paymentInfo['email'];
					$feesHisoty->phone = $paymentInfo['phone'];
					$feesHisoty->username = $paymentInfo['username'];
					$feesHisoty->coins = $paymentInfo['coins'];
					$feesHisoty->client_id = $paymentInfo['client_id'];
					$feesHisoty->merchant_amount = $paymentInfo['merchant_amount'];
					$feesHisoty->merchant_total = $paymentInfo['merchant_total'];
					$feesHisoty->currency_code = $paymentInfo['currency_code'];
					$feesHisoty->order_id = $paymentInfo['order_id'];
					$feesHisoty->razorpay_payment_id = $paymentInfo['razorpay_payment_id'];
					$feesHisoty->city = $paymentInfo['city'];
					$feesHisoty->billing_country = $paymentInfo['billing_country'];
					$feesHisoty->billing_state = $paymentInfo['billing_state'];
					$feesHisoty->pay_to = $paymentInfo['pay_to'];
					$feesHisoty->getpay = 1;
					$feesHisoty->message = $error;
					$feesHisoty->save();

					$clientdeatails = Client::find($paymentInfo['client_id']);
					$paymenthistory = new PaymentHistory;
					$paymenthistory->client_id = $paymentInfo['client_id'];
					$paymenthistory->customer_name = $paymentInfo['card_holder_name'];
					$paymenthistory->business_name = $clientdeatails->business_name;
					$paymenthistory->mobile = $paymentInfo['phone'];
					$paymenthistory->email = $paymentInfo['email'];
					$paymenthistory->package_name = $clientdeatails->client_type;
					$paymenthistory->coins_amt = $paymentInfo['coins'];
					$paymenthistory->selectproofid = "";
					$paymenthistory->proofid = "";
					$paymenthistory->paid_amount = $paymentInfo['paid_amount'];
					$paymenthistory->tds_status = "No";
					$paymenthistory->tds_amount = "0";
					$paymenthistory->gst_tax = $paymentInfo['gst_tax'];
					$paymenthistory->gst_total_amount = $paymentInfo['merchant_amount'];
					$paymenthistory->gst_status = "Yes";
					$paymenthistory->total_amount = $paymentInfo['merchant_amount'];
					$paymenthistory->transactionid = $paymentInfo['order_id'];
					$paymenthistory->paymentcollect = 0;
					$paymenthistory->payment_mode = "razorpay";
					$paymenthistory->payment_bank = "";
					$paymenthistory->save();

					$clientdeatails->coins_amt = $clientdeatails->coins_amt + $paymentInfo['coins'];
					if ($clientdeatails->expired_on == '0000-00-00 00:00:00' || $clientdeatails->expired_on == 'NULL') {

						$newDate = date('Y-m-d', strtotime(now() . ' +365 days'));

					} else if (strtotime($clientdeatails->expired_on) > strtotime(date('Y-m-d'))) {
						$newDate = date('Y-m-d', strtotime($clientdeatails->expired_on . ' +365 days'));

					} else if (strtotime($clientdeatails->expired_on) < strtotime(date('Y-m-d'))) {
						$newDate = date('Y-m-d', strtotime(now() . ' +365 days'));

					} else {
						$newDate = date('Y-m-d', strtotime(now() . ' +365 days'));
					}
					$clientdeatails->expired_on = $newDate;
					$clientdeatails->active_status = "1";
					$clientdeatails->paid_status = "1";
					$clientdeatails->save();

					$json['data'] = json_encode($paymentInfo);
					$json['redirectURL'] = $_POST['merchant_surl_id'];
				}
			} else {


				$json['data'] = json_encode($paymentInfo);
				$json['redirectURL'] = $_POST['merchant_furl_id'];
			}
			$json['msg'] = 'success';
		} else {

			$json['msg'] = 'An error occured. Contact site administrator, please!';
		}
		header('Content-Type: application/json');
		echo json_encode($json);




	}


	public function success(Request $request)
	{

		if (isset($_GET)) {
			$data = $_GET;
		} else {
			$data = "";
		}

		return view('business.razorpay.success', ['data' => $data]);
	}

	public function failed(Request $request)
	{

		return view('business.razorpay.failed');
	}

	public function getInvoicePrintPdf(Request $request)
	{

		if (isset($_POST['pid'])) {
			if ($request->input('action') == 'getInvoicePrintPdf') {

				$order_id = $_POST['pid'];
				$paydetails = RazorpayHistory::where('order_id', $order_id)->first();

				return response()->view("site.feesrazorpay.getPayPrintSlipInvoiceRazorpay", ['paydetails' => $paydetails]);

				die;
			}
		}
	}











	public function feesPayGatewaySave(Request $request)
	{


		if ($request->ajax()) {
			if ($request->input('order_id')) {
				$data = $this->dataDecodeJsonBase64($_POST['o']);

				$paymentHistory = new CcavenueHistory;
				$paymentHistory->name = $data->name;
				$paymentHistory->email = $data->email;
				$paymentHistory->mobile = $data->phone;
				$paymentHistory->course = $data->course;
				$paymentHistory->amount = $data->amt;
				$paymentHistory->billing_city = $data->city;
				$paymentHistory->country = $data->country;
				$paymentHistory->billing_state = $data->state;
				$paymentHistory->payment_mode = $request->input('mode');
				$paymentHistory->order_id = $request->input('order_id');

				if ($paymentHistory->save()) {




					$headers = 'MIME-Version: 1.0' . "\r\n";
					$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
					//	$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					// Additional headers
					//	$headers .= 'From: enquiry@quickdials.in' . "\r\n";
					$headers .= 'From: quickdials <enquiry@quickdials.in>';

					$to = "brijesh.chauhan@quickdials.in";
					$subject = "Payment- " . $data->name . " | " . $data->course . " | " . $request->input('mode') . " | " . $data->amt . " Amount";

					$message = ' <tr>
						<td style="padding:0in 0in 7.5pt 0in">
						<p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Name:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">
						' . $data->name . '</span><u></u><u></u></p>
						</td>
						</tr>
						<tr>
						<td style="padding:0in 0in 7.5pt 0in">
						<p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Email:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">
						' . $data->email . '</span><u></u><u></u></p>
						</td>
						</tr>
						<tr>
						<td style="padding:0in 0in 7.5pt 0in">
						<p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Technology:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">
						' . $data->course . ' </span><u></u><u></u></p>
						</td>
						</tr>
						<tr>
						<td style="padding:0in 0in 7.5pt 0in">
						<p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Mobile:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">' . $data->phone . '</span><u></u><u></u></p>
						</td>
						</tr>	<tr>
						<td style="padding:0in 0in 7.5pt 0in">
						<p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Amount: </span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333"> ' . $data->amt . '</span><u></u><u></u></p>
						</td>
						</tr>		

						<tr>
						<td style="padding:0in 0in 7.5pt 0in">
						<p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">City:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333"> ' . $data->city . '</span><u></u><u></u></p>
						</td>
						</tr>
						<tr>
						<td style="padding:0in 0in 7.5pt 0in">
						<p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Country :</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333"> ' . $data->country . '</span><u></u><u></u></p>
						</td>
						</tr>
						<tr>
						<td style="padding:0in 0in 7.5pt 0in">
						<p class="MsoNormal"><strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333">Mode:</span></strong><span style="font-size:10.5pt;font-family:&quot;Tahoma&quot;,&quot;sans-serif&quot;;color:#333333"> ' . $request->input('mode') . '</span><u></u><u></u></p>
						</td>
						</tr>
						 
						';



					$stdemail = "";
					$codemail = "";
					$coordinator = "";


					Mail::send('mails.send_payment_inquiry', ['msg' => $message], function ($m) use ($message, $request, $subject, $stdemail, $codemail, $data) {
						$m->from('info@quickdials.com', $data->name);
						if ($request->file('photoimg')) {
							$m->attach($request->file('photoimg')->getRealPath(), [
								'as' => $request->file('photoimg')->getClientOriginalName(),
								'mime' => $request->file('photoimg')->getMimeType()
							]);
						}
						$m->to('quickdials1@gmail.com', "")->subject($subject)->cc($data->email);
					});




					$arr['status'] = 1;
					$arr['msg'] = "Successfully submit ";
					$arr['oo'] = $_POST['o'];

				} else {
					$arr['status'] = 0;
					$arr['msg'] = "Not Successfully payment";


				}
			} else {
				$arr['status'] = 1;
				$arr['msg'] = "Not Update Photo Successfully ";

			}

			echo json_encode($arr);


		}



	}



	public function airpay(Request $request)
	{
		date_default_timezone_set('Asia/Kolkata');
		header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Cache-Control: no-store, no-cache, must-revalidate');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache');


		$buyerEmail = "test@gmail.com";//trim($_POST['buyerEmail']);
		$buyerPhone = '8457425742';//trim($_POST['buyerPhone']);
		$buyerFirstName = "First Name";//trim($_POST['buyerFirstName']);
		$buyerLastName = "last Name";//trim($_POST['buyerLastName']);
		$buyerAddress = "Address";//trim($_POST['buyerAddress']);
		$amount = "1.00";//trim($_POST['amount']);
		$buyerCity = "Noida";//trim($_POST['buyerCity']);
		$buyerState = "UP";//trim($_POST['buyerState']);
		$buyerPinCode = 852574;//trim($_POST['buyerPinCode']);
		$buyerCountry = "India";//trim($_POST['buyerCountry']);
		$orderid = mt_rand(1000, 9999); //trim($_POST['orderid']); //Your System Generated Order ID
		// $hiddenmod = trim($_POST['directindexvar']);
		$currency = 356;//trim($_POST['currency']);
		$isocurrency = "INR";//trim($_POST['isocurrency']);

		//$this->config();
		$username = '9563650'; // Username
		$password = 'WeZ4CrwA'; // Password
		$secret = 'U6eQEwZ3b9FNtdj3'; // API key
		$mercid = '274181'; //Merchant ID 

		$this->airpayvalidation($buyerEmail, $buyerPhone, $buyerFirstName, $buyerLastName, $buyerAddress, $amount, $buyerCity, $buyerState, $buyerPinCode, $buyerCountry, $orderid, $currency, $isocurrency);
		//include('site/feesrazorpay/config.php');
		//include('site/feesrazorpay/checksum.php');
		//include('site/feesrazorpay/validation.php');


		// $date = date('Y-m-d');
		// $alldata   = $buyerEmail.$buyerFirstName.$buyerLastName.$buyerAddress.$buyerCity.$buyerState.$buyerCountry.$amount.$orderid.$hiddenmod;
		// $privatekey = Checksum::encrypt($username.":|:".$password, $secret);
		// $keySha256 = Checksum::encryptSha256($username."~:~".$password);
		// $checksum = Checksum::calculateChecksum($alldata,$keySha256);

		$alldata = $buyerEmail . $buyerFirstName . $buyerLastName . $buyerAddress . $buyerCity . $buyerState . $buyerCountry . $amount . $orderid;
		// 	$privatekey = Checksum::encrypt($username.":|:".$password, $secret);
		//     $keySha256 = Checksum::encryptSha256($username."~:~".$password);
		//     $checksum = Checksum::calculateChecksumSha256($alldata.date('Y-m-d'),$keySha256);

		$privatekey = $this->encrypt($username . ":|:" . $password, $secret);
		$keySha256 = $this->encryptSha256($username . "~:~" . $password);
		$checksum = $this->calculateChecksumSha256($alldata . date('Y-m-d'), $keySha256);

		// Session::put('alldata',$alldata);
		// Session::put('keySha256',$keySha256);
		Session::put('checksum', $checksum);


		$hiddenmod = "";
		$this->processform($checksum);
	}




	public function config()
	{
		$username = '9563650'; // Username
		$password = 'WeZ4CrwA'; // Password
		$secret = 'U6eQEwZ3b9FNtdj3'; // API key
		$mercid = '274181'; //Merchant ID   
	}

	//for checksom
	public function calculateChecksum($data, $secret_key)
	{
		$checksum = md5($data . $secret_key);
		return $checksum;
	}

	public function encrypt($data, $salt)
	{
		// Build a 256-bit $key which is a SHA256 hash of $salt and $password.
		$key = hash('SHA256', $salt . '@' . $data);
		return $key;
	}

	public function encryptSha256($data)
	{
		$key = hash('SHA256', $data);
		return $key;
	}
	public function calculateChecksumSha256($data, $salt)
	{
		$checksum = hash('SHA256', $salt . '@' . $data);
		return $checksum;
	}


	public function outputForm($checksum)
	{
		//ksort($_POST);

		foreach ($_POST as $key => $value) {
			echo '<input type="hidden" name="' . $key . '" value="' . $value . '" />' . "\n";
		}
		echo '<input type="hidden" name="checksum" value="' . $checksum . '" />' . "\n";
	}

	public function verifyChecksum($checksum, $all, $secret)
	{
		$cal_checksum = Checksum::calculateChecksum($secret, $all);
		$bool = 0;
		if ($checksum == $cal_checksum) {
			$bool = 1;
		}

		return $bool;
	}

	public function subscribeFree(Request $request)
	{

		if (isset($_GET['status'], $_GET['o']) && !empty($_GET['o'])) {
			$oo = base64_decode($_GET['o'], $strict = false);
			$data = json_decode($oo);
			$status = $_GET['status'];
		} else {
			$data = array();
		}


		return view('business.razorpay.subscribe-free', ['data' => $data, 'oo' => $_GET['o']]);

	}


	/**
	 * @OA\Post(
	 *     path="/api/business/razorpay/success",
	 *     tags={"Razorpay"},
	 *     summary="Save Razorpay payment success",
	 *     description="Save Razorpay payment success details after successful transaction.",
	 *     security={{"bearerAuth":{}}},
	 *
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={
	 *                 "business_id",
	 *                 "razorpay_order_id",
	 *                 "transaction_id",
	 *                 "paid_amount",
	 *                 "status",
	 *                 "customer_name",
	 *                 "email",
	 *                 "mobile"
	 *             },
	 *
	 *             @OA\Property(property="business_id", type="integer", example=12),
	 *             @OA\Property(property="razorpay_order_id", type="string", example="order_ABC123"),
	 *             @OA\Property(property="transaction_id", type="string", example="pay_XYZ123"),
	 *             @OA\Property(property="paid_amount", type="number", example=299.00),
	 *             @OA\Property(property="status", type="string", example="success"),
	 *             @OA\Property(property="customer_name", type="string", example="Rahul Sharma"),
	 *             @OA\Property(property="email", type="string", example="rahul@gmail.com"),
	 *             @OA\Property(property="mobile", type="string", example="9876543210"),
	 *
	 *             @OA\Property(property="coins", type="integer", example=1111),
	 *             @OA\Property(property="payment_mode", type="string", example="Card")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Payment saved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Payment saved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="object",
	 *                 @OA\Property(property="payment_id", type="string", example="pay_XYZ123"),
	 *                 @OA\Property(property="order_id", type="string", example="order_ABC123"),
	 *                 @OA\Property(property="paid_amount", type="number", example=299.00),
	 *                 @OA\Property(property="currency", type="string", example="INR"),
	 *                 @OA\Property(property="status", type="string", example="success"),
	 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-04T12:45:00Z")
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Unauthenticated")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation failed",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Validation failed"),
	 *             @OA\Property(property="errors", type="object")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=500,
	 *         description="Server error",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Failed to save payment")
	 *         )
	 *     )
	 * )
	 */



	public function saveSuccess(Request $request)
	{

		// ✅ Validate input
		$request->validate([
			'business_id' => 'required',
			'razorpay_order_id' => 'required|string',
			'customer_name' => 'required|string',
			'transaction_id' => 'required|string',
			'paid_amount' => 'required|numeric',
			'status' => 'required',
			'email' => 'required|email',
			'mobile' => 'required|string',
			'payment_mode' => 'nullable|string',
		]);

		$client = Client::find($request->business_id);
		if (!$client) {
			return response()->json([
				'status' => false,
				'msg' => 'Client not found'
			], 404);
		}

		$payment = PaymentHistory::where('order_number', $request->razorpay_order_id)->first();
		if (!$payment) {
			return response()->json([
				'status' => false,
				'msg' => 'Payment history not found'
			], 404);
		}

		DB::beginTransaction();

		try {
			if ($request->paid_amount > 0) {
				// ✅ Update payment status
				$payment->update([
					'customer_name' => $request->customer_name,
					'business_name' => $client->business_name,
					'mobile' => $request->mobile,
					'email' => $request->email,
					'paid_amount' => $request->paid_amount,
					'transactionid' => $request->transaction_id,
					'payment_mode' => $request->payment_mode,
					'invoice_status' => '1',
					'status' => $request->status,
				]);
			}
			if ($request->status !== 'success') {
				DB::commit();
				return response()->json([
					'status' => true,
					'msg' => 'Payment status updated'
				], 200);
			}


			$client->coins_amt += (int) $payment->coins_amt;


			$today = Carbon::today();

			if (!$client->expired_on || Carbon::parse($client->expired_on)->lt($today)) {
				$client->expired_on = $today->copy()->addDays(365);
			} else {
				$client->expired_on = Carbon::parse($client->expired_on)->addDays(365);
			}

			// ✅ Activate account
			$client->active_status = '1';
			$client->paid_status = '1';
			$client->save();

			DB::commit();

			return response()->json([
				'status' => true,
				'msg' => 'Payment successful'
			], 200);

		} catch (\Exception $e) {
			DB::rollBack();

			return response()->json([
				'status' => false,
				'msg' => 'Payment processing failed',
				'error' => $e->getMessage()
			], 500);
		}

	}


	/**
	 * @OA\Post(
	 *     path="/api/business/razorpay/subscribe-free",
	 *     summary="subscribe free",
	 *     description="subscribe free for customized Razorpay payment",
	 *     
	 *     tags={"Razorpay"},
	 *	   security={{"bearerAuth":{}}},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={"encrypt"},
	 *             @OA\Property(
	 *                 property="encrypt",
	 *                 type="string",
	 *                 example="U2FsdGVkX19kXzEyMzQ1Ng==",
	 *                 description="Encrypted payment payload"
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Payment initiated successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Payment initiated successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="object",
	 *                 @OA\Property(property="payment_id", type="string", example="pay_NXJH123456"),
	 *                 @OA\Property(property="order_id", type="string", example="ORD123456789"),
	 *                 @OA\Property(property="amount", type="number", example=100.50),
	 *                 @OA\Property(property="currency", type="string", example="INR")
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation failed",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Validation failed"),
	 *             @OA\Property(property="errors", type="object")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=500,
	 *         description="Server error",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Failed to initiate payment")
	 *         )
	 *     )
	 * )
	 */


	public function saveSubscribeFree(Request $request)
	{

		// dd($request->encrypt);
		// ✅ Validate request
		$request->validate([
			'encrypt' => 'required|string'
		]);

		try {
			$data = $this->dataDecodeJsonBase64($request->encrypt);

		} catch (\Exception $e) {
			return response()->json([
				'status' => false,
				'msg' => 'Invalid encrypted data'
			], 422);
		}

		// ✅ Client fetch
		$client = Client::find($data->client_id);
		if (!$client) {
			return response()->json([
				'status' => false,
				'msg' => 'Client not found'
			], 404);
		}

		//  Already used free plan
		if ($client->coins_free == '1') {
			return response()->json([
				'status' => false,
				'msg' => 'Already subscribed!'
			], 200);
		}

		DB::beginTransaction();

		try {

			// ✅ Save payment history
			PaymentHistory::create([
				'client_id' => $client->id,
				'customer_name' => $data->customer_name,
				'business_name' => $client->business_name,
				'mobile' => $data->phone,
				'email' => $data->email,
				'package_name' => $client->client_type,
				'coins_amt' => $data->coins,
				'paid_amount' => 0,
				'tds_status' => 'No',
				'tds_amount' => 0,
				'gst_tax' => 0,
				'gst_total_amount' => 0,
				'gst_status' => 'Yes',
				'total_amount' => 0,
				'transactionid' => 'FREE-' . time(),
				'order_number' => 'FREE-' . time(),
				'paymentcollect' => 0,
				'payment_mode' => 'free subscribe',
				'invoice_status' => 1,
			]);

			// ✅ Coins update
			$client->coins_amt += $data->coins;

			// ✅ Expiry logic (clean)
			$today = Carbon::today();

			if (!$client->expired_on || Carbon::parse($client->expired_on)->lt($today)) {
				$client->expired_on = $today->addDays(365);
			} else {
				$client->expired_on = Carbon::parse($client->expired_on)->addDays(365);
			}

			// ✅ Status update
			$client->active_status = '1';
			$client->paid_status = '1';
			$client->coins_free = '1';
			$client->save();

			DB::commit();

			return response()->json([
				'status' => true,
				'msg' => 'Free subscribed successfully'
			], 200);

		} catch (\Exception $e) {
			DB::rollBack();

			return response()->json([
				'status' => false,
				'msg' => 'Subscription failed',
				'error' => $e->getMessage()
			], 500);
		}
	}



}
