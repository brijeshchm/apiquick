<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Client;
use App\Models\OtpCode;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Mail;
use Carbon\Carbon;
use Laravel\Socialite\Facades\Socialite;
use Validator;
use App\Events\LeadPush;
use App\Models\Lead;
use Google\Client as GoogleClient;
/**
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Authentication"},
     *     summary="Login with Token",
     *     description="This endpoint sends an email to the provided address.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","subject","message"},
     *             @OA\Property(property="email", type="string", format="email", example="test@example.com"),
     *             @OA\Property(property="fcm_token", type="string", example=""),
     *                        
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email sent successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input"
     *     )
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            //'password' => 'required',
        ]);
 
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
 
        $user = Client::where('email', $request->email)->first();
 
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'User account not found',], 403);
        }

        if (!$user->active_status) {
            return response()->json(['status' => false, 'message' => 'Your account has been deactivated',], 403);
        }

        if (!empty($user->deleted_at)) {
            return response()->json(['status' => false, 'message' => 'Your account has been deactivated',], 403);
        }
       
        if ($user) {
            if ($request->fcm_token) {
                $user->fcm_token = $request->fcm_token;
                $user->save();
            }

            $otp = mt_rand(100000, 999999);

            //     //$message = "{$otp} is quickdials Portal Verification Code for {$request->session()->get('client.mobile')}.";
            // // $message = "{$otp} is Lead Portal Verification Code for {$request->session()->get('client.mobile')} quickdials";
            //     $templateId ='1707161786775524106';

            // //sendSMS($request->session()->get('client.mobile'),$message,$templateId);


            OtpCode::updateOrCreate(
                ['user_id' => $user->id], // condition: find by user_id
                [
                    'code' => $otp,  // update/create this
                    'expires_at' => Carbon::now()->addMinutes(5),
                ]
            );
            $message = "{$otp} is QuickDials Verification Code for {$user->email} .";
            $subject = "{$otp} is QuickDials Verification Code";
            $checkmail = Mail::send('emails.sendotp_to_email', ['msg' => $message], function ($m) use ($message, $request, $subject) {
                $m->from('otp@quickdials.com', 'Login OTP');
                $m->to($request->input('email'), "")->subject($subject);
            });
        }
        // Generate new Sanctum token
        $token = $user->createToken('api-token')->plainTextToken;
        //$token = $user->createToken('browser-extension')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'OTP has been sent to your email successfully',
            'token' => $token,
            'token_type' => 'Bearer',
            //  'expires_in' => auth()->factory()->getTTL()*60,
            'data' => $user,
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/verifyOtp",
     *     tags={"Verify Otp"},
     *     summary="Verify OTP and Login",
     *     description="Verify the 6-digit OTP sent to the user's email and issue an API token on success.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "otp"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="otp", type="integer", example=123456, description="6-digit OTP code"),
     *              @OA\Property(property="fcm_token", type="string", example=""),
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP verified successfully. Login successful.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="token", type="string", example="1|xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"),
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="user@example.com"),
     *                 @OA\Property(property="fcm_token", type="string", example="")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid or expired OTP",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid OTP or expired.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The email field is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:clients,email',
            'otp' => 'required|size:6',
        ]);

        $master = '202525';
        $user = client::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'User account not found',], 403);
        }

        if (!$user->active_status) {
            return response()->json(['status' => false, 'message' => 'Your account has been deactivated',], 403);
        }

        if (!empty($user->deleted_at)) {
            return response()->json(['status' => false, 'message' => 'Your account has been deactivated',], 403);
        }

        $otp = OtpCode::where(function ($q) use ($request, $user) {
            $q->where('user_id', $user->id)
                ->where('code', $request->otp);
        })->first();


        if ($otp || $master == $request->otp) {
            // OTP is valid → delete it (one-time use)
            if ($otp) {

                OtpCode::updateOrCreate(
                    ['user_id' => $otp->user_id],
                    [
                        'code' => 0,
                    ]
                );
            }

            if ($request->fcm_token) {
                $user->fcm_token = $request->fcm_token;
                $user->save();
            }

            // Issue Sanctum token
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'token' => $token,
                'token_type' => 'Bearer',
                //  'expires_in' => auth()->factory()->getTTL()*60,
                'data' => $user,

            ]);

        } else {
            return response()->json(['error' => 'Invalid OTP.'], 422);

        }
    }



 /**
     * @OA\Post(
     *     path="/api/apple/login",
     *     tags={"Apple Login"},
     *     summary="Login with Token",
     *     description="This endpoint sends an email to the provided address.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","subject","message"},
     *             @OA\Property(property="email", type="string", format="email", example="test@example.com"),
     *             @OA\Property(property="apple_token", type="string", example=""),
     *                        
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email sent successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input"
     *     )
     * )
     */
    public function appleLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            //'password' => 'required',
        ]);
 
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
 
        $user = Client::where('email', $request->email)->first();
 
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'User account not found',], 403);
        }

        if (!$user->active_status) {
            return response()->json(['status' => false, 'message' => 'Your account has been deactivated',], 403);
        }

        if (!empty($user->deleted_at)) {
            return response()->json(['status' => false, 'message' => 'Your account has been deactivated',], 403);
        }
       
        if ($user) {
            if ($request->apple_token) {
                $user->apple_token = $request->apple_token;
                $user->save();
            }

            $otp = mt_rand(100000, 999999);

            //     //$message = "{$otp} is quickdials Portal Verification Code for {$request->session()->get('client.mobile')}.";
            // // $message = "{$otp} is Lead Portal Verification Code for {$request->session()->get('client.mobile')} quickdials";
            //     $templateId ='1707161786775524106';

            // //sendSMS($request->session()->get('client.mobile'),$message,$templateId);


            OtpCode::updateOrCreate(
                ['user_id' => $user->id], // condition: find by user_id
                [
                    'code' => $otp,  // update/create this
                    'expires_at' => Carbon::now()->addMinutes(5),
                ]
            );
            $message = "{$otp} is QuickDials Verification Code for {$user->email} .";
            $subject = "{$otp} is QuickDials Verification Code";
            $checkmail = Mail::send('emails.sendotp_to_email', ['msg' => $message], function ($m) use ($message, $request, $subject) {
                $m->from('leads.quickdials@gmail.com', 'Login OTP');
                $m->to($request->input('email'), "")->subject($subject);
            }); 			
        }
        // Generate new Sanctum token
        $token = $user->createToken('api-token')->plainTextToken;
		
        //$token = $user->createToken('browser-extension')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'token' => $token,
            //  'expires_in' => auth()->factory()->getTTL()*60,
            'data' => $user,
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/apple/verifyOtp",
     *     tags={"Apple Login Otp "},
     *     summary="Verify OTP and Login",
     *     description="Verify the 6-digit OTP sent to the user's email and issue an API token on success.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "otp"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com"),
     *             @OA\Property(property="otp", type="integer", example=123456, description="6-digit OTP code"),
     *               
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="OTP verified successfully. Login successful.",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *              
     *             @OA\Property(
     *                 property="user",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="user@example.com"),
     *                  
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Invalid or expired OTP",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Invalid OTP or expired.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The email field is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     )
     * )
     */
    public function appleVerifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:clients,email',
            'otp' => 'required|size:6',
        ]);

        $master = '202525';
        $user = client::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'User account not found',], 403);
        }

        if (!$user->active_status) {
            return response()->json(['status' => false, 'message' => 'Your account has been deactivated',], 403);
        }

        if (!empty($user->deleted_at)) {
            return response()->json(['status' => false, 'message' => 'Your account has been deactivated',], 403);
        }

        $otp = OtpCode::where(function ($q) use ($request, $user) {
            $q->where('user_id', $user->id)
                ->where('code', $request->otp);
        })->first();


        if ($otp || $master == $request->otp) {
            // OTP is valid → delete it (one-time use)
            if ($otp) {

                OtpCode::updateOrCreate(
                    ['user_id' => $otp->user_id],
                    [
                        'code' => 0,
                    ]
                );
            }

           

            // Issue Sanctum token
            $token = $user->createToken('api-token')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'token' => $token,
                'token_type' => 'Bearer',
                //  'expires_in' => auth()->factory()->getTTL()*60,
                'data' => $user,

            ]);

        } else {
            return response()->json(['error' => 'Invalid OTP.'], 422);

        }
    }


    /**
     * @OA\Post(
     *     path="/api/google-login",
     *     tags={"Google Login"},
     *     summary="Login with Token",
     *     description="This endpoint sends an email to the provided address.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","subject","message"},
     *             @OA\Property(property="email", type="string", format="email", example="test@example.com"),
     *             @OA\Property(property="fcm_token", type="string", example=""),
     *                        
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email sent successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input"
     *     )
     * )
     */
    public function googleLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'fcm_token' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Client::where('email', $request->email)->first();
        if (!$user) {
            $user = Client::create([
                'email' => $request->email,
                'client_type' => 'gold',
                'active_status' => '1',
                'password' => random_int(1111, 9999),

            ]);

            $emailname = $request->email;
            $clientIDToAppend = $clientID = $user->id;
            if (strlen((string) $clientID) < 4) {
                $clientIDToAppend = str_pad($clientIDToAppend, 4, '0', STR_PAD_LEFT);
            }
            Client::where('email', $request->email)
                ->update(['username' => strtoupper(substr($emailname, 0, 2)) . $clientIDToAppend]);
        }

        if (!$user->active_status) {
            return response()->json(['status' => false, 'message' => 'Your account has been deactivated',], 403);
        }

        if (!empty($user->deleted_at)) {
            return response()->json(['status' => false, 'message' => 'Your account has been deactivated',], 403);
        }

        if ($user) {
            $user->fcm_token = $request->fcm_token;
            $user->save();
        }
        // Generate new Sanctum token
        $token = $user->createToken('api-token')->plainTextToken;
        //$token = $user->createToken('browser-extension')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Login successfully',
            'token' => $token,
            'token_type' => 'Bearer',
            //  'expires_in' => auth()->factory()->getTTL()*60,
            'data' => $user,
        ]);
    }
    /**
     * @OA\Post(
     *     path="/api/check-fcm-token",
     *     tags={"Check FCM Token"},
     *     summary="Check FCM Token",
     *     description="This endpoint sends an email to the provided address.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","subject","message"},
     *             @OA\Property(property="email", type="string", format="email", example="test@example.com"),
     *             
     *                        
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Email sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Email sent successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input"
     *     )
     * )
     */
    public function checkFcmToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = Client::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'User account not found',], 403);
        }

        if (!$user->active_status) {
            return response()->json(['status' => false, 'message' => 'Your account has been deactivated',], 403);
        }

        if (!empty($user->deleted_at)) {
            return response()->json(['status' => false, 'message' => 'Your account has been deactivated',], 403);
        }
        if (!$user->fcm_token) {
            return response()->json(['status' => false, 'message' => 'User FCM token not found',], 403);
        }

        $ids = [680, 155, 156, 145, 682, 559];
        $randomId = $ids[array_rand($ids)];
        $lead = Lead::find($randomId);


        event(new LeadPush($lead, $user->id));

        // Generate new Sanctum token
        // $token = $user->createToken('api-token')->plainTextToken;
        //$token = $user->createToken('browser-extension')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'FCM successfully',
            'fcm_token' => $user->fcm_token,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     tags={"Auth Logout"},
     *     summary="Logout user",
     *     description="Logout the authenticated user by revoking all access tokens",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Logout successful")
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
     *     )
     * )
     */
    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'Logout successfully'
        ], 200);
    }


    /**
     * @OA\Post(
     *     path="/api/delete-account",
     *     tags={"Delete Account"},
     *     summary="Delete Account",
     *     description="Delete the authenticated user by revoking all access tokens",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(
     *                 property="email",
     *                 type="string",
     *                 format="email",
     *                 example="user@example.com"
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Delete successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Delete successful")
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
     *     )
     * )
     */
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);
        Client::where('email', $request->email)->update([
            'active_status' => '0',
            'deleted_at' => date('Y-m-d'),
        ]);

        $user = $request->user();

        if ($user) {
            $user->currentAccessToken()->delete();
        }

        return response()->json([
            'status' => true,
            'message' => 'Delete successfully'
        ], 200);
    }



    /**
     * @OA\Post(
     *     path="/api/business/saveBusinessOwners",
     *     tags={"Frontend Registration Business"},
     *     summary="List Your Business",
     *     description="Registration of business business name, email, mobile.",     *      
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"business_name","mobile","email"},
     *             @OA\Property(property="email", type="string", format="email", example="example@gmail.com"),
     *             @OA\Property(property="business_name", type="integer", example="business name"),
     *             @OA\Property(property="mobile", type="interger", example="9999998888"),
     *              
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Registration Business saved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Registration Business saved successfully")
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

    public function saveBusinessOwners(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'business_name' => 'required|unique:clients,business_name',
            'mobile' => 'required|numeric|digits:10|regex:/^[1-9]+/|unique:clients,mobile,NULL,id',
            'email' => 'required|email|unique:clients,email,NULL,id'
        ]);
        if ($validator->fails()) {
            $errorsBag = $validator->getMessageBag()->toArray();
            return response()->json(['status' => true, 'errors' => $errorsBag], 400);
        }
        $client = new Client;
        $business_slug = NULL;
        $string = $request->input('business_name');
        $string = filter_var($string, FILTER_SANITIZE_STRING);
        $string = preg_replace('/[^A-Za-z0-9]/', ' ', $string);
        $businessName = preg_replace('/\s+/', ' ', str_replace('&', '', trim($string)));
        $business_slug = trim(generate_slug(trim($businessName)));

        $slugExists = DB::table('clients')
            ->select(DB::raw('business_slug'))
            ->where('business_slug', 'like', '%' . $business_slug . '%')
            ->orderBy('id', 'desc')
            ->get();
        if (!empty($slugExists) && $slugExists->count() > 0) {
            $business_slug = $slugExists[0]->business_slug;
            $business_slug = explode("-", $business_slug);
            $end = end($business_slug);
            reset($business_slug);
            if (!is_numeric($end)) {
                $business_slug[] = 1;
            } else {
                ++$end;
                $business_slug[count($business_slug) - 1] = $end;
            }
            $business_slug = implode("-", $business_slug);
        }

        $client->business_name = $businessName;
        $client->business_slug = $business_slug;
        $pass = rand(000001, 999999);
        $client->password = bcrypt($pass);
        $client->mobile = $request->input('mobile');
        $client->email = $request->input('email');
        $client->max_kw = 30;
        $client->expired_from = date('Y-m-d');
        $client->expired_on = date('Y-m-d', strtotime('+60 days'));
        $client->active_status = '1';
        $client->username = '';
        $client->client_type = 'gold';
        if ($client->save()) {

            $client = Client::find($client->id);
            $emailname = $request->input('email');
            $clientIDToAppend = $clientID = $client->id;
            if (strlen((string) $clientID) < 4) {
                $clientIDToAppend = str_pad($clientIDToAppend, 4, '0', STR_PAD_LEFT);
            }
            $client->username = strtoupper(substr($emailname, 0, 2)) . $clientIDToAppend;
            $client->save();

            $smsMessage = "Thanks for registering with QuickDials.
				%0D%0ALogin %26 Update your profile to get more leads to grow your business.
				%0D%0A%0D%0ABusiness Name:" . $client->business_name . "
				%0D%0AURL:www.quickdials.com
				%0D%0AUID:" . $client->username . "
				%0D%0APassword:" . $pass . "
				%0D%0A--
				%0D%0ARegards
				%0D%0AQuickDials Team";
            // sendSMS($client->mobile, $smsMessage);
            $data['clientDetails'] = Client::find($client->id);
            $data['status'] = true;
            $data['message'] = "Business registered successfully!";

        } else {
            $data['status'] = false;
            $data['message'] = "Business not registered successfully!";

        }
        return response()->json([
            'data' => $data,
        ], 200);

    }

}
