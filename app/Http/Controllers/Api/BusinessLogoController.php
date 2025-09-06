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
	public function profileLogo(Request $request)
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
		$data['client'] = Client::find($user->id);
		echo json_encode($data);
	}

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

	public function logoDel($id)
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
		$delet_data = Client::findOrFail($user->id);	 

		if ($delet_data->logo != '') {
			$image = unserialize($delet_data->logo);

			$large = '' . $image['large']['src'];
		
			if (file_exists($large)) {
				unlink($large);
			}
		}

		$edit_data = array('logo' => "", );
		$del = Client::where('id', $user->id)->update($edit_data);
		if($del){
			$data['status'] = true;
			$data['message'] = "Successfully Deleted!";

		}else{
			$data['status'] = true;
			$data['message'] = "Not deleted logo!";
			
		}
		echo json_encode($data);
	}


	public function profilePicDel($id)
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
		$delet_data = Client::findOrFail($user->id);
		 
		$client = Client::find($user->isDirty);
		if ($delet_data->profile_pic != '') {
			$image = unserialize($delet_data->profile_pic);
			$large = '' . $image['large']['src'];		 
			if (file_exists($large)) {
				unlink($large);
			}
		}
		$edit_data = array('profile_pic' => "", );
		$del = Client::where('id', $id)->update($edit_data);
		if($del){
			$data['status'] = true;
			$data['message'] = "Successfully Deleted!";

		}else{
			$data['status'] = true;
			$data['message'] = "Not deleted logo!";
			
		}
		echo json_encode($data);

	}
	public function uploadPictures(Request $request)
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
