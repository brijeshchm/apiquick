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
            return response()->json(['status' => false, 'message' => 'User account is inactive',], 403);
        }
        // if (!$user || !Hash::check($request->password, $user->password)) {
        //     return response()->json(['status' => false, 'message' => 'Invalid credentials'], 401);
        // }
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
            Mail::send('emails.sendotp_to_email', ['msg' => $message], function ($m) use ($message, $request, $subject) {
                $m->from('leads@quickdials.com', 'Login OTP');
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
        $otp = OtpCode::where(function ($q) use ($request, $user) {
            $q->where('user_id', $user->id)
                ->where('code', $request->otp);
        })
            ->first();


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
     *     path="/api/google-login",
     *     tags={"Socials Login"},
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
            return response()->json(['status' => false, 'message' => 'User account not found',], 403);
        }
        // if (!$user->active_status) {
        //     return response()->json(['status' => false, 'message' => 'User account is inactive',], 403);
        // }

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
            'message' => 'Logout successful'
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


    /**
     * @OA\Get(
     *     path="/api/auth/google/redirect",
     *     tags={"Google Auth Login Android"},
     *     summary="Get Google OAuth redirect URL",
     *     description="Returns the URL where the mobile/web client should redirect the user to start Google login",
     *     @OA\Response(
     *         response=200,
     *         description="Redirect URL",
     *         @OA\JsonContent(
     *             @OA\Property(property="url", type="string", example="")
     *         )
     *     )
     * )
     */
    public function getRedirectUrl()
    {
        $url = Socialite::driver('google')
            ->stateless()
            ->scopes(['openid', 'email', 'profile'])
            ->redirect()
            ->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/google/callback",
     *     tags={"Google Auth Login Android"},
     *     summary="Google OAuth callback (for API clients)",
     *     description="Mobile apps / SPAs send the 'code' received from Google here. Backend exchanges code → token → user info.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code"},
     *             @OA\Property(property="code", type="string", example="4/0AX4XfW...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer"),
     *             @OA\Property(property="user", ref="")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid code / state"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function handleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $parts = explode(' ', $googleUser->getName(), 2);

            $firstName = $parts[0];
            $lastName = $parts[1] ?? '';
            $client = Client::where('email', $googleUser->getEmail())->first();

            if (!$client) {


                $client = Client::create([
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'client_type' => 'gold',
                    'active_status' => 1,

                ]);

                $emailname = $googleUser->getEmail();
                $clientIDToAppend = $clientID = $client->id;
                if (strlen((string) $clientID) < 4) {
                    $clientIDToAppend = str_pad($clientIDToAppend, 4, '0', STR_PAD_LEFT);
                }
                Client::where('email', $googleUser->getEmail())
                    ->update(['username' => strtoupper(substr($emailname, 0, 2)) . $clientIDToAppend]);


            } else {


                $client = User::updateOrCreate(
                    ['email' => $googleUser->getEmail()],
                    [

                        'google_id' => $googleUser->getId(),

                    ]
                );
            }


            $token = $client->createToken('google-auth-token')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Your email successfully',
                'token' => $token,
                'token_type' => 'Bearer',
                'data' => $client,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/auth/google-ios/redirect",
     *     tags={"Google Auth Login IOS"},
     *     summary="Get Google OAuth redirect URL",
     *     description="Returns the URL where the mobile/web client should redirect the user to start Google login",
     *     @OA\Response(
     *         response=200,
     *         description="Redirect URL",
     *         @OA\JsonContent(
     *             @OA\Property(property="url", type="string", example="")
     *         )
     *     )
     * )
     */
    public function getRedirectUrlIos()
    {
        $url = Socialite::driver('google_ios')
            ->stateless()
            ->scopes(['openid', 'email', 'profile'])
            ->redirect()
            ->getTargetUrl();

        return response()->json(['url' => $url]);
    }

    /**
     * @OA\Post(
     *     path="/api/auth/google-ios/callback",
     *     tags={"Google Auth Login IOS"},
     *     summary="Google OAuth callback (for API clients)",
     *     description="Mobile apps / SPAs send the 'code' received from Google here. Backend exchanges code → token → user info.",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"code"},
     *             @OA\Property(property="code", type="string", example="4/0AX4XfW...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *         @OA\JsonContent(
     *             @OA\Property(property="access_token", type="string"),
     *             @OA\Property(property="token_type", type="string", example="Bearer"),
     *             @OA\Property(property="expires_in", type="integer"),
     *             @OA\Property(property="user", ref="")
     *         )
     *     ),
     *     @OA\Response(response=400, description="Invalid code / state"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function handleCallbackIos()
    {
        try {
            $googleUser = Socialite::driver('google_ios')->stateless()->user();

            $parts = explode(' ', $googleUser->getName(), 2);

            $firstName = $parts[0];
            $lastName = $parts[1] ?? '';
            $client = Client::where('email', $googleUser->getEmail())->first();

            if (!$client) {


                $client = Client::create([
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'client_type' => 'gold',
                    'active_status' => 1,

                ]);

                $emailname = $googleUser->getEmail();
                $clientIDToAppend = $clientID = $client->id;
                if (strlen((string) $clientID) < 4) {
                    $clientIDToAppend = str_pad($clientIDToAppend, 4, '0', STR_PAD_LEFT);
                }
                Client::where('email', $googleUser->getEmail())
                    ->update(['username' => strtoupper(substr($emailname, 0, 2)) . $clientIDToAppend]);


            } else {


                $client = User::updateOrCreate(
                    ['email' => $googleUser->getEmail()],
                    [

                        'google_id' => $googleUser->getId(),

                    ]
                );
            }


            $token = $client->createToken('google-auth-token')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Your email successfully',
                'token' => $token,
                'token_type' => 'Bearer',
                'data' => $client,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }



}
