<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddLeadRequest;
use DB;
use Mail;
use Artisan;
use Validator;
//model
use App\Models\Keyword;

use App\Models\Citieslists;
use App\Models\Lead;
use App\Models\ChildCategory;
use App\Models\ParentCategory;
use App\Models\ClientCategory;
use App\Models\Client\Client;
use App\AssignedClientCategory;
use App\Models\Blogdetails;
use App\Models\Testimonialsdetail;
use App\Models\LeadFollowUp;
use App\Models\Status;
use App\Models\Contacts;
use App\Models\Zone;
use App\Models\Client\Comment;
class ContactController extends Controller
{

	/**
	 * @OA\Post(
	 *     path="/api/site/saveEnquiry",
	 *     tags={"Frontend Save Enquiry"},
	 *     summary="Submit enquiry",
	 *     description="Creates a new enquiry. Duplicate enquiries with same mobile and keyword within last 4 days are not allowed.",
	 *
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             type="object",
	 *             required={"name","email","mobile","kw_text"},
	 *             @OA\Property(
	 *                 property="name",
	 *                 type="string",
	 *                 example="Rahul Sharma"
	 *             ),
	 *             @OA\Property(
	 *                 property="email",
	 *                 type="string",
	 *                 format="email",
	 *                 example="rahul@gmail.com"
	 *             ),
	 *             @OA\Property(
	 *                 property="country_code",
	 *                 type="string",
	 *                 example="91"
	 *             ),
	 *             @OA\Property(
	 *                 property="mobile",
	 *                 type="string",
	 *                 example="9876543210"
	 *             ),
	 *             @OA\Property(
	 *                 property="kw_text",
	 *                 type="string",
	 *                 example="Physics Tuition"
	 *             ),
	 *             @OA\Property(
	 *                 property="city",
	 *                 type="integer",
	 *                 nullable=true,
	 *                 example="hyderabad",
	 *                 description="City name"
	 *             ),
	 *             @OA\Property(
	 *                 property="zone",
	 *                 type="integer",
	 *                 nullable=true,
	 *                 example="1090",
	 *                 description="Zone id"
	 *             ),
	 *             @OA\Property(
	 *                 property="lead_form",
	 *                 type="string",
	 *                 nullable=true,
	 *                 example="1"
	 *             ),
	 *             @OA\Property(
	 *                 property="from_page",
	 *                 type="string",
	 *                 nullable=true,
	 *                 example="web-design"
	 *             ),
	 *             @OA\Property(
	 *                 property="appointment",
	 *                 type="string",
	 *                 nullable=true,
	 *                 example="web-design"
	 *             ),
	 *             @OA\Property(
	 *                 property="frmcheck",
	 *                 type="string",               
	 *                 example="multiple check"
	 *             ),
	 *             @OA\Property(
	 *                 property="remark",
	 *                 type="string",
	 *                 nullable=true,
	 *                 example="Customer wants urgent callback"
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Enquiry created successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="status", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Enquiry created successfully")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation error"
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=429,
	 *         description="Duplicate enquiry"
	 *     )
	 * )
	 */


