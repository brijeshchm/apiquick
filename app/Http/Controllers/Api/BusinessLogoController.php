<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Client\Client; //model
use Validator;
use Exception;

class BusinessLogoController extends Controller
{
	protected $danger_message = '';
	protected $success_message = '';
	protected $warning_message = '';
	protected $info_message = '';
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
	 *     path="/api/business/profile-logo",
	 *     tags={"Profile"},
	 *     summary="Get business profile logo",
	 *     description="Fetches the business profile logo of the authenticated user. Requires Bearer token.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="Profile logo retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="logo_url", type="string", format="uri", example="https://yourdomain.com/storage/logos/profile123.png")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="Logo not found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="Profile logo not found")
	 *         )
	 *     )
	 * )
	 */
	public function getProfileLogo(Request $request)
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


			if (!empty($user->profile_pic)) {
				$profile_pic = unserialize($user->profile_pic);
			} else {
				$profile_pic = "";
			}
			if (!empty($user->logo)) {
				$logo = unserialize($user->logo);
			} else {
				$logo = "";
			}

			$data['userDetails'] = array(
				'client_id' => $user->id,
				'username' => $user->username,
				'business_slug' => $user->business_slug,
				'profile_pic' => $profile_pic,
				'logo' => $logo,
				'active_status' => $user->active_status,
			);
			$data['business_id'] = $user->id;
			return response()->json([
				'success' => true,
				'data' => $data,
			], 200);

		} catch (\Exception $e) {
			return response()->json([
				'success' => false,
				'message' => 'Failed to retrieve users: ' . $e->getMessage(),
			], 500);
		}
	}

	/**
	 * @OA\Post(
	 *     path="https://www.quickdials.com/api/business/saveProfileLogo",
	 *     tags={"Profile"},
	 *     summary="Upload business logo and profile picture",
	 *     description="Uploads the business logo and profile picture for the authenticated user. Requires Bearer token.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\MediaType(
	 *             mediaType="multipart/form-data",
	 *             @OA\Schema(
	 *                 required={"logo", "profile_pic"},
	 *                 @OA\Property(
	 *                     property="business_id",
	 *                     type="string",
	 *                     format="binary",
	 *                     description="business_id"
	 *                 ),
	 *                 @OA\Property(
	 *                     property="logo",
	 *                     type="string",
	 *                     format="binary",
	 *                     description="Business logo file"
	 *                 ),
	 *                 @OA\Property(
	 *                     property="profile_pic",
	 *                     type="string",
	 *                     format="binary",
	 *                     description="Profile picture file"
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Files uploaded successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="logo_url", type="string", format="uri", example="https://yourdomain.com/storage/logos/business.png"),
	 *             @OA\Property(property="profile_pic_url", type="string", format="uri", example="https://yourdomain.com/storage/profiles/profile.png")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation error",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="The logo field is required."),
	 *             @OA\Property(
	 *                 property="errors",
	 *                 type="object",
	 *                 @OA\Property(
	 *                     property="logo",
	 *                     type="array",
	 *                     @OA\Items(type="string", example="The logo must be an image.")
	 *                 ),
	 *                 @OA\Property(
	 *                     property="profile_pic",
	 *                     type="array",
	 *                     @OA\Items(type="string", example="The profile_pic must be an image.")
	 *                 )
	 *             )
	 *         )
	 *     )
	 * )
	 */

	public function saveProfileLogo()
	{

	}
	/**
	 * @OA\Delete(
	 *     path="https://www.quickdials.com/api/business/profileLogo/logoDel/{business_id}",
	 *     tags={"Profile"},
	 *     summary="Delete business logo",
	 *     description="Deletes the current business logo of the authenticated user", *     
	 *
	 *      
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Logo deleted successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Business logo deleted successfully")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=404,
	 *         description="Logo not found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No logo found for this user")
	 *         )
	 *     )
	 * )
	 */

	public function deleteLogo($business_id)
	{

	}
	/**
	 * @OA\Delete(
	 *     path="https://www.quickdials.com/api/business/profileLogo/profilePicDel/{business_id}",
	 *     tags={"Profile"},
	 *     summary="Delete business logo",
	 *     description="Deletes the current business logo of the authenticated user", *     
	 *
	 *      
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Logo deleted successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Business logo deleted successfully")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=404,
	 *         description="Logo not found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No logo found for this user")
	 *         )
	 *     )
	 * )
	 */
	public function deleteProfilePic($business_id)
	{

	}





	/**
	 * @OA\Get(
	 *     path="/api/business/get-gallery-pictures",
	 *     tags={"Gallery"},
	 *     summary="Get business gallery pictures",
	 *     description="Fetch all gallery pictures uploaded by the authenticated user's business.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="Gallery pictures retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=1),
	 *                     @OA\Property(property="image_url", type="string", example="https://api.quickdials.com/storage/gallery/image1.jpg"),
	 *                     @OA\Property(property="title", type="string", example="Office Front View"),
	 *                     @OA\Property(property="created_at", type="string", example="2025-09-04 12:30:00")
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
public function getGalleryPictures(Request $request)
{
    // 🔐 Sanctum authentication
    if (!Auth::guard('sanctum')->check()) {
        return response()->json([
            'status'  => false,
            'message' => 'Unauthenticated: Token is missing or invalid',
            'error'   => 'token_missing_or_invalid'
        ], 401);
    }

    $user = auth('sanctum')->user();
    if (!$user) {
        return response()->json([
            'status'  => false,
            'message' => 'Unauthenticated: Token is missing or invalid',
            'error'   => 'token_missing_or_invalid'
        ], 401);
    }

    $client = Client::find($user->id);

    $data = [];
    $pictures = [];

    // ✅ Safely unserialize pictures
    if (!empty($client->pictures)) {
        $pictures = @unserialize($client->pictures);

        if (!is_array($pictures)) {
            $pictures = [];
        }
    }

    // ✅ Always return exactly 12 slots
    for ($i = 0; $i < 12; $i++) {
        $data[$i] = '';

        if (
            isset($pictures[$i]['large']['src']) &&
            !empty($pictures[$i]['large']['src'])
        ) {
            $data[$i] = $pictures[$i]['large']['src'];
        }
    }

    // ✅ Add business_id separately
    $response = [
        'pictures'    => $data,
        'business_id' => $user->id
    ];

    return response()->json([
        'status'  => true,
        'message' => 'Successfully',
        'data'    => $response
    ], 200);
}


	// public function getGalleryPictures(Request $request)
	// {
	// 	if (!Auth::guard('sanctum')->check()) {
	// 		return response()->json([
	// 			'status' => false,
	// 			'message' => 'Unauthenticated: Token is missing or invalid',
	// 			'error' => 'token_missing_or_invalid'
	// 		], 401);
	// 	}

	// 	// Check if user is active
	// 	$user = auth('sanctum')->user();
	// 	if (!$user) {
	// 		return response()->json([
	// 			'status' => false,
	// 			'message' => 'Unauthenticated: Token is missing or invalid',
	// 			'error' => 'token_missing_or_invalid'
	// 		], 401);
	// 	}

	// 	$client = Client::find($user->id);


	// 	if (!empty($client->pictures)) {
	// 		$picture = unserialize($client->pictures);
	// 		$picture['large']['name'] = '';
	// 		for ($i = 0; $i < 12; $i++) {
	// 			if (!isset($picture[$i])) {
	// 				$picture[$i]['large']['name'] = '';
	// 			}
	// 		}
	// 	}
	// 	for ($i = 0; $i < 12; $i++) {
	// 		if (isset($picture[$i]['large']['src']) && !empty($picture[$i]['large']['src'])) {
	// 			$data[$i] = $picture[$i]['large']['src'];
	// 			//$data[$i][$picture[$i]['large']['src']] = $picture[$i]['large']['src'];

	// 		}
	// 	}
	// 	$data['business_id'] = $user->id;
	// 	return response()->json([
	// 		'status' => true,
	// 		'message' => "Successfully",
	// 		'data' => $data,

	// 	], 200);

	// }
	/**
	 * @OA\Post(
	 *     path="https://www.quickdials.com/api/business/save-gallery",
	 *     tags={"Gallery"},
	 *     summary="Upload a new gallery picture",
	 *     description="Upload a gallery picture for the authenticated user's business.",
	 *      
	 *
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\MediaType(
	 *             mediaType="multipart/form-data",
	 *             @OA\Schema(
	 *                 required={"business_id","image"},
	 *
	 *                 @OA\Property(
	 *                     property="business_id",
	 *                     type="integer",
	 *                     example=12,
	 *                     description="Business ID"
	 *                 ),
	 *
	 *                 @OA\Property(
	 *                     property="image",
	 *                     type="string",
	 *                     format="binary",
	 *                     description="Gallery image file"
	 *                 ),
	 *
	 *                 @OA\Property(
	 *                     property="title",
	 *                     type="string",
	 *                     example="Office Front View"
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Gallery picture uploaded successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Gallery picture uploaded successfully."),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="object",
	 *                 @OA\Property(property="id", type="integer", example=1),
	 *                 @OA\Property(property="image_url", type="string", example="https://www.quickdials.com/uploads/gallery/office1.jpg"),
	 *                 @OA\Property(property="title", type="string", example="Office Front View")
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation error",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
	 *             @OA\Property(
	 *                 property="errors",
	 *                 type="object",
	 *                 @OA\Property(
	 *                     property="image",
	 *                     type="array",
	 *                     @OA\Items(type="string", example="The image field is required.")
	 *                 )
	 *             )
	 *         )
	 *     )
	 * )
	 */

	public function saveGallary()
	{

	}

