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
	/**
	 * @OA\Get(
	 *     path="/api/business/personal-details",
	 *     tags={"User"},
	 *     summary="Get personal details",
	 *     description="Fetch the personal details of the authenticated user",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Personal details retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="object",
	 *                 @OA\Property(property="id", type="integer", example=101),
	 *                 @OA\Property(property="name", type="string", example="John Doe"),
	 *                 @OA\Property(property="email", type="string", example="john@example.com"),
	 *                 @OA\Property(property="phone", type="string", example="+911234567890"),
	 *                 @OA\Property(property="address", type="string", example="123, Example Street, City"),
	 *                 @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-06T12:00:00Z"),
	 *                 @OA\Property(property="updated_at", type="string", format="date-time", example="2025-09-06T12:30:00Z")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Unauthorized access")
	 *         )
	 *     )
	 * )
	 */

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
	/**
 * @OA\Post(
 *     path="/api/business/savePersonalDetails",
 *     tags={"User"},
 *     summary="Save or update personal details",
 *     description="Save or update the personal details of the authenticated user",
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             @OA\Property(property="sirName", type="string", example="Mr"),
 *             @OA\Property(property="first_name", type="string", example="John"),
 *             @OA\Property(property="middle_name", type="string", example="A"),
 *             @OA\Property(property="last_name", type="string", example="Doe"),
 *             @OA\Property(property="dob", type="string", format="date", example="1990-01-01"),
 *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *             @OA\Property(property="marital", type="string", example="single"),
 *             @OA\Property(property="mobile", type="string", example="+911234567890"),
 *             @OA\Property(property="sec_mobile", type="string", example="+911234567891"),
 *             @OA\Property(property="city", type="string", example="New Delhi"),
 *             @OA\Property(property="area", type="string", example="Connaught Place"),
 *             @OA\Property(property="pincode", type="string", example="110001"),
 *             @OA\Property(property="occupation", type="string", example="Software Engineer"),
 *             @OA\Property(property="gender", type="string", example="male")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Personal details saved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Personal details updated successfully")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Invalid request",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Invalid parameters")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Unauthorized access")
 *         )
 *     )
 * )
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