	public function saveEnquiry(Request $request)
	{
		// ✅ Validation
		$validator = Validator::make($request->all(), [
			'name' => 'required|string|min:3|max:32',
			'email' => 'required|email',
			'mobile' => 'required|digits_between:10,16',
			'kw_text' => 'required|string',
		]);

		if ($validator->fails()) {
			return response()->json([
				'status' => false,
				'errors' => $validator->errors()
			], 422);
		}

		// ✅ Clean name
		$name = preg_replace(
			'/\s+/',
			' ',
			preg_replace('/[^A-Za-z0-9 ]/', '', trim($request->name))
		);

		// ✅ Clean mobile
		$mobile = preg_replace('/\s+/', '', ltrim($request->mobile, '0'));

		// ✅ Duplicate check (last 4 days)
		$duplicate = Lead::where('mobile', $mobile)
			->where('kw_text', $request->kw_text)
			->whereDate('created_at', '>=', now()->subDays(4))
			->exists();

		// if ($duplicate) {
		// 	return response()->json([
		// 	'status' => true,
		// 	'message' => 'Enquiry created successfully'
		// ], 200);
		// }

		// ✅ City
		$cityname = ucwords(str_replace("-", " ", $request->city));
		$city = Citieslists::where('city', $cityname)->first();

		// ✅ Keyword
		$keyword = Keyword::where('keyword', $request->kw_text)->first();

		// ✅ Status
		$status = Status::where('name', 'New Lead')->first();

		// ✅ Save Lead
		$lead = new Lead();
		$lead->name = $name;
		$lead->email = $request->email;
		$lead->mobile = $mobile;
		$lead->kw_id = $keyword?->id ?? 0;
		$lead->kw_text = $keyword?->keyword ?? $request->kw_text;
		$lead->city_id = $city?->id;
		$lead->city_name = $city?->city ?? 'none' ?? 'none';
		$lead->lead_form = $request->lead_form;
		$lead->from_page = $request->from_page;
		$lead->remark = $request->remark;
		$lead->status_id = $status->id;
		$lead->status_name = $status->name;
		$lead->b_end = '0';
		$lead->terms = '1';

		if($request->zone){
			 
				$zone = Zone::where('id',$request->zone)->first();
				$lead->zone_id = $zone->id;
				$lead->zone = $zone->zone;
			}else{
				if(!empty($city->id)){
				$zone = Zone::where('city_id',$city->id)->first();
				$lead->zone_id = $zone->id;
				$lead->zone = $zone->zone;
				}
			}
 
		$lead->save();

		// ✅ Follow-up
		LeadFollowUp::create([
			'lead_id' => $lead->id,
			'status' => $status->id,
			'remark' => $request->remark,
		]);
			
		 
		if (!$duplicate) {
			// leadassignWithoutZoneCounsellor($lead);
		}
		return response()->json([
			'status' => true,
			'message' => 'Enquiry created successfully'
		], 200);
	}


