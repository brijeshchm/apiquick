<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use App\Models\Client\Client;
use DB;
use Log;
use Validator;
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
     *     summary="Get authenticated user profile information",
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

            $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            $times = ["24:00" => "Open 24 Hrs", "00:00" => "00:00", "00:30" => "00:30", "01:00" => "01:00", "01:30" => "01:30", "02:00" => "02:00", "02:30" => "02:30", "03:00" => "03:00", "03:30" => "03:30", "04:00" => "04:00", "04:30" => "04:30", "05:00" => "05:00", "05:30" => "05:30", "06:00" => "06:00", "06:30" => "06:30", "07:00" => "07:00", "07:30" => "07:30", "08:00" => "08:00", "08:30" => "08:30", "09:00" => "09:00", "09:30" => "09:30", "10:00" => "10:00", "10:30" => "10:30", "11:00" => "11:00", "11:30" => "11:30", "12:00" => "12:00", "12:30" => "12:30", "13:00" => "13:00", "13:30" => "13:30", "14:00" => "14:00", "14:30" => "14:30", "15:00" => "15:00", "15:30" => "15:30", "16:00" => "16:00", "16:30" => "16:30", "17:00" => "17:00", "17:30" => "17:30", "18:00" => "18:00", "18:30" => "18:30", "19:00" => "19:00", "19:30" => "19:30", "20:00" => "20:00", "20:30" => "20:30", "21:00" => "21:00", "21:30" => "21:30", "22:00" => "22:00", "22:30" => "22:30", "23:00" => "23:00", "23:30" => "23:30", "closed" => "Closed"];
            if (!empty($user->time)) {
                $time = unserialize($user->time);
            } else {
                $time = "";
            }
            if (!empty($user->certifications)) {
                $certifications = $user->certifications;
            } else {
                $certifications = "";
            }
            if (!empty($user->profile_pic)) {
                $profile_pic = unserialize($user->profile_pic);
            } else {
                $profile_pic = "";
            }
            if (!empty($user->pictures)) {
                $pictures = unserialize($user->pictures);
            } else {
                $pictures = "";
            }

            $data['userDetails'] = array(
                'client_id' => $user->id,
                'username' => $user->username,
                'business_slug' => $user->business_slug,
                'business_name' => $user->business_name,
                'business_intro' => $user->business_intro,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'client_type' => $user->client_type,
                'balance_amt' => $user->balance_amt,
                'coins_amt' => $user->coins_amt,
                'leads_remaining' => $user->leads_remaining,
                'expired_from' => $user->expired_from,
                'expired_on' => $user->expired_on,
                'certified_status' => $user->certified_status,
                'city_id' => $user->city_id,
                'city' => $user->city,
                'address' => $user->address,
                'landmark' => $user->landmark,
                'state' => $user->state,
                'country' => $user->country,
                'time' => $time,
                'days' => $days,
                'times' => $times,
                'certifications' => $certifications,
                'year_of_estb' => $user->year_of_estb,
                'profile_pic' => $profile_pic,
                'pictures' => $pictures,
                'active_status' => $user->active_status,
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
     *     summary="Save profile information",
     *     description="Stores profile information like email, year of establishment, display info, intro, and certifications.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","year_of_estb"},
     *             @OA\Property(property="email", type="string", format="email", example="example@domain.com"),
     *             @OA\Property(property="year_of_estb", type="integer", example=2020),
     *             @OA\Property(property="display_hofo", type="string", example="Head Office Display Name"),
     *             @OA\Property(property="business_intro", type="string", example="We are a leading provider of IT services established in 2020."),
     *             @OA\Property(property="certifications", type="string", example="ISO 9001, ISO 27001")
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
                'email' => 'required|max:255|unique:clients,email,' . $user->id . ',id',
                'year_of_estb' => 'required',


            ]);

            if ($validator->fails()) {
                $errorsBag = $validator->getMessageBag()->toArray();
                return response()->json(['status' => 1, 'errors' => $errorsBag], 400);
            }

            $user = Client::find($user->id);
            $user->display_hofo = $request->input('display_hofo');
            $user->business_intro = $request->input('business_intro');
            $user->year_of_estb = $request->input('year_of_estb');
            $user->certifications = $request->input('certifications');


            if ($user->save()) {

                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                $times = ["24:00" => "Open 24 Hrs", "00:00" => "00:00", "00:30" => "00:30", "01:00" => "01:00", "01:30" => "01:30", "02:00" => "02:00", "02:30" => "02:30", "03:00" => "03:00", "03:30" => "03:30", "04:00" => "04:00", "04:30" => "04:30", "05:00" => "05:00", "05:30" => "05:30", "06:00" => "06:00", "06:30" => "06:30", "07:00" => "07:00", "07:30" => "07:30", "08:00" => "08:00", "08:30" => "08:30", "09:00" => "09:00", "09:30" => "09:30", "10:00" => "10:00", "10:30" => "10:30", "11:00" => "11:00", "11:30" => "11:30", "12:00" => "12:00", "12:30" => "12:30", "13:00" => "13:00", "13:30" => "13:30", "14:00" => "14:00", "14:30" => "14:30", "15:00" => "15:00", "15:30" => "15:30", "16:00" => "16:00", "16:30" => "16:30", "17:00" => "17:00", "17:30" => "17:30", "18:00" => "18:00", "18:30" => "18:30", "19:00" => "19:00", "19:30" => "19:30", "20:00" => "20:00", "20:30" => "20:30", "21:00" => "21:00", "21:30" => "21:30", "22:00" => "22:00", "22:30" => "22:30", "23:00" => "23:00", "23:30" => "23:30", "closed" => "Closed"];
                if (!empty($user->time)) {
                    $time = unserialize($user->time);
                } else {
                    $time = "";
                }
                if (!empty($user->certifications)) {
                    $certifications = $user->certifications;
                } else {
                    $certifications = "";
                }
                if (!empty($user->profile_pic)) {
                    $profile_pic = unserialize($user->profile_pic);
                } else {
                    $profile_pic = "";
                }
                if (!empty($user->pictures)) {
                    $pictures = unserialize($user->pictures);
                } else {
                    $pictures = "";
                }
                $data['userDetails'] = array(
                    'client_id' => $user->id,
                    'username' => $user->username,
                    'business_slug' => $user->business_slug,
                    'business_name' => $user->business_name,
                    'business_intro' => $user->business_intro,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'client_type' => $user->client_type,
                    'balance_amt' => $user->balance_amt,
                    'coins_amt' => $user->coins_amt,
                    'leads_remaining' => $user->leads_remaining,
                    'expired_from' => $user->expired_from,
                    'expired_on' => $user->expired_on,
                    'certified_status' => $user->certified_status,
                    'city_id' => $user->city_id,
                    'city' => $user->city,
                    'address' => $user->address,
                    'landmark' => $user->landmark,
                    'state' => $user->state,
                    'country' => $user->country,
                    'time' => $time,
                    'days' => $days,
                    'times' => $times,
                    'certifications' => $certifications,
                    'year_of_estb' => $user->year_of_estb,
                    'profile_pic' => $profile_pic,
                    'pictures' => $pictures,
                    'active_status' => $user->active_status,
                );

                $data['status'] = true;
                $data['message'] = "Profile updated successfully!";
                 
               
            } else {
                $data['status'] = false;
                $data['message'] = "Profile not updated successfully!";
            }
        } catch (\Exception $e) {
                $data['status'] = false;
                $data['message'] = 'Failed to : ' . $e->getMessage();
            
        }
        echo json_encode($data);

    }

}
