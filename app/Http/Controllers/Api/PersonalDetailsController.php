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
	 *     tags={"Profile"},
	 *     summary="Get personal details",
	 *     description="Fetch the personal details of the authenticated user",
	 * 	   security={{"bearerAuth":{}}},
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
			        'occupation' => $user->occupation,
			        
			    );
		 
			$data['status']= true;
			$data['code']= 200;
			$data['message']= "Successfully";

		} catch (\Exception $e) {
			$data['status']= false;
			$data['code']= 400;
			$data['message']= $e->getMessage();
		}
		 
		return response()->json([
			'status' => true,
			'message' => "Successfully",
			'data' => $data,

		], 200);
	}
	/**
 * @OA\Post(
 *     path="/api/business/savePersonalDetails",
 *     tags={"Profile"},
 *     summary="Save or update personal details",
 *     description="Save or update the personal details of the authenticated user",
 *     security={{"bearerAuth":{}}},
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
 *             @OA\Property(property="city", type="string", example="1011"),
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

        // ✅ Sanctum Authentication
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated: Token is missing or invalid',
                'error'   => 'token_missing_or_invalid'
            ], 401);
        }

        // ✅ Check active user
        if (!$user->active_status) {
            $user->tokens()->delete();
            return response()->json([
                'status' => false,
                'message' => 'User account is inactive'
            ], 403);
        }

        // ✅ Fetch client
        $client = Client::where('id', $user->id)->first();
        if (!$client) {
            return response()->json([
                'status' => false,
                'message' => 'Client not found'
            ], 404);
        }

        // ✅ Validation (email unique on update)
        $validator = Validator::make($request->all(), [
            'sirName'     => 'required|string|max:15',
            'first_name'  => 'required|string|min:3|max:255',
            'dob'         => 'required|date|before:today',
            'email'       => 'required|email|unique:clients,email,' . $client->id,
            'marital'     => 'required|string',
            'mobile'      => 'required|string|max:15',
            'city'        => 'required|integer|exists:citylists,id',
            'middle_name' => 'nullable|string|max:255',
            'last_name'   => 'nullable|string|max:255',
            'sec_mobile'  => 'nullable|string|max:15',
            'area'        => 'nullable|string|max:255',
            'pincode'     => 'nullable|string|max:10',
            'occupation'  => 'nullable|string|max:255',
            'gender'      => 'nullable|in:Male,Female,Other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // ✅ City lookup
        $city = Citieslists::find($request->city);

        // ✅ Update client
        $client->update([
            'sirName'      => $request->sirName,
            'first_name'   => ucfirst($request->first_name),
            'middle_name'  => $request->middle_name,
            'last_name'    => $request->last_name,
            'dob'          => $request->dob,
            // 'email'        => $request->email,
            'marital'      => $request->marital,
            'mobile'       => $request->mobile,
            'sec_mobile'   => $request->sec_mobile,
            'city_id'      => $city?->id,
            'city'         => $city?->city,
            'area'         => $request->area,
            'pincode'      => $request->pincode,
            'occupation'   => $request->occupation,
            'gender'       => $request->gender,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Personal details updated successfully',
            'data' => $client
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => false,
            'message' => 'Something went wrong',
            'error' => $e->getMessage()
        ], 500);
    }
}



	/**
	 * @OA\Get(
	 *     path="/api/business/get-occupation",
	 *     tags={"Occupation"},
	 *     summary="Get Occupation",
	 *     description="Fetch the Occupation of the authenticated user",
	 * 	   security={{"bearerAuth":{}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="Occupation retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="object",
	 *                 @OA\Property(property="id", type="integer", example=101),
	 *                 @OA\Property(property="Occupation", type="string", example="Software engineer")
	 *                 
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

	public function getOccupation(Request $request)
	{

			$occupations = Occupation::where('status','1')->get();
			if (!empty($occupations)) {
			foreach ($occupations as $key => $val) {
				 
				$occupations_list[$key] = array(
					'id' => $val->id,
					'name' => $val->name,
					 

				);
			}
			$data['occupations'] = $occupations_list;
		}

		return response()->json([
			'status' => true,
			'data' => $data,
			 
		], 200);
	}

}