/**
	 * @OA\Post(
	 *     path="https://www.quickdials.com/api/business/save-pictures",
	 *     tags={"Gallery"},
	 *     summary="Upload Picture",
	 *     description="Upload a Picture picture for the authenticated user's business.",
	 *      
	 *
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\MediaType(
	 *             mediaType="multipart/form-data",
	 *             @OA\Schema(
	 *                 required={"business_id","image"},
	 *
	 *                 @OA\Property(
	 *                     property="business_id",
	 *                     type="integer",
	 *                     example=12,
	 *                     description="Business ID"
	 *                 ),
	 *
	 *                 @OA\Property(
	 *                     property="image[]",
	 *                     type="string",
	 *                     format="binary",
	 *                     description="Gallery image file"
	 *                 ),
	 *                 @OA\Property(
	 *                     property="delete_images[]",
	 *                     type="string",
	 *                     format="binary",
	 *                     description="pass image path"
	 *                 ),
	 *
	 *                 @OA\Property(
	 *                     property="title",
	 *                     type="string",
	 *                     example="Office Front View"
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Gallery picture uploaded successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Gallery picture uploaded successfully."),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="object",
	 *                 @OA\Property(property="id", type="integer", example=1),
	 *                 @OA\Property(property="image_url", type="string", example="https://www.quickdials.com/uploads/gallery/office1.jpg"),
	 *                 @OA\Property(property="title", type="string", example="Office Front View")
	 *             )
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthenticated",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation error",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
	 *             @OA\Property(
	 *                 property="errors",
	 *                 type="object",
	 *                 @OA\Property(
	 *                     property="image",
	 *                     type="array",
	 *                     @OA\Items(type="string", example="The image field is required.")
	 *                 )
	 *             )
	 *         )
	 *     )
	 * )
	 */

	public function savePictures()
	{
 		dd('https://www.quickdials.com/api/business/save-pictures');
	}

}