	public function saveEnquiryWithoutZone(Request $request)
	{
		if ($request->ajax()) {

			$validator = Validator::make($request->all(), [
				'name' => 'required|regex:/^[\pL\s\-]+$/u|min:3|max:32',
				'email' => 'required|regex:/^[^\s()<>@,;:\/]+@\w[\w\.-]+\.[a-z]{2,}$/i',
				'mobile' => 'required|numeric',
				//	'phone' 	=> 'required|regex:/^[\+]?[(]?[0-9]{3}[)]?[-\s\.]?[0-9]{3}[-\s\.]?[0-9]{4,6}$/im',				
				'kw_text' => 'required',


			]);

			if ($validator->fails()) {
				$errorsBag = $validator->getMessageBag()->toArray();

				return response()->json(['status' => 1, 'errors' => $errorsBag], 400);
			}

			$lead = new Lead;
			$string = filter_var($request->input('name'), FILTER_SANITIZE_STRING);
			$string = preg_replace('/[^A-Za-z0-9]/', ' ', $string);
			$name = preg_replace('/\s+/', ' ', str_replace('&', '', trim($string)));
			$lead->name = $name;
			$lead->email = $request->input('email');
			$lead->lead_form = $request->input('lead_form');
			$lead->from_page = filter_var($request->input('from_page'), FILTER_SANITIZE_STRING);
			$cityname = ucwords(str_replace("-", " ", $request->input('city_id')));
			$city = Citieslists::where('city', ucwords(str_replace("-", " ", $request->input('city_id'))))->first();

			if (!empty($city->id)) {
				$lead->city_id = $city->id;
				$lead->city_name = $city->city;
			} else {
				if ($cityname) {
					$lead->city_name = $cityname;
				} else {
					$lead->city_name = 'none';
				}
			}
			if ($request->has('b_end')) {
				$lead->b_end = $request->input('b_end');
			}

			$mobile = ltrim($request->input('mobile'), '0');
			$mobile = trim($mobile);
			$newmobile = preg_replace('/\s+/', '', $mobile);
			$lead->mobile = $newmobile;
			$kw_text = filter_var($request->input('kw_text'), FILTER_SANITIZE_STRING);
			$kw_text = preg_replace('/[^A-Za-z0-9]/', ' ', $kw_text);
			$kw_text = preg_replace('/\s+/', ' ', str_replace('&', '', trim($kw_text)));
			$keyword = Keyword::where('keyword', $kw_text)->first();

			if (!empty($keyword)) {
				$lead->kw_id = $keyword->id;
				$lead->kw_text = $keyword->keyword;
				$course_name = $keyword->keyword;
			} else {
				$lead->kw_id = 0;
				$lead->kw_text = $request->input('kw_text');
				$course_name = $request->input('kw_text');
			}


			$lead->status_id = Status::where('name', 'LIKE', 'New Lead')->first()->id;
			$lead->status_name = Status::where('name', 'LIKE', 'New Lead')->first()->name;
			$lead->remark = $request->input('remark');
			$lead->created_by = 101;



			$today = date('Y-m-d');
			$checklead = Lead::where('mobile', $newmobile)->where('kw_text', $request->input('kw_text'))->where('city_name', $cityname)->whereDate('created_at', '=', date_format(date_create($today), 'Y-m-d'))->get()->count();

			$currentdate = date('Y-m-d');
			$lastDate = date('Y-m-d', strtotime($currentdate . '- 4 day'));

			$checkday = Lead::where('mobile', $newmobile)->where('kw_text', $request->input('kw_text'))->whereDate('created_at', '>', date_format(date_create($lastDate), 'Y-m-d'))->get()->count();

			if (!empty($checklead) && $checklead > 0) {
				return response()->json([
					'statusCode' => 1,
					'response' => [
						'responseCode' => 200,
						'payload' => '',
						'message' => 'Follow Up created successfully'
					]
				], 200);
			} else if (!empty($checkday) && $checkday > 0) {
				$lead->duplicate = '1';
				if ($lead->save()) {

					$followUp = new LeadFollowUp;
					$followUp->status = Status::where('name', 'LIKE', 'New Lead')->first()->id;
					$followUp->remark = $request->input('remark');
					//	$followUp->expected_date_time = date('Y-m-d H:i:s');
					$followUp->lead_id = $lead->id;
					//$followUp->remark_by =Auth::user()->id;
					$followUp->save();

					//leadassignWithoutZoneCounsellor($lead);

					return response()->json([
						'statusCode' => 1,
						'response' => [
							'responseCode' => 200,
							'payload' => '',
							'message' => 'Follow Up created successfully'
						]
					], 200);
				} else {
					return response()->json([
						'statusCode' => 1,
						'response' => [
							'responseCode' => 200,
							'payload' => '',
							'message' => 'Some Error Follow up'
						]
					], 200);

				}
			} else {

				if ($lead->save()) {

					$followUp = new LeadFollowUp;
					$followUp->status = Status::where('name', 'LIKE', 'New Lead')->first()->id;
					$followUp->remark = $request->input('remark');
					//	$followUp->expected_date_time = date('Y-m-d H:i:s');
					$followUp->lead_id = $lead->id;
					//$followUp->remark_by =Auth::user()->id;
					$followUp->save();

					leadassignWithoutZoneCounsellor($lead);

					return response()->json([
						'statusCode' => 1,
						'response' => [
							'responseCode' => 200,
							'payload' => '',
							'message' => 'Follow Up created successfully'
						]
					], 200);
				} else {
					return response()->json([
						'statusCode' => 1,
						'response' => [
							'responseCode' => 200,
							'payload' => '',
							'message' => 'Some Error Follow up'
						]
					], 200);
				}
			}
		}
	}

