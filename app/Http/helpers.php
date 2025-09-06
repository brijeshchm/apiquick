<?php
/**
 * CONTAINS HELPER FUNCTIONS
 */


// SENDING SMS AND IT'S CONFIGURATION
// **********************************
function sendSMS($sendto, $message, $tempid = null)
{
	$username = 't1quickdialssms';
	$password = '42308595';
	$sender = 'CCAMPS';
	$sendto = $sendto;
	//	$tempid = $tempid;
	$tempid = '1707161786775524106';
	$message = str_replace(' ', '%20', $message);
	//	$url = 'http://nimbusit.co.in/api/swsendSingle.asp';
	$url = 'http://nimbusit.co.in/api/swsend.asp';
	//	$data = "username=$username&password=$password&sender=$sender&sendto=$sendto&message=$message&entityID=1701160344973814570";

	$data = "username=$username&password=$password&sender=$sender&sendto=$sendto&entityID=1701160344973814570&templateID=$tempid&message=$message";


	$objURL = curl_init($url);
	curl_setopt($objURL, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($objURL, CURLOPT_POST, 1);
	curl_setopt($objURL, CURLOPT_POSTFIELDS, $data);
	$retval = trim(curl_exec($objURL));
	curl_close($objURL);
	return $retval;
}

// SENDING SMS AND IT'S CONFIGURATION
// **********************************
function sendSMSoldd($sendto, $message)
{
	$username = 't1quickdialssms';
	$password = '42308595';
	$sender = 'LEADEG';
	$sendto = $sendto;
	$message = str_replace(' ', '%20', $message);
	//$url = 'http://nimbusit.co.in/api/swsendSingle.asp';
	$url = 'http://nimbusit.co.in/api/swsend.asp';

	$data = "username=$username&password=$password&sender=$sender&sendto=$sendto&message=$message";

	$objURL = curl_init($url);
	curl_setopt($objURL, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($objURL, CURLOPT_POST, 1);
	curl_setopt($objURL, CURLOPT_POSTFIELDS, $data);
	$retval = trim(curl_exec($objURL));
	curl_close($objURL);
}

// SLUG GENERATOR FOR CLIENTS
// **************************
function generate_slug($slug = null)
{
	if (is_null($slug)) {
		return null;
	}
	$slug = explode(" ", $slug);
	$slug = array_map('trim', $slug);
	$slug = array_map('remove_splchars', $slug);
	$slug = array_map('strtolower', $slug);
	$slug = implode("-", $slug);
	return $slug;
}

// INVERSE SLUG GENERATOR FOR CLIENTS
// **********************************
function inverse_generate_slug($slug = null)
{
	if (is_null($slug)) {
		return null;
	}
	$slug = preg_replace('/--/', '-&-', $slug);
	$slug = preg_replace('/-/', ' ', $slug);
	return $slug;
}




function getCity()
{
	return $cities = App\Models\City::where('popular','1')->get();
}


function get_time($time)
{

	$start_date = date('Y-m-d H:i:s');

	$diff = abs(strtotime($start_date) - $time);

	$totalyear = floor($diff / (365 * 60 * 60 * 24));
	$totalmonths = floor(($diff - $totalyear * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
	$days = floor(($diff - $totalyear * 365 * 60 * 60 * 24 - $totalmonths * 30 * 60 * 60 * 24) / (60 * 60 * 24));



	$create_time = $time;
	$current_time = time();
	$dtCurrent = DateTime::createFromFormat('U', $current_time);
	$dtCreate = DateTime::createFromFormat('U', $create_time);
	$diff = $dtCurrent->diff($dtCreate);

	if ($days < 1 && $totalmonths == 0) {
		$interval = $diff->format("%h hrs %i minutes");
		$interval = preg_replace('/(^0| 0) (hrs|minutes)/', '', $interval);

	} else if ($days > 0 && $totalmonths == 0) {
		$interval = $diff->format("%d days %h hrs");
		$interval = preg_replace('/(^0| 0) (days|hrs)/', '', $interval);
	} else if ($totalmonths > 0 && $days > 1 && $totalyear == '0') {

		$interval = $diff->format("%m months %d days");
		$interval = preg_replace('/(^0| 0) (months|days)/', '', $interval);

	} else if ($totalmonths >= 12 && $totalyear > 0) {
		$interval = $diff->format("%y years %m months");
		$interval = preg_replace('/(^0| 0) (years|months)/', '', $interval);
	} else {

		$interval = $diff->format("%h hours %i minutes");
		$interval = preg_replace('/(^0| 0) (hours|minutes)/', '', $interval);
	}

	echo $interval;



}
// SPECIAL CHARACTERS REMOVER
// **************************
function remove_splchars($str)
{
	return preg_replace("/[^a-zA-Z0-9-.]/", "", $str);
}

// FOLDER STRUCTURE GENERATOR
// **************************
function getFolderBlogStructure()
{
	try {
		$partial_str = '';
		$day = date('j');
		$week = '';
		if ($day < 11) {
			$week = 'week_1';
		} else if ($day >= 11 && $day < 21) {
			$week = 'week_2';
		} else if ($day >= 21) {
			$week = 'week_3';
		}
		$partial_str = 'uploads/images/' . date('Y') . '/' . date('m') . '/' . $week;
		$structure = public_path($partial_str);
		if (file_exists($structure)) {
			return $partial_str;
		} else {
			if (mkdir($structure, 0755, true)) {
				return $partial_str;
			} else {
				throw new Exception("Folder structure not found.\nUnable to create folder structure.");
			}
		}
	} catch (Exception $e) {
		return $e->getMessage();
	}
}


function getFolderCourseStructure()
{
	try {
		$partial_str = '';
		$day = date('j');
		$week = '';
		if ($day < 11) {
			$week = 'week_1';
		} else if ($day >= 11 && $day < 21) {
			$week = 'week_2';
		} else if ($day >= 21) {
			$week = 'week_3';
		}
		$partial_str = 'upload/crs/' . date('Y') . '/' . date('m') . '/' . $week;
		$structure = public_path($partial_str);
		if (file_exists($structure)) {
			return $partial_str;
		} else {
			if (mkdir($structure, 0755, true)) {
				return $partial_str;
			} else {
				throw new Exception("Folder structure not found.\nUnable to create folder structure.");
			}
		}
	} catch (Exception $e) {
		return $e->getMessage();
	}
}


function getFolderCategoryStructure()
{
	try {
		$partial_str = '';
		$day = date('j');
		$week = '';
		if ($day < 11) {
			$week = 'week_1';
		} else if ($day >= 11 && $day < 21) {
			$week = 'week_2';
		} else if ($day >= 21) {
			$week = 'week_3';
		}
		$partial_str = 'uploads/category/' . date('Y') . '/' . date('m') . '/' . $week;
		$structure = public_path($partial_str);
		if (file_exists($structure)) {
			return $partial_str;
		} else {
			if (mkdir($structure, 0755, true)) {
				return $partial_str;
			} else {
				throw new Exception("Folder structure not found.\nUnable to create folder structure.");
			}
		}
	} catch (Exception $e) {
		return $e->getMessage();
	}
}

function getFolderStructure()
{
	try {
		$partial_str = '';
		$day = date('j');
		$week = '';
		if ($day < 11) {
			$week = 'week_1';
		} else if ($day >= 11 && $day < 21) {
			$week = 'week_2';
		} else if ($day >= 21) {
			$week = 'week_3';
		}
		$partial_str = 'uploads/images/' . date('Y') . '/' . date('m') . '/' . $week;
		$structure = public_path($partial_str);
		if (file_exists($structure)) {
			return $partial_str;
		} else {
			if (mkdir($structure, 0755, true)) {
				return $partial_str;
			} else {
				throw new Exception("Folder structure not found.\nUnable to create folder structure.");
			}
		}
	} catch (Exception $e) {
		return $e->getMessage();
	}
}




// SUBSTRING GETTER
// ****************
function getAddress($arr, $len)
{
	$response = [];
	$response['fullstr'] = $response['substr'] = '';
	$response['isfullstr'] = $response['issubstr'] = 0;
	$response['ispositiveresponse'] = 0;
	$str = '';
	if (!empty($arr)) {
		$str = implode(", ", $arr);
		$response['ispositiveresponse'] = 1;
		if (strlen($str) > $len) {
			$response['fullstr'] = $str;
			$response['isfullstr'] = 1;
			$response['substr'] = substr($str, 0, ($len - 1)) . "...";
			$response['issubstr'] = 1;
		} else {
			$response['fullstr'] = $str;
			$response['isfullstr'] = 1;
		}
	}
	// returning response object not an array
	return json_decode(json_encode($response), FALSE);
}

// STAR CODED STRING GETTER
// ************************
function getStarCodedStr($str, $type = NULL)
{
	if (empty($str))
		return NULL;
	if ($type == 'number') {
		$strArr = str_split($str, 1);
		$strLen = count($strArr);
		$strToReturn = [];
		for ($i = 0; $i < $strLen; ++$i) {
			if ($i < 2) {
				$strToReturn[] = $strArr[$i];
			} else if ($i >= 2 && $i <= ($strLen - 3)) {
				$strToReturn[] = '*';
			} else if ($i > ($strLen - 3)) {
				$strToReturn[] = $strArr[$i];
			}
		}
		$strToReturn = implode($strToReturn);
	} else if ($type == 'email') {
		$strExpl = explode('@', $str);
		$strArr = str_split($strExpl[0], 1);
		$strLen = count($strArr);
		$strToReturn = [];
		for ($i = 0; $i < $strLen; ++$i) {
			if ($i < 1) {
				$strToReturn[] = $strArr[$i];
			} else if ($i >= 1 && $i <= ($strLen - 2)) {
				$strToReturn[] = '*';
			} else if ($i > ($strLen - 2)) {
				$strToReturn[] = $strArr[$i];
			}
		}
		$strToReturn = implode($strToReturn);
		if (preg_match("/@/", $str)) {
			$strToReturn .= "@" . $strExpl[1];
		}
	}
	return $strToReturn;
}

// RETURN STATE/UNION TERROTERIES LIST
// ***********************************

function getStates()
{
	return array(
		array('id' => '1', 'state_name' => 'Andaman and Nicobar Islands', 'state_country_id' => '101'),
		array('id' => '2', 'state_name' => 'Andhra Pradesh', 'state_country_id' => '101'),
		array('id' => '3', 'state_name' => 'Arunachal Pradesh', 'state_country_id' => '101'),
		array('id' => '4', 'state_name' => 'Assam', 'state_country_id' => '101'),
		array('id' => '5', 'state_name' => 'Bihar', 'state_country_id' => '101'),
		array('id' => '6', 'state_name' => 'Chandigarh', 'state_country_id' => '101'),
		array('id' => '7', 'state_name' => 'Chhattisgarh', 'state_country_id' => '101'),
		array('id' => '8', 'state_name' => 'Dadra and Nagar Haveli', 'state_country_id' => '101'),
		array('id' => '9', 'state_name' => 'Daman and Diu', 'state_country_id' => '101'),
		array('id' => '10', 'state_name' => 'Delhi', 'state_country_id' => '101'),
		array('id' => '11', 'state_name' => 'Goa', 'state_country_id' => '101'),
		array('id' => '12', 'state_name' => 'Gujarat', 'state_country_id' => '101'),
		array('id' => '13', 'state_name' => 'Haryana', 'state_country_id' => '101'),
		array('id' => '14', 'state_name' => 'Himachal Pradesh', 'state_country_id' => '101'),
		array('id' => '15', 'state_name' => 'Jammu and Kashmir', 'state_country_id' => '101'),
		array('id' => '16', 'state_name' => 'Jharkhand', 'state_country_id' => '101'),
		array('id' => '17', 'state_name' => 'Karnataka', 'state_country_id' => '101'),
		array('id' => '18', 'state_name' => 'Kenmore', 'state_country_id' => '101'),
		array('id' => '19', 'state_name' => 'Kerala', 'state_country_id' => '101'),
		array('id' => '20', 'state_name' => 'Lakshadweep', 'state_country_id' => '101'),
		array('id' => '21', 'state_name' => 'Madhya Pradesh', 'state_country_id' => '101'),
		array('id' => '22', 'state_name' => 'Maharashtra', 'state_country_id' => '101'),
		array('id' => '23', 'state_name' => 'Manipur', 'state_country_id' => '101'),
		array('id' => '24', 'state_name' => 'Meghalaya', 'state_country_id' => '101'),
		array('id' => '25', 'state_name' => 'Mizoram', 'state_country_id' => '101'),
		array('id' => '26', 'state_name' => 'Nagaland', 'state_country_id' => '101'),
		array('id' => '27', 'state_name' => 'Narora', 'state_country_id' => '101'),
		array('id' => '28', 'state_name' => 'Natwar', 'state_country_id' => '101'),
		array('id' => '29', 'state_name' => 'Odisha', 'state_country_id' => '101'),
		array('id' => '30', 'state_name' => 'Paschim Medinipur', 'state_country_id' => '101'),
		array('id' => '31', 'state_name' => 'Pondicherry', 'state_country_id' => '101'),
		array('id' => '32', 'state_name' => 'Punjab', 'state_country_id' => '101'),
		array('id' => '33', 'state_name' => 'Rajasthan', 'state_country_id' => '101'),
		array('id' => '34', 'state_name' => 'Sikkim', 'state_country_id' => '101'),
		array('id' => '35', 'state_name' => 'Tamil Nadu', 'state_country_id' => '101'),
		array('id' => '36', 'state_name' => 'Telangana', 'state_country_id' => '101'),
		array('id' => '37', 'state_name' => 'Tripura', 'state_country_id' => '101'),
		array('id' => '38', 'state_name' => 'Uttar Pradesh', 'state_country_id' => '101'),
		array('id' => '39', 'state_name' => 'Uttaranchal', 'state_country_id' => '101'),
		array('id' => '40', 'state_name' => 'Vaishali', 'state_country_id' => '101'),
		array('id' => '41', 'state_name' => 'West Bengal', 'state_country_id' => '101'),
		array('id' => '42', 'state_name' => 'Uttarakhand', 'state_country_id' => '101'),
	);

}


// RETURN CLIENTS TYPE
// *******************
/* function getClientsType(){
	return [
		'general'=>'General',
		'lead_based'=>'Lead Based',
		'yearly_subscription'=>'Yearly Subscription',
		'free_subscription'=>'Free Subscription (2 Month)',
		'count_based_subscription'=>'Count Based Subscription'
	];
} */


function getClientsType()
{
	return [
		'' => 'Select Package Name',
		'Gold' => 'Gold',
		'Diamond' => 'Diamond',
		'Platinum' => 'Platinum'

	];
}

function getClientsList()
{
	$getClientsList = App\Models\Client\Client::where('paid_status', 1)->select('id', 'business_name')->orderby('business_name', 'asc')->get();
	return $getClientsList;
}



function getUserList()
{
	$getUserList = App\Models\User::select('id', 'first_name', 'last_name')->orderby('first_name', 'asc')->get();
	return $getUserList;
}

function getClientsConversionList()
{
	$getClientsConversionList = App\Models\client\Client::where('conversion_status', 1)->select('id', 'business_name')->orderby('business_name', 'asc')->get();
	return $getClientsConversionList;
}




function leadFilterstatus()
{
	$leadFilterstatus = App\Models\Status::where('lead_filter', 1)->orderby('name', 'asc')->get();
	return $leadFilterstatus;
}

function leadFollowStatus()
{
	$leadFollowStatus = App\Models\Status::where('lead_follow_up', 1)->orderby('name', 'asc')->get();
	return $leadFollowStatus;
}

function clientFollowStatus()
{
	$clientFollowStatus = App\Models\Status::where('client_follow_up', 1)->orderby('name', 'asc')->get();
	return $clientFollowStatus;
}

// RETURN PROPER WEBSITE URL
// *************************
function buildWebsiteURL($link = null)
{
	if (null == $link)
		return null;

	if (!preg_match("~^(?:f|ht)tps?://~i", $link)) {
		$link = "http://" . $link;
	}
	return $link;
}

/**
 * Limit the number of characters in a string.
 *
 * @param  string  $value
 * @param  int     $limit
 * @param  string  $end
 * @return string
 */
function str_limit_custom($value, $limit = 100, $end = '...', $more = 'More', $target = 'myModal')
{
	if (mb_strlen($value) <= $limit)
		return $value;
	return rtrim(mb_substr($value, 0, $limit, 'UTF-8')) . $end . " <a href='#' data-toggle='modal' data-target='#" . $target . "'>" . $more . "</a>";
}


function leadassignWithoutZoneCounsellor($leads)
{
	if ($leads) {
		$lead = App\Models\Lead::findOrFail($leads->id);

		if ($lead) {

			$city = App\Models\Citieslists::findOrFail($lead->city_id);

			if ($city) {
				if (!empty($lead->kw_id)) {

					$keyword = App\Models\Keyword::findOrFail($lead->kw_id);

					if ($keyword) {

						$bucketIndex = $keyword->bucket;
						$clientsList = DB::table('clients');
						$clientsList = $clientsList->join('assigned_zones', 'clients.id', '=', 'assigned_zones.client_id');
						//$clientsList = $clientsList->join('assignedd_areas','assignedd_areas.assigned_zone_id','=','assigned_zones.id');

						$clientsList = $clientsList->join('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id');
						$clientsList = $clientsList->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id');
						$clientsList = $clientsList->join('keyword_sell_count', 'keyword_sell_count.slug', '=', 'assigned_kwds.sold_on_position');
						$clientsList = $clientsList->select('clients.*', 'assigned_kwds.*', 'assigned_zones.*', 'assigned_zones.city_id as assgn_city_id', 'keyword.keyword', 'keyword.category', 'keyword.bucket', 'keyword_sell_count.*', 'keyword_sell_count.cat1_price', 'keyword_sell_count.cat2_price', 'keyword_sell_count.cat3_price', 'keyword_sell_count.cat4_price', 'keyword_sell_count.cat5_price');
						$clientsList = $clientsList->where('keyword.id', '=', $lead->kw_id);
						$clientsList = $clientsList->where('assigned_zones.city_id', '=', $lead->city_id);

						//$clientsList = $clientsList->where('assigned_zones.zone_id','=',$request->input('area_zone'))
						//	$clientsList = $clientsList->where('assigned_zones.zone_id','=',$lead->zone_id);
						//	$clientsList = $clientsList->where('assignedd_areas.area_id','=',$lead->area_id);

						$clientsList = $clientsList->whereNull('clients.deleted_at');
						$clientsList = $clientsList->where('clients.coins_amt', '>', '0');
						//$clientsList = $clientsList->where('clients.coins_amt','>',50);
						//$clientsList = $clientsList->where('clients.expired_on','>',date("Y-m-d H:i:s"));
						/* 
						$clientsList = $clientsList->where(function($query){
						$query->where(function($query){
						$query->where('clients.leads_remaining','>','0')								
						->orWhere('clients.coins_amt','>','50') 				
						->orWhere('clients.expired_on','>',date("Y-m-d H:i:s"));					
						}); */

						/* ->orWhere(function($query){
						$query->where(function($query){
						$query->where('clients.client_type','free_subscription')
						->orWhere('clients.client_type','yearly_subscription');
						})
						->whereDate('clients.yrly_subs_end_date','>',date('Y-m-d'));
						})
						->orWhere(function($query){
						$query->where('clients.client_type','count_based_subscription')
						->where('clients.leads_remaining','>','0');
						}); */

						$clientsList = $clientsList->groupby('clients.id');
						$clientsList = $clientsList->where('active_status', '1');
						$clientsList = $clientsList->orderBy(DB::raw('(CASE `assigned_kwds`.`sold_on_position` WHEN \'diamond\' THEN 1 WHEN \'platinum\' THEN 2 WHEN \'gold\' THEN 3 WHEN \'silver\' THEN 4 END)'), 'asc');
						$clientsList = $clientsList->get();

						$defaulterClientsList = DB::table('clients');
						//	$defaulterClientsList = $defaulterClientsList->join('assigned_zones','clients.id','=','assigned_zones.client_id');
						//$defaulterClientsList = $defaulterClientsList->join('assignedd_areas','assignedd_areas.assigned_zone_id','=','assigned_zones.id');

						$defaulterClientsList = $defaulterClientsList->join('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id');
						$defaulterClientsList = $defaulterClientsList->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id');
						$defaulterClientsList = $defaulterClientsList->join('keyword_sell_count', 'keyword_sell_count.slug', '=', 'assigned_kwds.sold_on_position');
						$defaulterClientsList = $defaulterClientsList->select('clients.*', 'assigned_kwds.*', 'keyword.keyword', 'keyword.category', 'keyword.bucket');
						$defaulterClientsList = $defaulterClientsList->where('keyword.id', '=', $lead->kw_id);

						//	$defaulterClientsList = $defaulterClientsList->where('assigned_zones.zone_id','=',$lead->zone_id);
						//$defaulterClientsList = $defaulterClientsList->where('assignedd_areas.area_id','=',$lead->area_id);

						$defaulterClientsList = $defaulterClientsList->whereNull('clients.deleted_at');
						$defaulterClientsList = $defaulterClientsList->where(function ($query) {
							$query->where(function ($query) {

								//$query->whereIn('clients.client_type','lead_based')
								$query->where('clients.coins_amt', '>', '0');
								// ->where('clients.leads_remaining','>','0');
							});
							/* ->orWhere(function($query){
								$query->where(function($query){
									$query->where('clients.client_type','free_subscription')
									->orWhere('clients.client_type','yearly_subscription');
								})
								->whereDate('clients.yrly_subs_end_date','<=',date('Y-m-d'));
							})
							->orWhere(function($query){
								$query->where('clients.client_type','count_based_subscription')
									  ->where('clients.leads_remaining','=','0');
							}); */


						});
						$defaulterClientsList = $defaulterClientsList->orderby(DB::raw('(CASE `assigned_kwds`.`sold_on_position` WHEN \'platinum\' THEN 1 WHEN \'diamond\' THEN 2 END)'), 'asc');
						//$defaulterClientsList = $defaulterClientsList->orderby(DB::raw('(CASE `clients`.`client_type` WHEN \'Platinum\' THEN 1 WHEN \'Diamond\' THEN 2 END)'),'asc');
						//->orderby('comment_count','desc')
						//->tosql();
						$defaulterClientsList = $defaulterClientsList->get();

						// BUCKET CALCULATION
						// ******************
						$max = $mCount = 5;
						$i = 0;
						$totalClients = count($clientsList);
						$buckets = [];
						foreach ($clientsList as $client) {
							if ($mCount == 0) {
								$j = $i;
								$buckets[++$j] = $buckets[$i++];
								$buckets[$j]['diamond'] = [];
								$mCount = $max - (count($buckets[$j], 1) - 5);
							}
							if ($client->sold_on_position == 'platinum') {
								$buckets[$i]['platinum'][] = $client;
							}
							if ($client->sold_on_position == 'diamond') {
								$buckets[$i]['diamond'][] = $client;
							}

							if ($client->sold_on_position == 'gold') {
								$buckets[$i]['gold'][] = $client;
							}

							if ($client->sold_on_position == 'silver') {
								$buckets[$i]['silver'][] = $client;
							}


							--$mCount;
						}

						$i = 0;
						$bucketCount = count($buckets);


						if (!empty($clientsList)) {
							foreach ($buckets as $bucket) {
								if ($bucketCount <= $bucketIndex || $bucketIndex == 0) {
									$bucketIndex = 0;
								}

								if ($bucketIndex == $i) {
									foreach ($bucket as $position => $clientss) {

										foreach ($clientss as $clientC) {

											if ($clientC->client_type) {
												$clnt = App\Models\Client\Client::find($clientC->client_id);

												if ($clnt) {
													$dontSave = 0;
													switch ($clientC->client_type) {
														case 'Platinum':
															/*if($clientC->coins_amt-$clientC->cost_per_lead<0){
																	$dontSave = 1;
																}else{
																	$clnt->coins_amt = $clnt->coins_amt - $clientC->cost_per_lead;
																}*/

															if ($clientC->coins_amt - 1 < 0) {
																$dontSave = 1;
															} else {




																$keyword = App\Models\Keyword::find($lead->kw_id);

																$keywordSellCount = App\Models\KeywordSellCount::where('slug', strtolower($clnt->client_type))->first();


																if ($keyword->category == 'Category 1') {
																	$coinsAmt = $keywordSellCount->cat1_price;
																} else if ($keyword->category == 'Category 2') {
																	$coinsAmt = $keywordSellCount->cat2_price;
																} else if ($keyword->category == 'Category 3') {
																	$coinsAmt = $keywordSellCount->cat3_price;
																} else if ($keyword->category == 'Category 4') {
																	$coinsAmt = $keywordSellCount->cat4_price;
																} else if ($keyword->category == 'Category 5') {
																	$coinsAmt = $keywordSellCount->cat5_price;
																} else if ($keyword->category == 'Category 6') {
																	$coinsAmt = $keywordSellCount->cat6_price;
																}else if ($keyword->category == 'Category 7') {
																	$coinsAmt = $keywordSellCount->cat7_price;
																}else if ($keyword->category == 'Category 8') {
																	$coinsAmt = $keywordSellCount->cat8_price;
																}else if ($keyword->category == 'Category 9') {
																	$coinsAmt = $keywordSellCount->cat9_price;
																}else if ($keyword->category == 'Category 10') {
																	$coinsAmt = $keywordSellCount->cat10_price;
																}else {
																	$coinsAmt = '130';
																}


																$clnt->coins_amt = $clnt->coins_amt - $coinsAmt;

															}

															/* if($clnt->coins_amt<50){
																$clnt->expired_on = date("Y-m-d H:i:s");
															} */
															break;
														case 'Diamond':
															/* if($clientC->coins_amt-$clientC->cost_per_lead<0){
																	$dontSave = 1;
																}else{
																	$clnt->coins_amt = $clnt->coins_amt - $clientC->cost_per_lead;
																} */

															if ($clientC->coins_amt - 1 < 0) {
																$dontSave = 1;
															} else {



																$keyword = App\Models\Keyword::find($lead->kw_id);

																$keywordSellCount = App\Models\KeywordSellCount::where('slug', strtolower($clnt->client_type))->first();


																if ($keyword->category == 'Category 1') {
																	$coinsAmt = $keywordSellCount->cat1_price;
																} else if ($keyword->category == 'Category 2') {
																	$coinsAmt = $keywordSellCount->cat2_price;
																} else if ($keyword->category == 'Category 3') {
																	$coinsAmt = $keywordSellCount->cat3_price;
																} else if ($keyword->category == 'Category 4') {
																	$coinsAmt = $keywordSellCount->cat4_price;
																} else if ($keyword->category == 'Category 5') {
																	$coinsAmt = $keywordSellCount->cat5_price;
																} else if ($keyword->category == 'Category 6') {
																	$coinsAmt = $keywordSellCount->cat6_price;
																}else if ($keyword->category == 'Category 7') {
																	$coinsAmt = $keywordSellCount->cat7_price;
																}else if ($keyword->category == 'Category 8') {
																	$coinsAmt = $keywordSellCount->cat8_price;
																}else if ($keyword->category == 'Category 9') {
																	$coinsAmt = $keywordSellCount->cat9_price;
																}else if ($keyword->category == 'Category 10') {
																	$coinsAmt = $keywordSellCount->cat10_price;
																}else {
																	$coinsAmt = 130;
																}


																$clnt->coins_amt = $clnt->coins_amt - $coinsAmt;

															}

															/* if($clnt->coins_amt<50){
																$clnt->expired_on = date("Y-m-d H:i:s");
															} */
															break;



													}
													switch ($clientC->category) {
														case 'Category 1':
															if ($clientC->coins_amt - $clientC->cat1_price < 0) {
																$dontSave = 1;
															} else {
																$clnt->coins_amt = $clnt->coins_amt - $clientC->cat1_price;
															}
															break;
														case 'Category 2':
															if ($clientC->coins_amt - $clientC->cat2_price < 0) {
																$dontSave = 1;
															} else {
																$clnt->coins_amt = $clientC->coins_amt - $client->cat2_price;
															}
															break;
														case 'Category 3':
															if ($clientC->coins_amt - $clientC->cat3_price < 0) {
																$dontSave = 1;
															} else {
																$clnt->coins_amt = $clientC->coins_amt - $clientC->cat3_price;
															}
															break;
														case 'Category 4':
															if ($clientC->coins_amt - $clientC->cat4_price < 0) {
																$dontSave = 1;
															} else {
																$clnt->coins_amt = $clientC->coins_amt - $clientC->cat4_price;
															}
															break;
														case 'Category 5':
															if ($clientC->coins_amt - $clientC->cat5_price < 0) {
																$dontSave = 1;
															} else {
																$clnt->coins_amt = $clientC->coins_amt - $clientC->cat5_price;
															}
															break;
														case 'Category X':
															if ($clientC->sold_on_position == 'premium') {
																$amtToDeduct = $clientC->premium_price;
															}
															if ($clientC->sold_on_position == 'platinum') {
																$amtToDeduct = $clientC->platinum_price;
															}
															if ($clientC->sold_on_position == 'king') {
																$amtToDeduct = $clientC->king_price;
															}
															if ($clientC->sold_on_position == 'royal') {
																$amtToDeduct = $clientC->royal_price;
															}
															if ($clientC->sold_on_position == 'preferred') {
																$amtToDeduct = $clientC->preferred_price;
															}
															if ($clientC->coins_amt - $amtToDeduct < 0) {
																$dontSave = 1;
															} else {
																$clnt->coins_amt = $clientC->coins_amt - $amtToDeduct;
															}
															break;
													}
													if ($dontSave) {
														//$this->intimateDefaulterClients($client, $lead);
														continue;
													} else {
														$clnt->save();
													}
												}
											}
											/* else if($client->client_type == 'count_based_subscription'){
												$clnt = Client::find($client->id);
												if($clnt){
													//$dontSave = 0;
													if($clnt->leads_remaining==0){
														$this->intimateDefaulterClients($client, $lead);
														continue;
													}
													else{
														$clnt->leads_remaining = $clnt->leads_remaining-1;	
														if($clnt->leads_remaining==0){
															$clnt->expired_on = date("Y-m-d H:i:s");
														}
														$clnt->save();
													}
												}
											} */

											$assignvalidation = App\Models\AssignedLead::where('client_id', $clientC->client_id)->where('kw_id', $lead->kw_id)->where('lead_id', $lead->id)->get()->count();
											if ($assignvalidation == 0) {

												$assignedLead = new App\Models\AssignedLead;
												$assignedLead->kw_id = $lead->kw_id;
												$assignedLead->client_id = $clientC->client_id;
												$assignedLead->lead_id = $lead->id;
												$assignedLead->coins = $coinsAmt;

												if ($assignedLead->save()) {
													$lead->push_by = 1;
													$lead->assign_status = 1;
													$lead->save();
													$followUp = new App\Models\LeadFollowUp;
													$followUp->status = App\Models\Status::where('name', 'LIKE', 'New Lead')->first()->id;
													$followUp->lead_id = $lead->id;
													$followUp->client_id = $clientC->client_id;
													$followUp->save();

												}
											}



										}

									}
									$kw = App\Models\Keyword::find($lead->kw_id);
									$kw->bucket = $i + 1;
									$kw->save();
								}
								$i++;



							}




						}

					}

				}



			}
		}
	}

}