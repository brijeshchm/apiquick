<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\State;
use App\Models\Zone;
use App\Models\Citieslists;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use App\Models\Client\Client;
use App\Models\Client\Comment;
use DB;
use Log;
use Validator;
use Illuminate\Validation\Rule;
use function PHPUnit\Framework\isFalse;

/**
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
/**
 * @OA\Tag(
 *     name="Profile",
 *     description="API Endpoints for user profile information"
 * )
 */
class ProfileController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/business/profileInfo",
     *     tags={"Profile"},
     *     summary="Get Business Information Profile",
     *     description="Returns profile details of the logged-in user. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile info",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-04T12:34:56Z")
     *         )
     *     ),
     *      @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function profileInfo(Request $request)
    {
        try {

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

            if (!$user->active_status) {
                $user->tokens()->delete();
                return response()->json(['status' => false, 'message' => 'User account is inactive',], 403);
            }


            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            $times = ["24:00" => "Open 24 Hrs", "00:00" => "00:00", "00:30" => "00:30", "01:00" => "01:00", "01:30" => "01:30", "02:00" => "02:00", "02:30" => "02:30", "03:00" => "03:00", "03:30" => "03:30", "04:00" => "04:00", "04:30" => "04:30", "05:00" => "05:00", "05:30" => "05:30", "06:00" => "06:00", "06:30" => "06:30", "07:00" => "07:00", "07:30" => "07:30", "08:00" => "08:00", "08:30" => "08:30", "09:00" => "09:00", "09:30" => "09:30", "10:00" => "10:00", "10:30" => "10:30", "11:00" => "11:00", "11:30" => "11:30", "12:00" => "12:00", "12:30" => "12:30", "13:00" => "13:00", "13:30" => "13:30", "14:00" => "14:00", "14:30" => "14:30", "15:00" => "15:00", "15:30" => "15:30", "16:00" => "16:00", "16:30" => "16:30", "17:00" => "17:00", "17:30" => "17:30", "18:00" => "18:00", "18:30" => "18:30", "19:00" => "19:00", "19:30" => "19:30", "20:00" => "20:00", "20:30" => "20:30", "21:00" => "21:00", "21:30" => "21:30", "22:00" => "22:00", "22:30" => "22:30", "23:00" => "23:00", "23:30" => "23:30", "closed" => "Closed"];
            if (!empty($user->time)) {
                $time = json_decode($user->time);
            } else {
                $time = "";
            }
            if (!empty($user->certifications)) {
                $certifications = $user->certifications;
            } else {
                $certifications = "";
            }

           
            $data['businessInformation'] = array(
                'client_id' => $user->id,
                'business_name' => $user->business_name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'sec_mobile' => $user->sec_mobile,
                'address' => $user->address,
                'landmark' => $user->landmark,
                'zone' => $user->zone,
                'zone_id' => $user->zone_id,
                'occupation' => $user->occupation,
                'city_id' => $user->city_id,
                'city' => $user->city,
                'state' => $user->state,
                'state_id' => $user->state_id,
                'country_id' => $user->country,
                'country' => 'India',
                'business_intro' => $user->business_intro,
                'client_type' => $user->client_type,
                'certified_status' => $user->certified_status,
                'time' => $time,
                'days' => $days,
                'times' => $times,
                "area" => $user->area,
                "pincode" => $user->pincode,
                'certifications' => $certifications,
                'year_of_estb' => $user->year_of_estb,
                'display_hofo' => $user->display_hofo,
                'business_map' => $user->business_map,
                'trusted_status' => $user->trusted_status,
                'gst_status' => $user->gst_status,
              

            );

            return response()->json([
                'status' => true,
                'data' => $data,
                'message' => 'get data record',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to : ' . $e->getMessage(),
            ], 500);
        }


    }

    /**
     * @OA\Post(
     *     path="/api/business/saveProfileInfo",
     *     tags={"Profile"},
     *     summary="Save Business Information Profile",
     *     description="Stores profile information like email, year of establishment, display info, intro, and certifications.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"certifications","business_name","business_intro"},     *            
     *             @OA\Property(property="business_name", type="string", example="business name"),
     *             @OA\Property(property="address", type="string", example=" E-23 sector -3 noida"),
     *             @OA\Property(property="landmark", type="string", example="landmark"),
     *             @OA\Property(property="email", type="string", example="email"),
     *             @OA\Property(property="mobile", type="integer", example="234567986"),
     *             @OA\Property(property="sec_mobile", type="integer", example="234567986"),
     *             @OA\Property(property="state", type="integer", example=39),
     *             @OA\Property(property="city", type="integer", example=961),
     *             @OA\Property(property="zone", type="string", example="1090"),
     *             @OA\Property(property="area", type="string", example="sector-3"),
     *             @OA\Property(property="pincode", type="integer", example="201301"),
     *             @OA\Property(property="country", type="string", example="india"),
     *             @OA\Property(property="occupation", type="string", example="Engineer"),
     *             @OA\Property(property="year_of_estb", type="integer", example=2020),
     *             @OA\Property(property="display_hofo", type="string", example="0"),
     *             @OA\Property(property="business_intro", type="string", example="We are a leading provider of IT services established in 2020."),
     *             @OA\Property(property="certifications", type="string", example="ISO 9001, ISO 27001"),
     *             @OA\Property(property="business_map", type="string", example="location map"),     *          
     *             @OA\Property(property="time", type="string", example=""),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile info saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile info saved successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The email field is required."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(property="email", type="array", @OA\Items(type="string", example="The email must be a valid email address.")),
     *                 @OA\Property(property="year_of_estb", type="array", @OA\Items(type="string", example="The year of establishment field is required."))
     *             )
     *         )
     *     )
     * )
     */
    public function saveProfileInfo(Request $request)
    {
        try {

            // ✅ Sanctum Authentication
            $authUser = auth('sanctum')->user();
            if (!$authUser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated: Token is missing or invalid',
                    'error' => 'token_missing_or_invalid'
                ], 401);
            }

            // ✅ Get Client
            $client = Client::where('id', $authUser->id)->first();
            if (!$client) {
                return response()->json([
                    'status' => false,
                    'message' => 'Client not found'
                ], 404);
            }
 
            // ✅ Validation
            $validator = Validator::make($request->all(), [
                'year_of_estb' => 'required|digits:4|integer|min:1900|max:' . date('Y'),
                'business_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('clients', 'business_name')->ignore($client->id, 'id'),
                ],
                'business_intro' => 'required|string',
                'email' => 'required|email|unique:clients,email,' . $client->id . ',id',
                'mobile' => 'required|unique:clients,mobile,' . $client->id . ',id',     
            
                'address' => 'required|string|max:255',
                'certifications' => 'nullable|string|max:255',
                'city' => 'required|integer|exists:citylists,id',
                'state' => 'required|integer|exists:state,id',
                'zone' => 'nullable|integer|exists:zones,id',
                'pincode' => 'nullable|digits:6',
                'area' => 'nullable|string|max:255',

                // Optional
                'landmark' => 'nullable|string|max:255',
                'display_hofo' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'time' => 'nullable|string|max:255',
                 
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
        }

            $state = State::find($request->state);
            $city  = Citieslists::find($request->city);
            $zone  = Zone::find($request->zone);

     
            $string = strip_tags($request->business_name);
            $string = preg_replace('/[^A-Za-z0-9 ]/', ' ', $string);
            $string = preg_replace('/\s+/', ' ', trim($string));

         
            if (!empty($request->time)) {
                $client->time = json_encode($request->time);
            }
 
            $client->update([
                'business_name' => $string,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'sec_mobile' => $request->sec_mobile,

                'address' => $request->address,
                'landmark' => $request->landmark,
                'display_hofo' => $request->display_hofo,

                'state_id' => $state->id,
                'state' => $state->name,

                'city_id' => $city->id,
                'city' => $city->city,

                'zone_id' => $zone->id,
                'zone' => $zone->zone,

                'area' => $request->area,
                'occupation' => $request->occupation,
                'pincode' => $request->pincode,
                'country' => $request->country,

                'business_intro' => $request->business_intro,
                'year_of_estb' => $request->year_of_estb,
                'certifications' => $request->certifications,
                'business_map' => $request->business_map,
            ]);
          

            return response()->json([
                'status' => true,
                'message' => 'Business information updated successfully',
                'data' => $request->all()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }



}
