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
     *     path="/api/business/saveProfileLogo",
     *     tags={"Profile"},
     *     summary="Upload business logo and profile picture",
     *     description="Uploads the business logo and profile picture for the authenticated user. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"logo","profile_pic"},
     *                 @OA\Property(property="logo", type="string", format="binary", description="Business logo file"),
     *                 @OA\Property(property="profile_pic", type="string", format="binary", description="Profile picture file")
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
     *                 @OA\Property(property="logo", type="array", @OA\Items(type="string", example="The logo must be an image.")),
     *                 @OA\Property(property="profile_pic", type="array", @OA\Items(type="string", example="The profile_pic must be an image."))
     *             )
     *         )
     *     )
     * )
     */
   public function saveProfileLogo(Request $request)
	{
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
			$client = Client::find($user->id);
			$id = $request->input('business_id');
			$validator = Validator::make($request->all(), [
				'image' => 'mimes:jpeg,jpg,png,svg|max:2048',
				'profile_pic' => 'mimes:jpeg,jpg,png,svg|max:2048'
			], [
				'profile_pic.dimensions' => 'Please upload Banner of given size -> [Minimum Height:319px] &amp; [Minimum Width:1137px].',
				'image.dimensions' => 'Please upload profile logo of given size -> .[Maximum Height:150px] &amp; [Maximum Width:300px]'
			]);

			if ($validator->fails()) {
				$errorsBag = $validator->getMessageBag()->toArray();
				return response()->json(['status' => 1, 'errors' => $errorsBag], 400);
			}

			try {
				if ($request->hasFile('image')) {
					$image = [];
					$filePath = getFolderStructure();
					$file = $request->file('image');
					$filename = str_replace(' ', '_', $file->getClientOriginalName()); // $file->getClientOriginalName();
					$destinationPath = public_path($filePath);
					$nameArr = explode('.', $filename);
					$ext = array_pop($nameArr);
					$name = implode('_', $nameArr);
					if (file_exists($destinationPath . '/' . $filename)) {
						$filename = $name . "_" . time() . '.' . $ext;
					}

					$imagePath = $file->getPathname();
					$targetWidth = 250;
					$targetHeight = 141;
					$quality = 75;

					$ext = strtolower($file->getClientOriginalExtension());

					// Load original image
					if ($ext === 'jpeg' || $ext === 'jpg') {
						$srcImage = imagecreatefromjpeg($imagePath);
					} elseif ($ext === 'png') {
						$srcImage = imagecreatefrompng($imagePath);
					} else if ($ext === 'svg') {
						$file->move($destinationPath, $filename);
					}
					if ($ext === 'jpeg' || $ext === 'jpg' || $ext === 'png') {

						list($width, $height) = getimagesize($imagePath);

						$newImage = imagecreatetruecolor($targetWidth, $targetHeight);

						imagecopyresampled(
							$newImage,
							$srcImage,
							0,
							0,
							0,
							0,
							$targetWidth,
							$targetHeight,
							$width,
							$height
						);


						$outputPath = public_path($filePath . "/" . $filename);
						imagejpeg($newImage, $outputPath, $quality);
						imagedestroy($srcImage);
						imagedestroy($newImage);
					}

					$image['large'] = array(
						'name' => $filename,
						'alt' => $filename,
						'width' => '',
						'height' => '',
						'src' => $filePath . "/" . $filename
					);

					if (!empty($client->logo)) {
						$oldImages = unserialize($client->logo);
					}
					$client->logo = serialize($image);
				}

				// PROFILE PICTURE
				// ***************
				if ($request->hasFile('profile_pic')) {
					$image = [];
					$filePath = getFolderStructure();

					$file = $request->file('profile_pic');
					$filename = str_replace(' ', '_', $file->getClientOriginalName());
					$destinationPath = public_path($filePath);
					$nameArr = explode('.', $filename);
					$ext = array_pop($nameArr);
					$name = implode('_', $nameArr);
					if (file_exists($destinationPath . '/' . $filename)) {
						$filename = $name . "_" . time() . '.' . $ext;
					}				 

					$imagePath = $file->getPathname();
					$targetWidth = 1200;
					$targetHeight = 180; 
					$quality = 75;

					$ext = strtolower($file->getClientOriginalExtension());

					// Load original image
					if ($ext === 'jpeg' || $ext === 'jpg') {
						$srcImage = imagecreatefromjpeg($imagePath);
					} elseif ($ext === 'png') {
						$srcImage = imagecreatefrompng($imagePath);
					} else if ($ext === 'svg') {
						$file->move($destinationPath, $filename);
					}
					if ($ext === 'jpeg' || $ext === 'jpg' || $ext === 'png') {

						// Get original size
						list($width, $height) = getimagesize($imagePath);

						// Create new blank image
						$newImage = imagecreatetruecolor($targetWidth, $targetHeight);

						// Resize image
						imagecopyresampled(
							$newImage,
							$srcImage,
							0,
							0,
							0,
							0,
							$targetWidth,
							$targetHeight,
							$width,
							$height
						);

						// Save compressed image
						$outputPath = public_path($filePath . "/" . $filename);

						imagejpeg($newImage, $outputPath, $quality);  // For PNG, use imagepng()

						// Cleanup
						imagedestroy($srcImage);
						imagedestroy($newImage);

					}

					$image['large'] = array(
						'name' => $filename,
						'alt' => $filename,
						'width' => '',
						'height' => '',
						'src' => $filePath . "/" . $filename
					);

					if (!empty($client->profile_pic)) {
						$oldProfileImages = unserialize($client->profile_pic);
					}
					$client->profile_pic = serialize($image);
				}


				if ($client->save()) {

					$data['status'] = 1;
					$data['message'] = "Profile Logo updated successfully !";
				} else {
					$data['status'] = false;
					$data['message'] = "Profile Logo could not be successfully, Please try again !";
				}

			} catch (Exception $e) {
				$data['status'] = false;
				$data['message'] = $e->getMessage();
			}
			$clients = Client::find($user->id);
			if($clients){
			$image = '#';
			$profile_pic = '#';
			if(!empty($clients->logo)){
				$logo = unserialize($clients->logo);			 							
				$image = $logo['large']['src'];
			}
			if(!empty($client->profile_pic)){
				$profilepic = unserialize($client->profile_pic);
				$profile_pic = $profilepic['large']['src'];
			}
			$data['client_details'] = array(
					'logo'=>$image,
					'profile_pic'=>$profile_pic,
			);
		}
		echo json_encode($data);
	}

    /**
     * @OA\Delete(
     *     path="/api/business/profileLogo/logoDel",
     *     tags={"Profile"},
     *     summary="Delete business logo",
     *     description="Deletes the current business logo of the authenticated user. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logo deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Business logo deleted successfully")
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
     *             @OA\Property(property="message", type="string", example="No logo found for this user")
     *         )
     *     )
     * )
     */
    public function deleteLogo(Request $request)
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


            $delet_data = Client::findOrFail($user->id);


            if ($delet_data->logo != '') {
                $image = unserialize($delet_data->logo);

                $large = '' . $image['large']['src'];
                if (!empty($image['thumbnail']['src'])) {
                    $thumbnail = '' . $image['thumbnail']['src'];
                    if (file_exists($thumbnail)) {
                        unlink($thumbnail);
                    }
                }
                if (file_exists($large)) {
                    unlink($large);
                }
            }

            $edit_data = array('logo' => "", );
            $del = Client::where('id', $user->id)->update($edit_data);
            $user = Client::find($user->id);
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
	 * @OA\Delete(
	 *     path="/api/business/profileLogo/profilePicDel",
	 *     tags={"Profile"},
	 *     summary="Delete business profile picture",
	 *     description="Deletes the current profile picture of the authenticated user. Requires Bearer token.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\Response(
	 *         response=200,
	 *         description="Profile picture deleted successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Profile picture deleted successfully")
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
	 *         description="Profile picture not found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No profile picture found for this user")
	 *         )
	 *     )
	 * )
	 */
    public function deleteProfilePic(Request $request)
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


            $delet_data = Client::findOrFail($user->id);


            if ($delet_data->profile_pic != '') {
                $image = unserialize($delet_data->profile_pic);

                $large = '' . $image['large']['src'];
                if (!empty($image['thumbnail']['src'])) {
                    $thumbnail = '' . $image['thumbnail']['src'];
                    if (file_exists($thumbnail)) {
                        unlink($thumbnail);
                    }
                }
                if (file_exists($large)) {
                    unlink($large);
                }
            }

            $edit_data = array('profile_pic' => "", );
            $del = Client::where('id', $user->id)->update($edit_data);
            $user = Client::find($user->id);
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
		if(!Auth::guard('sanctum')->check()) {
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
		 
		$client = Client::find($user->id);
		 

				if(!empty($client->pictures)){
                    $picture = unserialize($client->pictures);
                 	$picture['large']['name'] = '';
                    for($i=0;$i<12;$i++){
                    if(!isset($picture[$i])){
                    	$picture[$i]['large']['name'] = '';
                    }
                    }
				}
					for($i=0;$i<12;$i++){
						if(isset($picture[$i]['large']['src'])&&!empty($picture[$i]['large']['src'])){
						$data[$i][$picture[$i]['large']['src']] = $picture[$i]['large']['src'];

						}
					}
		 echo json_encode($data);

	}
	/**
	 * @OA\Post(
	 *     path="/api/business/save-gallery",
	 *     tags={"Gallery"},
	 *     summary="Upload a new gallery picture",
	 *     description="Upload a gallery picture for the authenticated user's business.",
	 *     security={{"bearerAuth":{}}},
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\MediaType(
	 *             mediaType="multipart/form-data",
	 *             @OA\Schema(
	 *                 required={"image"},
	 *                 @OA\Property(
	 *                     property="image",
	 *                     type="string",
	 *                     format="binary",
	 *                     description="Gallery image file"
	 *                 ),
	 *                 @OA\Property(
	 *                     property="title",
	 *                     type="string",
	 *                     example="Office Front View"
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=200,
	 *         description="Gallery picture uploaded successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Gallery picture uploaded successfully."),
	 *             @OA\Property(property="data", type="object",
	 *                 @OA\Property(property="id", type="integer", example=1),
	 *                 @OA\Property(property="image_url", type="string", example="https://api.quickdials.com/storage/gallery/office1.jpg"),
	 *                 @OA\Property(property="title", type="string", example="Office Front View")
	 *             )
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
	 *             @OA\Property(property="message", type="string", example="The given data was invalid."),
	 *             @OA\Property(property="errors", type="object",
	 *                 @OA\Property(property="image", type="array",
	 *                     @OA\Items(type="string", example="The image field is required.")
	 *                 )
	 *             )
	 *         )
	 *     )
	 * )
	 */

	public function saveGallary(Request $request)
	{
		 if(!Auth::guard('sanctum')->check()) {
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
			$client = Client::find($user->id);
			 
			$image = [];
			if (!empty($client->pictures)) {
				$oldImages = unserialize($client->pictures);
			}
			$filePath = getFolderStructure();

			for ($i = 0; $i < 12; $i++) {
				if ($request->hasFile('image' . ($i + 1))) {

					$file = $request->file('image' . ($i + 1));

					$filename = str_replace(' ', '_', $file->getClientOriginalName());
					$destinationPath = public_path($filePath);
					$nameArr = explode('.', $filename);
					$ext = array_pop($nameArr);
					$name = implode('_', $nameArr);
					if (file_exists($destinationPath . '/' . $filename)) {
						$filename = $name . "_" . time() . '.' . $ext;
					}
				 


					$imagePath = $file->getPathname();
					$targetWidth = 800;   
					$targetHeight = 600;  
					$quality = 75;        

					$ext = strtolower($file->getClientOriginalExtension());

					// Load original image
					if ($ext === 'jpeg' || $ext === 'jpg') {
						$srcImage = imagecreatefromjpeg($imagePath);
					} elseif ($ext === 'png') {
						$srcImage = imagecreatefrompng($imagePath);
					} elseif ($ext === 'svg') {
						$file->move($destinationPath, $filename);
					}
					if ($ext === 'jpeg' || $ext === 'jpg' || $ext === 'png') {

						// Get original size
						list($width, $height) = getimagesize($imagePath);

						// Create new blank image
						$newImage = imagecreatetruecolor($targetWidth, $targetHeight);

						// Resize image
						imagecopyresampled(
							$newImage,
							$srcImage,
							0,
							0,
							0,
							0,
							$targetWidth,
							$targetHeight,
							$width,
							$height
						);

						// Save compressed image
						$outputPath = public_path($filePath . "/" . $filename);

						imagejpeg($newImage, $outputPath, $quality);  // For PNG, use imagepng()

						// Cleanup
						imagedestroy($srcImage);
						imagedestroy($newImage);

					}

					$image[$i]['large'] = array(
						'name' => $filename,
						'alt' => $filename,
						'width' => '',
						'height' => '',
						'src' => $filePath . "/" . $filename
					);
				} else if (isset($_FILES['image' . ($i + 1)]) && $_FILES['image' . ($i + 1)]['size'] == 0) {
				} else {
					if (isset($oldImages)) {
						if (array_key_exists($i, $oldImages)) {
							$image[$i] = $oldImages[$i];
						}
						unset($oldImages[$i]);
					}
				}
			}
			if (count($image) > 0) {
				$client->pictures = serialize($image);
			} else {
				$client->pictures = '';
			}

			if ($client->save()) {
				if (isset($oldImages)) {
					foreach ($oldImages as $oldImage) {
						try {
							if (!unlink(public_path($oldImage['large']['src'])))
								throw new Exception("Old files not deleted...");
							 
						} catch (Exception $e) {
							echo $e->getMessage();
						}
					}
				}

				$data['status'] = true;
				$data['message'] = "Gallery Successfully Save!";
			}else{
				$data['status'] = false;
				$data['message'] = "Gallery not Successfully save!";

			}
					 
			$client = Client::find($user->id);
				if(!empty($client->pictures)){
                    $picture = unserialize($client->pictures);
                 	$picture['large']['name'] = '';
                    for($i=0;$i<12;$i++){
                    if(!isset($picture[$i])){
                    	$picture[$i]['large']['name'] = '';
                    }
                    }
				}
				for($i=0;$i<12;$i++){
					if(isset($picture[$i]['large']['src'])&&!empty($picture[$i]['large']['src'])){
					$data[$i][$picture[$i]['large']['src']] = $picture[$i]['large']['src'];

					}
				}
		 echo json_encode($data);
		
	}
}
