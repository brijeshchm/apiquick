<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Client\Client; //model
use Validator;
use App\Models\Occupation;
use App\Models\Citieslists;
class PersonalDetailsController extends Controller
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

	public function personalDetails(Request $request)
	{
		try {
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
			if (!$user->active_status) {
				$user->tokens()->delete();
				return response()->json(['status' => false, 'message' => 'User account is inactive',], 403);
			}


			$occupations = Occupation::where('status', '1')->get();
			$occupation_list = [];
			if (!empty($occupations)) {
				foreach ($occupations as $key => $occupation) {
 
					$occupation_list[$key] = array(
						'id' => $occupation->id,
						'name' => $occupation->name,
						'status' => $occupation->status,
					);
				}
			$data['occupation'] = $occupation_list;
			}
			
			$citys = Citieslists::get();

			$city_list = [];
			if (!empty($citys)) {
				foreach ($citys as $cityKey => $cityVal) {
 
					$city_list[$cityKey] = array(
						'id' => $cityVal->id,
						'city' => $cityVal->city,
						'state_id' => $cityVal->state_id,
					);
				}
				$data['cities'] = $city_list;
			}
		
 
			  $data['edit_data'] = array(
			        'client_id' => $user->id,
			        'sirName' => $user->sirName,
			        'first_name' => $user->first_name,
			        'middle_name' => $user->middle_name,
			        'last_name' => $user->last_name,
			        'dob' =>date('Y-m-d',strtotime($user->dob)),
			        'email' => $user->email,
			        'marital' => $user->marital,
			        'mobile' => $user->mobile,
			        'sec_mobile' => $user->sec_mobile,
			        'city_id' => $user->city_id,
			        'city' => $user->city,
			        'area' => $user->area,
			        'pincode' => $user->pincode,
			        'gender' => $user->gender,
			        
			    );
		 
			$data['status']= true;
			$data['code']= 200;
			$data['message']= "Successfully";

		} catch (\Exception $e) {
			$data['status']= false;
			$data['code']= 400;
			$data['message']= $e->getMessage();
		}
		echo json_encode($data);
		
	}
	/*
	form field:sirName,first_name,middle_name,last_name,dob,email,marital,mobile,sec_mobile,city,area,pincode,occupation,gender

	*/
	public function savePersonalDetails(Request $request)
	{
		try {
 
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
			if (!$user->active_status) {
				$user->tokens()->delete();
				return response()->json(['status' => false, 'message' => 'User account is inactive',], 403);
			}


			$validator = Validator::make($request->all(), [

				'first_name' => 'required|max:255',
				'dob' => 'required',
				'email' => 'required',
				'marital' => 'required',
				'mobile' => 'required',
				'city' => 'required',
				'sirName' => 'required',

			]);

			if ($validator->fails()) {
				$errorsBag = $validator->getMessageBag()->toArray();
				return response()->json(['status' => true, 'errors' => $errorsBag], 400);
			}

			 
			$client = Client::find($user->id);
			$client->sirName = $request->input('sirName');
			$client->first_name = ucfirst($request->input('first_name'));
			$client->middle_name = $request->input('middle_name');
			$client->last_name = $request->input('last_name');
			$client->dob = date('Y-m-d', strtotime($request->input('dob')));
			$client->email = $request->input('email');
			$client->marital = $request->input('marital');
			$client->mobile = $request->input('mobile');
			$client->sec_mobile = $request->input('sec_mobile');

			$cityName = Citieslists::where('id',$request->input('city'))->first();
			if($cityName){
				$client->city_id = $cityName->id;
				$client->city = $cityName->city;
			}
			$client->area = $request->input('area');
			$client->pincode = $request->input('pincode');
			$client->occupation = $request->input('occupation');
			$client->gender = $request->input('gender');
		 
			if ($client->save()) {
				$data['status'] = true;
				$data['code'] = 200;
				$data['message'] = "Personal Details updated successfully !";
			} else {
				$data['status'] = false;
				$data['code'] = 400;
				$data['message'] = "Personal Details could not be successfully, Please try again !";
			}

		}catch (\Exception $e) {
				$data['status'] = false;
				$data['code'] = 400;
				$data['message'] = $e->getMessage();
		}
		echo json_encode($data);
	}


}