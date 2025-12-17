<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\State;
use App\Models\Citieslists;
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
                $time = unserialize($user->time);
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
                'address' => $user->address,
                'landmark' => $user->landmark,
                'business_city_id' => $user->business_city_id,
                'business_city' => $user->business_city,
                'business_state' => $user->business_state,
                'business_state_id' => $user->business_state_id,
                'country' => $user->country,
                'business_intro' => $user->business_intro,                
                'client_type' => $user->client_type,                 
                'certified_status' => $user->certified_status,
                'time' => $time,
                'days' => $days,
                'times' => $times,
                 "area"=>$user->area,
                "pincode"=> $user->pincode,
                'certifications' => $certifications,
                'year_of_estb' => $user->year_of_estb,
                'display_hofo' => $user->display_hofo,
                 
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
     *             @OA\Property(property="business_state", type="integer", example=10),
     *             @OA\Property(property="business_city", type="integer", example=961),
     *             @OA\Property(property="area", type="string", example="sector-3"),
     *             @OA\Property(property="pincode", type="integer", example="201301"),
     *             @OA\Property(property="country", type="string", example="india"),
     *             @OA\Property(property="year_of_estb", type="integer", example=2020),
     *             @OA\Property(property="display_hofo", type="string", example="0"),
     *             @OA\Property(property="business_intro", type="string", example="We are a leading provider of IT services established in 2020."),
     *             @OA\Property(property="certifications", type="string", example="ISO 9001, ISO 27001"),
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
                'year_of_estb' => 'required|digits:4|integer|min:1900|max:' . date('Y'),
                'business_name' => 'required|string|max:255',
                'business_intro' => 'required|string',
                'address' => 'required|string',
                'certifications' => 'required|string|max:255',
                'business_city'  => 'required|integer|exists:citylists,id',
                'business_state' => 'required|integer|exists:state,id',
                'pincode' => 'required|digits:6',
                'area' => 'required|string',
            ]);

            if ($validator->fails()) {
                $errorsBag = $validator->getMessageBag()->toArray();
                return response()->json(['status' => 1, 'errors' => $errorsBag], 400);
            }

            $user = Client::find($user->id);
            $user->business_name = $request->input('business_name');
            $user->address = $request->input('address');
            $user->landmark = $request->input('landmark');
            $user->display_hofo = $request->input('display_hofo');
            $state = State::where('id',$request->input('business_state'))->first();
            if($state){
                $user->business_state_id = $state->id;
                $user->business_state = $state->name;

            }
           $cityName = Citieslists::where('id',$request->input('business_city'))->first();
			if($cityName){
				$user->business_city_id = $cityName->id;
				$user->business_city = $cityName->city;
			}
           

            $user->area = $request->input('area');
            $user->pincode = $request->input('pincode');
            $user->country = $request->input('country');
            $user->business_intro = $request->input('business_intro');
            $user->year_of_estb = $request->input('year_of_estb');
            $user->certifications = $request->input('certifications');
            $user->time = $request->input('time');


            if ($user->save()) {
               
                $data['status'] = true;
                $data['message'] = "Business Information updated successfully!";


            } else {
                $data['status'] = false;
                $data['message'] = "Business Information not updated successfully!";
            }
        } catch (\Exception $e) {
            $data['status'] = false;
            $data['message'] = 'Failed to : ' . $e->getMessage();

        }
        return response()->json([
            'data' => $data,
        ], 200);

    }


    /**
     * @OA\Get(
     *     path="/api/business/review",
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
    public function profileReview(Request $request)
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


            $clientscheck = DB::table('clients')
                ->leftJoin(DB::raw('(
        SELECT 
            SUM(rating) AS total_rating, 
            comment_client_ID, 
            COUNT(comment_ID) AS comment_count
        FROM comments 
        GROUP BY comment_client_ID
    ) as c'), 'c.comment_client_ID', '=', 'clients.id')
                ->select(
                    'clients.id',
                    'clients.business_name',
                    'clients.business_slug',
                    DB::raw('COALESCE(c.total_rating, 0) as total_rating'),
                    DB::raw('COALESCE(c.comment_count, 0) as comment_count')
                )
                ->where('clients.id', $user->id)
                ->get()
                ->map(function ($client) {
                    // ✅ Fetch all comments for this specific client
                    $comments = DB::table('comments')
                        ->where('comment_client_ID', $client->id)
                        ->select('comment_ID', 'comment_content', 'rating', 'created_at', 'comment_author', 'comment_author_email', 'comment_author_phone')
                        ->orderByDesc('created_at')
                        ->get()
                        ->map(function ($comment) {
                        // ✅ Mask email
                        if (!empty($comment->comment_author_email)) {
                            [$name, $domain] = explode('@', $comment->comment_author_email);
                            $comment->comment_author_email =
                                substr($name, 0, 4) . str_repeat('*', max(0, strlen($name) - 4)) . '@' . $domain;
                        }

                        // ✅ Mask phone (keep first 3 and last 3 digits visible)
                        if (!empty($comment->comment_author_phone)) {
                            $phone = preg_replace('/\D/', '', $comment->comment_author_phone); // remove non-digits
                            $comment->comment_author_phone =
                                substr($phone, 0, 3) . str_repeat('*', max(0, strlen($phone) - 6)) . substr($phone, -3);
                        }

                        return $comment;
                    });

                    // ✅ Compute average rating
                    $avg_rating = $client->comment_count > 0
                        ? round($client->total_rating / $client->comment_count, 1)
                        : 0;

                    // ✅ Build clean output array
                    return [
                        'business_name' => $client->business_name,
                        'business_slug' => $client->business_slug,
                        'total_rating' => $client->total_rating,
                        'avg_rating' => $avg_rating,
                        'comment_count' => $client->comment_count,
                        'comments' => $comments,
                    ];
                })
                ->first();


            return response()->json([
                'status' => true,
                'data' => $clientscheck,
                'message' => 'get data record',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to : ' . $e->getMessage(),
            ], 500);
        }


    }

}