	public function saveEnquiryContact(Request $request)
	{

		if ($request->ajax()) {

			$validator = Validator::make($request->all(), [
				'name' => 'required|regex:/^[\pL\s\-]+$/u|min:3|max:32',
				'email' => 'required|regex:/^[^\s()<>@,;:\/]+@\w[\w\.-]+\.[a-z]{2,}$/i',
				'mobile' => 'required|numeric',
				'subject' => 'required',


			]);

			if ($validator->fails()) {
				$errorsBag = $validator->getMessageBag()->toArray();

				return response()->json(['status' => 1, 'errors' => $errorsBag], 400);
			}

			$lead = new Contacts;
			$string = filter_var($request->input('name'), FILTER_SANITIZE_STRING);
			$string = preg_replace('/[^A-Za-z0-9]/', ' ', $string);
			$name = preg_replace('/\s+/', ' ', str_replace('&', '', trim($string)));
			$lead->name = $name;
			$lead->email = $request->input('email');
			$lead->mobile = $request->input('mobile');
			$lead->subject = filter_var($request->input('subject'), FILTER_SANITIZE_STRING);

			$message = filter_var($request->input('message'), FILTER_SANITIZE_STRING);
			$message = preg_replace('/[^A-Za-z0-9]/', ' ', $message);
			$message = preg_replace('/\s+/', ' ', str_replace('&', '', trim($message)));
			$lead->message = $message;


			if ($lead->save()) {

				return response()->json([
					'statusCode' => 1,
					'response' => [
						'responseCode' => 200,
						'payload' => '',
						'message' => 'Form submited successfully'
					]
				], 200);
			} else {
				return response()->json([
					'statusCode' => 1,
					'response' => [
						'responseCode' => 200,
						'payload' => '',
						'message' => 'Some Error Follow up'
					]
				], 200);

			}
		}
	}

