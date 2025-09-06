<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Client;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Validator;

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
     *             @OA\Property(property="password", type="string", example="Enter Password")     *            
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
        // dd($user);
        if(!$user){
             return response()->json(['status' => false, 'message' => 'User account not found',], 403);
        }
        if (!$user->active_status) {
            return response()->json(['status' => false, 'message' => 'User account is inactive',], 403);
        }
        // if (!$user || !Hash::check($request->password, $user->password)) {
        //     return response()->json(['status' => false, 'message' => 'Invalid credentials'], 401);
        // }

        // Generate new Sanctum token
        $token = $user->createToken('api-token')->plainTextToken;
        //$token = $user->createToken('browser-extension')->plainTextToken;

        return response()->json([
            'status' => true,
            'message' => 'Login successful',
            'token' => $token,
            'token_type' => 'Bearer',
          //  'expires_in' => auth()->factory()->getTTL()*60,
            'data' => $user,
        ]);
    }

 

    // Logout API
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['status'=>true,'message' => 'Logout successful']);
    }
	
 
	
}
