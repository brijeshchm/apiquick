<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Models\Client\Client;
use Validator;
use App\Models\Zone;
use App\Models\Citieslists;
use App\Models\AssignedZone;
use App\Models\State;

class BusinessLocationController extends Controller
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

	public function locationInformation(Request $request)
	{
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
	 
		$data['clientDetails'] = Client::find($user->id);

		$search = [];
		if ($request->has('search')) {
			$data['search'] = $request->input('search');
		}
	echo json_encode($data);	

		 
	}
	public function saveBusinessLocation(Request $request, $id)
	{
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

			if ($request->input('zone_id') == "Other") {


				$validator = Validator::make($request->all(), [
					'city_id' => 'required|max:25',
					//'other' 	=> 'required|regex:/^[\pL\s\-]+$/u|min:3|max:32',	
					'other' => 'required|min:3|max:32|regex:/^(?!.*(.)\1{3,}).+$/',
				]);


			} else {
				$validator = Validator::make($request->all(), [
					'city_id' => 'required|max:255',
					'zone_id' => 'required|max:255',

				]);
			}

			if ($validator->fails()) {
				$errorsBag = $validator->getMessageBag()->toArray();
				return response()->json(['status' => 1, 'errors' => $errorsBag], 400);
			}
			$assignedZone = new AssignedZone;
			$assignedZone->city_id = $request->input('city_id');
			if ($request->input('zone_id') == "Other") {
				$checkZone = Zone::where('zone', $request->input('other'))->where('city_id', $request->input('city_id'))->first();
				if (empty($checkZone)) {
					$zone = new Zone;
					$zone->city_id = $request->input('city_id');
					$zone->zone = ucfirst($request->input('other'));
					$zone->save();
					$zone_id = $zone->id;
				} else {
					$zone_id = $checkZone->id;
				}

			} else {
				$zone_id = $request->input('zone_id');
			}
			$assignedZone->zone_id = $zone_id;
			$assignedZone->client_id = $request->input('client_id');

			$checkAssignedZone = AssignedZone::where('client_id', $request->input('client_id'))->where('zone_id', $zone_id)->where('city_id', $request->input('city_id'))->first();

			if (empty($checkAssignedZone)) {
				if ($assignedZone->save()) {

					$data['status'] = true;
					$data['message'] = "Business Location updated successfully !";
				} else {
					$data['status'] = 0;
					$data['message'] = "Business Location could not be successfully, Please try again !";
				}
			} else {
				$data['status'] = 0;
				$data['message'] = "Already exists <strong>" . $request->input('other') . "</strong> Please add right zone !";
			}

		echo json_encode($data);	

	}

	public function saveLocationInformation(Request $request)
	{
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

			$client = Client::find($request->input('business_id'));
			$id = $request->input('business_id');
			$messages = ['mobile.regex' => 'Mobile number cannot start with 0.'];
			$validator = Validator::make($request->all(), [
				'business_name' => 'required|unique:clients,business_name,' . $id . ',id,city,' . $request->input('city'),
				'landmark' => 'regex:/[a-zA-z ]$/',
				'city' => 'required|regex:/[a-zA-z ]+$/',
				'state' => 'required|regex:/[a-zA-z ()]+$/',
				'country' => 'required|regex:/[a-zA-z ]+$/',

			]);
			if ($validator->fails()) {
				$errorsBag = $validator->getMessageBag()->toArray();
				return response()->json(['status' => true, 'errors' => $errorsBag], 400);
			}
			$string = $request->input('business_name');
			$string = filter_var($string, FILTER_SANITIZE_STRING);
			$string = preg_replace('/[^A-Za-z0-9]/', ' ', $string);
			$string = preg_replace('/\s+/', ' ', str_replace('&', '', trim($string)));

			$client->business_name = $string;
			$client->address = $request->input('address');
			$client->landmark = $request->input('landmark');
			$client->business_city = $request->input('city');
			$client->state = $request->input('state');
			$client->country = $request->input('country');

			if ($client->save()) {
				$data['clientDetails'] = Client::find($id);
				$data['status'] = true;
				$data['message'] = "Location Information Updated Successfully";
				 
			} else {
				$data['status'] = false;
				$data['message'] = "Location Information not assigned";
				 
			}
			echo json_encode($data);	


	}




	public function businessLocation(Request $request)
	{
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
		 
		$data['clientDetails'] = Client::find($user->id);
		$data['search'] = [];
		if ($request->has('search')) {
			$data['search'] = $request->input('search');
		}
		$data['statesis'] = State::get();
		echo json_encode($data);	

	}
}