	public function saveTwoEnquiry(Request $request)
	{
		if ($request->ajax()) {

			$validator = Validator::make($request->all(), [
				'name' => 'required|regex:/^[\pL\s\-]+$/u|min:3|max:32',
				'mobile' => 'required|numeric',
				'kw_text' => 'required',
			]);

			if ($validator->fails()) {
				$errorsBag = $validator->getMessageBag()->toArray();

				return response()->json(['status' => 1, 'errors' => $errorsBag], 400);
			}

			$lead = new Lead;
			$string = filter_var($request->input('name'), FILTER_SANITIZE_STRING);
			$string = preg_replace('/[^A-Za-z0-9]/', ' ', $string);
			$name = preg_replace('/\s+/', ' ', str_replace('&', '', trim($string)));
			$lead->name = $name;
			$lead->email = $request->input('email');
			$lead->lead_form = $request->input('lead_form');
			$lead->from_page = filter_var($request->input('from_page'), FILTER_SANITIZE_STRING);
			$cityname = ucwords(str_replace("-", " ", $request->input('city_id')));
			$city = Citieslists::where('city', ucwords(str_replace("-", " ", $request->input('city_id'))))->first();

			if (!empty($city->id)) {
				$lead->city_id = $city->id;
				$lead->city_name = $city->city;
			} else {
				if ($cityname) {
					$lead->city_name = $cityname;
				} else {
					$lead->city_name = 'none';
				}
			}
			if ($request->has('b_end')) {
				$lead->b_end = $request->input('b_end');
			}

			$mobile = ltrim($request->input('mobile'), '0');
			$mobile = trim($mobile);
			$newmobile = preg_replace('/\s+/', '', $mobile);
			$lead->mobile = $newmobile;
			$kw_text = filter_var($request->input('kw_text'), FILTER_SANITIZE_STRING);
			$kw_text = preg_replace('/[^A-Za-z0-9]/', ' ', $kw_text);
			$kw_text = preg_replace('/\s+/', ' ', str_replace('&', '', trim($kw_text)));
			$keyword = Keyword::where('keyword', $kw_text)->first();

			if (!empty($keyword)) {
				$lead->kw_id = $keyword->id;
				$lead->kw_text = $keyword->keyword;

			} else {
				$lead->kw_id = 0;
				$lead->kw_text = $request->input('kw_text');
			}


			$lead->status_id = Status::where('name', 'LIKE', 'New Lead')->first()->id;
			$lead->status_name = Status::where('name', 'LIKE', 'New Lead')->first()->name;
			$lead->remark = $request->input('remark');
			$lead->created_by = 101;



			$today = date('Y-m-d');
			$checklead = Lead::where('mobile', $newmobile)->where('kw_text', $request->input('kw_text'))->where('city_name', $cityname)->whereDate('created_at', '=', date_format(date_create($today), 'Y-m-d'))->get()->count();
			//echo "<pre>";print_r($checklead);die;
			$currentdate = date('Y-m-d');
			$lastDate = date('Y-m-d', strtotime($currentdate . '- 4 day'));

			$checkday = Lead::where('mobile', $newmobile)->where('kw_text', $request->input('kw_text'))->whereDate('created_at', '>', date_format(date_create($lastDate), 'Y-m-d'))->get()->count();

			if (!empty($checklead) && $checklead > 0) {
				return response()->json([
					'statusCode' => 1,
					'response' => [
						'responseCode' => 200,
						'payload' => '',
						'message' => 'Follow Up created successfully'
					]
				], 200);
			} else if (!empty($checkday) && $checkday > 0) {
				$lead->duplicate = '1';
				if ($lead->save()) {

					$followUp = new LeadFollowUp;
					$followUp->status = Status::where('name', 'LIKE', 'New Lead')->first()->id;
					$followUp->remark = $request->input('remark');
					//	$followUp->expected_date_time = date('Y-m-d H:i:s');
					$followUp->lead_id = $lead->id;
					//$followUp->remark_by =Auth::user()->id;
					$followUp->save();

					//leadassignWithoutZoneCounsellor($lead);

					return response()->json([
						'statusCode' => 1,
						'response' => [
							'responseCode' => 200,
							'payload' => '',
							'message' => 'Follow Up created successfully'
						]
					], 200);
				} else {
					return response()->json([
						'statusCode' => 1,
						'response' => [
							'responseCode' => 200,
							'payload' => '',
							'message' => 'Some Error Follow up'
						]
					], 200);

				}
			} else {

				if ($lead->save()) {

					$followUp = new LeadFollowUp;
					$followUp->status = Status::where('name', 'LIKE', 'New Lead')->first()->id;
					$followUp->remark = $request->input('remark');
					//	$followUp->expected_date_time = date('Y-m-d H:i:s');
					$followUp->lead_id = $lead->id;
					//$followUp->remark_by =Auth::user()->id;
					$followUp->save();

					leadassignWithoutZoneCounsellor($lead);

					return response()->json([
						'statusCode' => 1,
						'response' => [
							'responseCode' => 200,
							'payload' => '',
							'message' => 'Follow Up created successfully'
						]
					], 200);
				} else {
					return response()->json([
						'statusCode' => 1,
						'response' => [
							'responseCode' => 200,
							'payload' => '',
							'message' => 'Some Error Follow up'
						]
					], 200);
				}
			}
		}
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function autoFormSave(Request $request)
	{
		$cityname = ucwords(str_replace("-", " ", $request->input('city_id')));
		$city = Citieslists::where('city', 'LIKE', ucwords(str_replace("-", " ", $request->input('city_id'))))->first();
		$lead = new Lead;
		if (!empty($city->id)) {
			$lead->city_id = $city->id;
			$lead->city_name = $city->city;
		} else {
			if ($cityname) {
				$lead->city_name = $cityname;
			} else {
				$lead->city_name = 'none';
			}
		}
		$string = filter_var($request->input('name'), FILTER_SANITIZE_STRING);
		$string = preg_replace('/[^A-Za-z0-9]/', ' ', $string);
		$name = preg_replace('/\s+/', ' ', str_replace('&', '', trim($string)));
		$lead->name = $name;

		if ($request->input('email') != '') {

			$lead->email = $request->input('email');
		}
		$mobile = ltrim($request->input('mobile'), '0');
		$mobile = trim($mobile);
		$newmobile = preg_replace('/\s+/', '', $mobile);
		$lead->mobile = $newmobile;
		$lead->lead_form = $request->input('lead_form');
		$lead->from_page = filter_var($request->input('from_page'), FILTER_SANITIZE_STRING);
		$keyword = Keyword::where('keyword', 'LIKE', $request->input('kw_text'))->get();
		if (!empty($keyword)) {
			$lead->kw_id = $keyword[0]->id;
			$lead->kw_text = $keyword[0]->keyword;
			$bucketIndex = $keyword[0]->bucket;
		} else {
			return response()->json(['status' => 1, 'msg' => 'Keyword not found'], 404);
		}
		if ($request->has('b_end')) {
			$lead->b_end = $request->input('b_end');
		}
		$lead->status_id = Status::where('name', 'LIKE', 'New Lead')->first()->id;
		$lead->status_name = Status::where('name', 'LIKE', 'New Lead')->first()->name;
		$lead->remark = $request->input('remark');
		$lead->created_by = '1';

		$today = date('Y-m-d');
		$checklead = Lead::where('mobile', $newmobile)->where('kw_text', $request->input('kw_text'))->where('city_name', $cityname)->whereDate('created_at', '=', date_format(date_create($today), 'Y-m-d'))->get()->count();

		$currentdate = date('Y-m-d');
		$lastDate = date('Y-m-d', strtotime($currentdate . '- 4 day'));

		$checkday = Lead::where('mobile', $newmobile)->where('kw_text', $request->input('kw_text'))->whereDate('created_at', '>', date_format(date_create($lastDate), 'Y-m-d'))->get()->count();

		if (!empty($checklead) && $checklead > 0) {
			return response()->json(['status' => 1, 'msg' => 'Lead added successfully'], 200);
		} else if (!empty($checkday) && $checkday > 0) {
			$lead->duplicate = '1';
			if ($lead->save()) {

				$followUp = new LeadFollowUp;
				$followUp->status = Status::where('name', 'LIKE', 'New Lead')->first()->id;
				$followUp->remark = $request->input('remark');

				$followUp->lead_id = $lead->id;

				$followUp->save();

				return response()->json(['status' => 1, 'msg' => 'Lead added successfully'], 200);
			}
		} else {

			if ($lead->save()) {

				$followUp = new LeadFollowUp;
				$followUp->status = Status::where('name', 'LIKE', 'New Lead')->first()->id;
				$followUp->remark = $request->input('remark');
				//	$followUp->expected_date_time = date('Y-m-d H:i:s');
				$followUp->lead_id = $lead->id;
				//$followUp->remark_by =Auth::user()->id;
				$followUp->save();

				leadassignWithoutZoneCounsellor($lead);
				return response()->json(['status' => 1, 'msg' => 'Lead added successfully'], 200);
			}
		}



	}
	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{
		if ($request->ajax()) {
			$cityname = ucwords(str_replace("-", " ", $request->input('city_id')));
			$city = Citieslists::where('city', 'LIKE', ucwords(str_replace("-", " ", $request->input('city_id'))))->first();
			$lead = new Lead;
			if (!empty($city->id)) {
				$lead->city_id = $city->id;
				$lead->city_name = $city->city;
			} else {
				if ($cityname) {
					$lead->city_name = $cityname;
				} else {
					$lead->city_name = 'none';
				}
			}
			$string = filter_var($request->input('name'), FILTER_SANITIZE_STRING);
			$string = preg_replace('/[^A-Za-z0-9]/', ' ', $string);
			$name = preg_replace('/\s+/', ' ', str_replace('&', '', trim($string)));
			$lead->name = $name;
			if ($request->input('email') != '') {

				$lead->email = $request->input('email');
			}
			$lead->mobile = $request->input('mobile');
			$lead->lead_form = $request->input('lead_form');
			$keyword = Keyword::where('keyword', 'LIKE', $request->input('kw_text'))->get();
			if (!empty($keyword)) {
				$lead->kw_id = $keyword[0]->id;
				$lead->kw_text = $keyword[0]->keyword;
				$bucketIndex = $keyword[0]->bucket;
			} else {
				return response()->json(['status' => 1, 'msg' => 'Keyword not found'], 404);
			}
			if ($request->has('b_end')) {
				$lead->b_end = $request->input('b_end');
			}
			$lead->status_id = Status::where('name', 'LIKE', 'New Lead')->first()->id;
			$lead->status_name = Status::where('name', 'LIKE', 'New Lead')->first()->name;
			$lead->remark = $request->input('remark');
			$lead->created_by = '1';

			if ($lead->save()) {
				$followUp = new LeadFollowUp;
				$followUp->status = Status::where('name', 'LIKE', 'New Lead')->first()->id;
				$followUp->remark = $request->input('remark');
				//	$followUp->expected_date_time = date('Y-m-d H:i:s');
				$followUp->lead_id = $lead->id;
				//$followUp->remark_by =Auth::user()->id;
				$followUp->save();
				leadassignWithoutZoneCounsellor($lead);
				return response()->json(['status' => 1, 'msg' => 'Lead added successfully'], 200);
			}

		}
	}





}
