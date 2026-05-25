<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Mostafaznv\PdfOptimizer\Pdf;

use Mostafaznv\PdfOptimizer\Laravel\Facade\PdfOptimizer;
use Mostafaznv\PdfOptimizer\Enums\PdfSettings;
use Mostafaznv\PdfOptimizer\Enums\ColorConversionStrategy;
use Illuminate\Support\Facades\Storage;
use DB;
use App\Models\Keyword;
use App\Models\Client\AssignedKWDS;
use App\Models\Client;
use App\Models\Citieslists;
use App\Models\City;
use App\Models\Blogdetails;
use App\Models\ChildCategory;
use App\Models\Lead;
use Session;
use App\Models\ParentCategory;
use App\Models\Client\Comment;
use App\Models\HomeSlider;
class WebsiteController extends Controller
{

	/**
	 * @OA\Get(
	 *     path="/api/website/city/keyword",
	 *     tags={"Website"},
	 *     summary="Frontend Search city and keyword",
	 *     description="Search records dynamically based on a keyword or filters",
	 *      
	 *      @OA\Parameter(
	 *         name="city",
	 *         in="query",
	 *         required=false,
	 *         description="Filter by city",
	 *         @OA\Schema(type="string", example="noida")
	 *     ),
	 *     @OA\Parameter(
	 *         name="keyword",
	 *         in="query",
	 *         required=true,
	 *         description="Search keyword",
	 *         @OA\Schema(type="string", example="php training")
	 *     ),        
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="ABC Coaching Center"),
	 *                     @OA\Property(property="city", type="string", example="Noida"),
	 *                     @OA\Property(property="category", type="string", example="Education"),
	 *                     @OA\Property(property="rating", type="number", format="float", example=4.5)
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */


	public function getSearch(Request $request)
	{

		$request->validate([
			'keyword' => 'required',
			'city' => 'required',
		]);

		// $search_kw = $request->input('keyword');
		$city = $request->input('city');
		$cityName = ucwords(str_replace('-', ' ', $city));
		//$keywordName = ucwords(str_replace('-', ' ', $search_kw));

		$city = strtolower(str_replace(' ', '-', trim($request->input('city'))));
		$search_kw = strtolower(str_replace(' ', '-', trim($request->input('keyword'))));


		$keywordDetails = DB::table('keyword')
			->join('parent_category', 'keyword.parent_category_id', '=', 'parent_category.id')
			->join('child_category', 'keyword.child_category_id', '=', 'child_category.id')
		 
			->where('keyword.slug', $search_kw)
			->select('keyword.*', 'parent_category.*','child_category.*', 'keyword.id as key_id', 'keyword.faqq1', 'keyword.faqa1', 'keyword.faqq2', 'keyword.faqa2', 'keyword.faqq3', 'keyword.faqa3', 'keyword.faqq4', 'keyword.faqa4', 'keyword.faqq5', 'keyword.faqa5','keyword.faqq6', 'keyword.faqa6', 'keyword.meta_title', 'keyword.meta_description', 'keyword.meta_keywords', 'keyword.top_description', 'keyword.bottom_description', 'keyword.ratingvalue', 'keyword.ratingcount','keyword.courseabout','keyword.heading','keyword.paragraph1','keyword.paragraph2','keyword.paragraph3','keyword.paragraph4','keyword.paragraph5','keyword.paragraph6','keyword.slug','keyword.bottom_heading','keyword.top_heading','keyword.extra_heading','keyword.extra_description')
			->first();
 
			 
		if (!$keywordDetails) {
			return response()->json([
				'status' => false,
				'message' => 'Keyword not found'
			], 404);
		}

		$keywordBanners = [];
		if($keywordDetails){		
			
			$keywordBanners = DB::table('keyword_banners')
			->where('keyword_id', $keywordDetails->key_id)
			->orderBy('sort_order')
			->get()
			->map(function ($b) use ($kwData) {
			$b->image_url = asset($b->image_path);
			$b->alt_text  = $b->alt_text ?: $kwData->meta_title ?? 'Banner';
			return $b;
			})
			->values();

		}
	
		$category_banner = config('app.website') . 'client/images/computer-courses-training.jpg';

		$alt = "";


		$zones = DB::table('citylists')->join('zones', 'zones.city_id', '=', 'citylists.id')->where('citylists.city', 'LIKE', $city)->select('zones.id', 'zones.zone')->orderBy('zones.zone', 'asc')->distinct()->get();

		$firstZone = $zones->get(1);
		$area = $city;

		if ($firstZone) {
			$zone = $firstZone->zone ?? '';
			$pincode = $firstZone->pincode ?? '';
			$area = $city . ', ' . $zone;
			if (!empty($pincode)) {
				$area .= ' ' . $pincode;
			}
		}


		if (!empty($keywordDetails->category_banner)) {
			$cicons = unserialize($keywordDetails->category_banner);

			if (!empty($cicons)) {
				$category_banner = config('app.website') . $cicons['category_banner']['src'];
				$alt = $cicons['category_banner']['name'];
			}
		}
		$child_icon =config('app.website') . 'client/images/it_training.jpg';
		$key_icon =config('app.website') . 'client/images/it_training.jpg';
		$child_alt =$keywordDetails->keyword;
		
		if (!empty($keywordDetails->pc_icon)) {
			$childcons = unserialize($keywordDetails->pc_icon);
 
			if (!empty($childcons)) {
				$child_icon = config('app.website') . $childcons['pc_icon']['src'];
				$child_alt = $childcons['pc_icon']['name'];
			}
		}
		
		if (!empty($keywordDetails->icon)) {
			$keycons = json_decode($keywordDetails->icon);

			if (!empty($keycons)) {
				$key_icon = config('app.website') . $keycons->src;
				$key_alt = $keywordDetails->keyword;
			}
		}


		if (!empty($keywordDetails->meta_title)) {
			$meta_title = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->meta_title);
		}
		if (!empty($keywordDetails->meta_keywords)) {
			$meta_keywords = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->meta_keywords);


		}


		if (!empty($keywordDetails->meta_description)) {
			$meta_description = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->meta_description);
		}

		$courseabout ="";
		$heading ="";
		$paragraph1 ="";
		$paragraph2="";
		$paragraph3 ="";
		$paragraph4 ="";
		$paragraph5 ="";
		$paragraph6 ="";
		if (!empty($keywordDetails->courseabout)) {
			$courseabout = preg_replace('/{{city}}/i', ucfirst($area), $keywordDetails->courseabout);
		}
		if (!empty($keywordDetails->heading)) {
			$heading = preg_replace('/{{city}}/i', ucfirst($area), $keywordDetails->heading);
		}
		if (!empty($keywordDetails->paragraph1)) {
			$paragraph1 = preg_replace('/{{city}}/i', ucfirst($area), $keywordDetails->paragraph1);
		}
		if (!empty($keywordDetails->paragraph2)) {
			$paragraph2 = preg_replace('/{{city}}/i', ucfirst($area), $keywordDetails->paragraph2);
		}
		if (!empty($keywordDetails->paragraph3)) {
			$paragraph3 = preg_replace('/{{city}}/i', ucfirst($area), $keywordDetails->paragraph3);
		}
		if (!empty($keywordDetails->paragraph4)) {
			$paragraph4 = preg_replace('/{{city}}/i', ucfirst($area), $keywordDetails->paragraph4);
		}
		if (!empty($keywordDetails->paragraph5)) {
			$paragraph5 = preg_replace('/{{city}}/i', ucfirst($area), $keywordDetails->paragraph5);
		}
		if (!empty($keywordDetails->paragraph6)) {
			$paragraph6 = preg_replace('/{{city}}/i', ucfirst($area), $keywordDetails->paragraph6);
		}

		$top_description = "";
		if (!empty($keywordDetails->top_description)) {
			$top_description = preg_replace('/{{city}}/i', ucfirst($area), $keywordDetails->top_description);
		}
		$bottom_description = "";
		if (!empty($keywordDetails->bottom_description)) {
			$bottom_description = preg_replace('/{{city}}/i', ucfirst($area), $keywordDetails->bottom_description);
		}
		 

		 

		$data['keyword'] = array(
			'keyword' => $keywordDetails->keyword,
			'keyword_slug' => generate_slug($keywordDetails->keyword),
			'category_banner' => $category_banner,
			'child_icon' => $child_icon,
			'child_alt' => $child_alt,
			'key_icon' => $key_icon,
			'key_alt' => $child_alt,
			'alt' => $alt,
			'meta_title' => $meta_title,
			'meta_keywords' => $meta_keywords,
			'meta_description' => $meta_description,
			'top_description' => $top_description,
			'bottom_description' => $bottom_description,
			'courseabout' => $courseabout,
			'heading' => $heading,
			'paragraph1' => $paragraph1,
			'paragraph2' => $paragraph2,
			'paragraph3' => $paragraph3,
			'paragraph4' => $paragraph4,
			'paragraph5' => $paragraph5,
			'paragraph6' => $paragraph6,			 
			'bottom_heading' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->bottom_heading),
			'top_heading' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->top_heading),
			'extra_heading' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->extra_heading),
			'extra_description' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->extra_description),
			'faqq1' => $keywordDetails->faqq1,
			'faqa1' => $keywordDetails->faqa1,
			'faqq2' => $keywordDetails->faqq2,
			'faqa2' => $keywordDetails->faqa2,
			'faqq3' => $keywordDetails->faqq3,
			'faqa3' => $keywordDetails->faqa3,
			'faqq4' => $keywordDetails->faqq4,
			'faqa4' => $keywordDetails->faqa4,
			'faqq5' => $keywordDetails->faqq5,
			'faqa5' => $keywordDetails->faqa5,
			'faqq6' => $keywordDetails->faqq6,
			'faqa6' => $keywordDetails->faqa6,
			'ratingvalue' => $keywordDetails->ratingvalue,
			'ratingcount' => $keywordDetails->ratingcount,
			'parent_category' => $keywordDetails->parent_category,
			'parent_slug' => $keywordDetails->parent_slug,
			'child_category' => $keywordDetails->child_category,
			'child_slug' => $keywordDetails->child_slug,
			'zone' => $zones,
			'city' => $cityName,
			'area' => $area,
			'keywordBanners	' => $keywordBanners,
		

		);

 


		$clientscheck = DB::table('clients')
			->join('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id')
			->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
			->join('assigned_zones', 'clients.id', '=', 'assigned_zones.client_id')
			->join('citylists', 'assigned_zones.city_id', '=', 'citylists.id')
			->leftJoin(DB::raw('(
        SELECT SUM(rating) AS rating, comment_client_ID, COUNT(comment_ID) AS comment_count
        FROM comments GROUP BY comment_client_ID
    ) c'), 'c.comment_client_ID', '=', 'clients.id')
			->select(
				'clients.*',
				'clients.id as business_id',
				'assigned_kwds.*',
				'citylists.city',
				'keyword.keyword as keywords',
				'keyword.slug as slugs',
				'clients.client_type',
				'c.rating',
				'c.comment_count'
			)
			->where('citylists.city', $city)
			 ->where('clients.active_status', '1')
			->where('keyword.slug', $search_kw)
			// ->groupBy('clients.id')
			->distinct('clients.id')
			->orderByRaw("
        CASE clients.client_type
            WHEN 'platinum' THEN 1
            WHEN 'diamond' THEN 2
            WHEN 'gold' THEN 3
            WHEN 'silver' THEN 4
            ELSE 5
        END
    ")
			->get();


		if ($clientscheck->count() > 0) {
			$clientsList = $clientscheck;
		} else {





			$clientsList = DB::table('clients')
				->join('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id')
				->join('assigned_zones', 'clients.id', '=', 'assigned_zones.client_id')
				->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')

				->join('citylists', 'assigned_zones.city_id', '=', 'citylists.id')
				->leftJoin(DB::raw('(
            SELECT SUM(rating) AS rating, comment_client_ID, COUNT(comment_ID) AS comment_count
            FROM comments GROUP BY comment_client_ID
        ) c'), 'c.comment_client_ID', '=', 'clients.id')
				->select(
					'clients.*',
					'clients.id as business_id',
					'assigned_kwds.*',
					'citylists.city',
					'keyword.keyword as keywords',
					'keyword.slug as slugs',
					'clients.client_type',
					'c.rating',
					'c.comment_count'
				)
				->where('keyword.slug', $search_kw)
				->where('clients.active_status', '1')
				->groupBy('clients.id')

				->orderByRaw("
            CASE clients.client_type
                WHEN 'platinum' THEN 1
                WHEN 'diamond' THEN 2
                WHEN 'gold' THEN 3
                WHEN 'silver' THEN 4
                ELSE 5
            END
        ")
				->get();

		}



		$data['clientsList'] = $clientsList->map(function ($client) {

			$logoImage = config('app.website') . 'client/images/default_pp_small.png';
			$altLogo = "Business Logo";
			if (!empty($client->logo)) {
				$cicons = unserialize($client->logo);
				if (!empty($cicons)) {
					$logoImage = config('app.website') . $cicons['large']['src'];
					$altLogo = $cicons['large']['name'];
				}
			}


			$galleryArray = array();
			if (!empty($client->pictures)) {
				$galleryList = unserialize($client->pictures);
				if (!empty($galleryList)) {
					foreach ($galleryList as $key => $value) {

						$galleryArray[$key] = array(
							'galley' => $value

						);

					}
				}
			}
			$certified_img = config('app.website') . 'img/q_verified.gif';
			$trusted_img = config('app.website') . 'img/q_trust.gif';
			$gst_img = config('app.website') . 'img/q_gst.gif';
			$avgRating = "0";
			if ($client->rating) {
				$avgRating = ($client->rating / (5 * $client->comment_count)) * 5;
				$avgRating = number_format($avgRating, 1, '.', '');
			}


			$assignedKeywords = DB::table('assigned_kwds')
				->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
				->where('assigned_kwds.client_id', $client->business_id)
				->orderBy('keyword', 'asc')
				->distinct()
				->limit(10)
				->pluck('keyword.keyword', 'keyword.slug')
				->toArray();

			$assignedCategory = DB::table('assigned_kwds')
				->join('child_category', 'assigned_kwds.child_cat_id', '=', 'child_category.id')
				->where('assigned_kwds.client_id', $client->client_id)
				->orderBy('child_category', 'asc')
				->distinct()
				->limit(10)
				->pluck('child_category.child_category', 'child_category.child_slug')
				->toArray();
				
				
					$workingHoursHtml = '10AM to 7PM';
					$categorySlug = $client->category_service;

					$template = $this->generate(
						$client,
						$workingHoursHtml,
						$categorySlug
					);
									
				
			return [
				'business_id' => $client->business_id,
				'business_name' => $client->business_name,
				'business_slug' => $client->business_slug,
				'logo' => $logoImage ?? '',
				'altLogo' => $altLogo ?? '',
				'gallery' => $galleryArray ?? '',
				'certifications' => $client->certifications,
				'sirName' => $client->sirName,
				'first_name' => $client->first_name,
				'middle_name' => $client->middle_name,
				'last_name' => $client->last_name,
				'certified_status' => $client->certified_status,
				'trusted_status' => $client->trusted_status,
				'gst_status' => $client->gst_status,
				'certified_img' => $certified_img,
				'trusted_img' => $trusted_img,
				'gst_img' => $gst_img,
				'website' => $client->website,
				'city' => $client->city,
				'state' => $client->state,
				'area' => $client->area,
				'zone' => $client->zone,
				'gst_no' => $client->gst_no,
				'dpiit_no' => $client->dpiit_no,
				'pan_no' => $client->pan_no,
				'cin_no' => $client->cin_no,
				'iso_no' => $client->iso_no,
				'msme_no' => $client->msme_no,
				'coi_no' => $client->coi_no,
			 
				'verified' => $client->verified,
				'trending' => $client->trending,
				'topSearch' => $client->topSearch,
				'featured' => $client->featured,
				'description' => $client->description,
				'mapUrl' => "https://maps.google.com/?q=" . generate_slug($client->address),
				'openUntil' => $client->openUntil,
				'address' => $client->address,
				'pincode' => $client->pincode,
				'country' => $client->country,
				'year_of_estb' => $client->year_of_estb,
				'landmark' => $client->landmark,
				'rating' => $client->rating,
				'avgRating' => $avgRating,
				'call' => "917559435943",
				'whatsapp' => "917559435943",
				'comment_count' => $client->comment_count,
				'tags' => $assignedKeywords,
				'category' => $assignedCategory ?? null,
				'overviewBusiness' => $template ?? null,

			];
		});

 
 
 
 $clientsAgents = DB::table('clients')
    ->join('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id')
    ->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
    ->join('assigned_zones', 'clients.id', '=', 'assigned_zones.client_id')
    ->join('citylists', 'assigned_zones.city_id', '=', 'citylists.id')
    ->leftJoin(DB::raw('(
        SELECT comment_client_ID,
               SUM(rating)        AS rating,
               AVG(rating)        AS avg_rating,
               COUNT(comment_ID)  AS comment_count
        FROM comments
        GROUP BY comment_client_ID
    ) c'), 'c.comment_client_ID', '=', 'clients.id')
    ->select(
        'clients.*',
        'clients.id as business_id',
        DB::raw('MIN(citylists.city)    as city'),
        DB::raw('MIN(keyword.keyword)   as keywords'),
        DB::raw('MIN(keyword.slug)      as slugs'),
        'c.rating',
        'c.avg_rating',
        'c.comment_count'
    )
	 //->where('citylists.city', $city)
    ->where('clients.active_status', '1')
    ->where('keyword.slug', $search_kw)
    ->groupBy('clients.id')
    ->orderByRaw("
        CASE clients.client_type
            WHEN 'platinum' THEN 1
            WHEN 'diamond'  THEN 2
            WHEN 'gold'     THEN 3
            WHEN 'silver'   THEN 4
            ELSE 5
        END
    ")
    ->limit(5)
    ->get();
 
		$data['agents'] = $clientsAgents->map(function ($client) {

			$logoImage = config('app.website') . 'client/images/default_pp_small.png';
			$altLogo = "Business Logo";
			if (!empty($client->logo)) {
				$cicons = unserialize($client->logo);
				if (!empty($cicons)) {
					$logoImage = config('app.website') . $cicons['large']['src'];
					$altLogo = $cicons['large']['name'];
				}
			}


			$galleryArray = array();
			if (!empty($client->pictures)) {
				$galleryList = unserialize($client->pictures);
				if (!empty($galleryList)) {
					foreach ($galleryList as $key => $value) {

						$galleryArray[$key] = array(
							'galley' => $value

						);

					}
				}
			}
			$certified_img = config('app.website') . 'img/q_verified.gif';
			$trusted_img = config('app.website') . 'img/q_trust.gif';
			$gst_img = config('app.website') . 'img/q_gst.gif';
			$avgRating = "0";
			if ($client->rating) {
				$avgRating = ($client->rating / (5 * $client->comment_count)) * 5;
				$avgRating = number_format($avgRating, 1, '.', '');
			}


			$assignedKeywords = DB::table('assigned_kwds')
				->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
				->where('assigned_kwds.client_id', $client->business_id)
				->orderBy('keyword', 'asc')
				->distinct()
				->limit(10)
				->pluck('keyword.keyword', 'keyword.slug')
				->toArray();

			$assignedCategory = DB::table('assigned_kwds')
				->join('child_category', 'assigned_kwds.child_cat_id', '=', 'child_category.id')
				->where('assigned_kwds.client_id', $client->business_id)
				->orderBy('child_category', 'asc')
				->distinct()
				->limit(10)
				->pluck('child_category.child_category', 'child_category.child_slug')
				->toArray();
				
				
					$workingHoursHtml = '10AM to 7PM';
					$categorySlug = $client->category_service;

					$template = $this->generate(
						$client,
						$workingHoursHtml,
						$categorySlug
					);
				 
			return [
				'business_id' => $client->business_id,
				'business_name' => $client->business_name,
				'business_slug' => $client->business_slug,
				'logo' => $logoImage ?? '',
				'altLogo' => $altLogo ?? '',
				'gallery' => $galleryArray ?? '',
				'certifications' => $client->certifications,
				'sirName' => $client->sirName,
				'first_name' => $client->first_name,
				'middle_name' => $client->middle_name,
				'last_name' => $client->last_name,
				'certified_status' => $client->certified_status,
				'trusted_status' => $client->trusted_status,
				'gst_status' => $client->gst_status,
				'certified_img' => $certified_img,
				'trusted_img' => $trusted_img,
				'gst_img' => $gst_img,
				'website' => $client->website,
				'city' => $client->city,
				'state' => $client->state,
				'area' => $client->area,
				'zone' => $client->zone,
				'gst_no' => $client->gst_no,
				'dpiit_no' => $client->dpiit_no,
				'pan_no' => $client->pan_no,
				'cin_no' => $client->cin_no,
				'iso_no' => $client->iso_no,
				'msme_no' => $client->msme_no,
				'coi_no' => $client->coi_no,
			 
				'verified' => $client->verified,
				'trending' => $client->trending,
				'topSearch' => $client->topSearch,
				'featured' => $client->featured,
				'description' => $client->description,
				'mapUrl' => "https://maps.google.com/?q=" . generate_slug($client->address),
				'openUntil' => $client->openUntil,
				'address' => $client->address,
				'pincode' => $client->pincode,
				'country' => $client->country,
				'year_of_estb' => $client->year_of_estb,
				'landmark' => $client->landmark,
				'rating' => $client->rating,
				'avgRating' => $avgRating,
				'call' => "917559435943",
				'whatsapp' => "917559435943",
				'comment_count' => $client->comment_count,
				'tags' => $assignedKeywords,
				'category' => $assignedCategory ?? null,
				'overviewBusiness' => $template ?? null,

			];
		});


		$servicesRelated = Keyword::where('child_category_id', $keywordDetails->child_category_id)
			->where('parent_category_id', $keywordDetails->parent_category_id)
			->select('keyword', 'icon', 'slug')
			->orderBy('keyword', 'asc')
			->distinct()
			->get();

		$servicesRelatedList = $servicesRelated->map(function ($keyword) {
			$img = "";
			$alt = "";

			if (!empty($keyword->icon)) {

				$data = json_decode($keyword->icon, true);
				if (is_array($data) && !empty($data['src'])) {
					$img = config('app.website') . $data['src'];
					$alt = $data['name'] ?? $keyword->keyword;
				}

			}

			return [
				'url' =>$keyword->slug,
				'img' => $img,
				'alt' => $alt,
				'title' => $keyword->keyword,
				'type' => 'keyword',
			];
		})->values()->toArray();

		$data['servicesRelated'] = $servicesRelatedList;

		$cities = City::where('popular', '1')->get();
		$cityList = array();
		if ($cities) {
			foreach ($cities as $ckey => $cvalue) {

				$cityList[$ckey] = array(
					'url' => '/' . strtolower($cvalue->city) . '/' . $keywordDetails->slug,
					'title' => $keywordDetails->keyword . ' in ' . $cvalue->city,

				);

			}
		}



		$defaultLogo = config('app.website') . 'client/images/default_pp_small.png';

		$reviewList = DB::table('clients')
		->join('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id')
		->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
		->leftJoin(DB::raw('(
		SELECT 
		comment_client_ID,
		SUM(rating) AS total_rating,
		COUNT(comment_ID) AS comment_count,
		MAX(comment_author) AS comment_author,
		MAX(comment_content) AS comment_content
		FROM comments
		GROUP BY comment_client_ID
		) c'), 'c.comment_client_ID', '=', 'clients.id')
		->select(
		'clients.id as business_id',
		'clients.business_slug as business_slug',
		'clients.business_name',
		'clients.logo',
		'clients.client_type',
		DB::raw('COALESCE(c.total_rating, 0) as rating'),
		DB::raw('COALESCE(c.comment_count, 0) as comment_count'),
		'c.comment_author',
		'c.comment_content'
		)
		->where('keyword.slug', $search_kw)
		->where('clients.active_status', '1')
		->whereNotNull('c.comment_content')
		->groupBy(
		'clients.id'        
		)
		->orderByRaw("
		CASE clients.client_type
		WHEN 'platinum' THEN 1
		WHEN 'diamond'  THEN 2
		WHEN 'gold'     THEN 3
		WHEN 'silver'   THEN 4
		ELSE 5
		END
		")
		->get()
		->map(function ($business) use ($defaultLogo) {

		// ✅ Safe logo handling with map
		$cicons = @unserialize($business->logo);

		if ($cicons !== false && isset($cicons['large']['src'], $cicons['large']['name'])) {
		$business->logo_image = config('app.website') . $cicons['large']['src'];
		$business->alt_logo   = $cicons['large']['name'];
		} else {
		$business->logo_image = $defaultLogo;
		$business->alt_logo   = 'Business Logo';
		}

		// ✅ Average rating
		$business->avg_rating = $business->comment_count > 0
		? round($business->rating / $business->comment_count, 1)
		: 0;

		// ✅ Remove raw logo field (no longer needed)
		unset($business->logo);

		return $business;
		});
		$data['reviewList'] = $reviewList;
 
		$data['findOtherLocation'] = $cityList;



		 $relatedCategory = DB::table('keyword')
    ->join('parent_category', 'keyword.parent_category_id', '=', 'parent_category.id')
    ->join('child_category', 'child_category.parent_category_id', '=', 'parent_category.id')
    ->where('keyword.slug', $search_kw)
    ->orderBy('child_category.child_category', 'asc')
    ->distinct()
    ->pluck('child_category.child_category', 'child_category.child_slug')
    ->toArray();


		$data['relatedCategory'] = $relatedCategory;
	

		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);




	}

	/**
	 * @OA\Get(
	 *     path="/api/website/homePage",
	 *     tags={"Website"},
	 *     summary="Frontend Home Page",
	 *     description="Display data home page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function homePage(Request $request)
	{
		$url = config('app.url');
		$data['homePage'] = [
			[
				'url' => 'professional-courses',
				'img' => config('app.website') . 'img/IT-Training.png',
				'alt' => 'Professional Courses',
				'title' => 'Professional Courses',
				'type' => 'categories',
				'rating' => '4',
				'count' => '434',

			],
			[
				'url' => 'wedding-pannel',
				'img' => config('app.website') . 'img/wedding.png',
				'alt' => 'Wedding pannel',
				'title' => 'Wedding pannel',
				'type' => 'keyword',
				'rating' => '4',
				'count' => '234',

			],
			[
				'url' => 'electric-services',
				'img' => config('app.website') . 'img/electric-services.png',
				'alt' => 'Electric Services',
				'title' => 'Electric Services',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '377',

			],
			[
				'url' => 'entrance-exams-coaching',
				'img' => config('app.website') . 'img/government-exam.png',
				'alt' => 'Government exam',
				'title' => 'Government exam',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '229',

			],
			[
				'url' => 'study-abroad',
				'img' => config('app.website') . 'img/study-abroad.png',
				'alt' => 'Study Abroad',
				'title' => 'Study Abroad',
				'type' => 'categories',
				'rating' => '5',
				'count' => '399',

			],
			[
				'url' => 'spa',
				'img' => config('app.website') . 'img/Spa & Beauty.png',
				'alt' => 'Spa & Beauty',
				'title' => 'Spa & Beauty',
				'type' => 'categories',
				'rating' => '5',
				'count' => '325',

			],
			[
				'url' => 'repair-services',
				'img' => config('app.website') . 'img/Repairs-Services.png',
				'alt' => 'Repair Services',
				'title' => 'Repair Services',
				'type' => 'child',
				'rating' => '5',
				'count' => '389',

			],
			[
				'url' => 'packers-and-movers',
				'img' => config('app.website') . 'img/Packers-movers.png',
				'alt' => 'Packers & Movers',
				'title' => 'Packers & Movers',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '199',

			],
			[
				'url' => 'professional',
				'img' => config('app.website') . 'img/Professional.png',
				'alt' => 'Professional',
				'title' => 'Professional',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '149',

			],
			[
				'url' => 'contractors',
				'img' => config('app.website') . 'img/contractors.png',
				'alt' => 'Contractors',
				'title' => 'Contractors',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '167',

			],
			[
				'url' => 'collages-and-Institutions',
				'img' => config('app.website') . 'img/Education.png',
				'alt' => 'Education',
				'title' => 'Education',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '197',

			],
			[
				'url' => 'rent-or-buy',
				'img' => config('app.website') . 'img/Rent-buy.png',
				'alt' => 'Rent & Buy',
				'title' => 'Rent & Buy',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '329',
			],
			[
				'url' => 'sports-academy',
				'img' => config('app.website') . 'img/sports.png',
				'alt' => 'Sport Academy',
				'title' => 'Sport Academy',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '539',
			],
			[
				'url' => 'medical',
				'img' => config('app.website') . 'img/Medical.png',
				'alt' => 'Medical',
				'title' => 'Medical',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '269',
			],
			[
				'url' => 'loan-service',
				'img' => config('app.website') . 'img/Loan.png',
				'alt' => 'Loan',
				'title' => 'Loan',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '69',
			],
			[
				'url' => 'dancing',
				'img' => config('app.website') . 'img/Dancing.png',
				'alt' => 'Dancing',
				'title' => 'Dancing',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '79',
			],
			[
				'url' => 'yoga-classes',
				'img' => config('app.website') . 'img/Yoga.png',
				'alt' => 'Yoga',
				'title' => 'Yoga',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '89',
			],
			[
				'url' => 'security-system',
				'img' => config('app.website') . 'img/CCTV-security.png',
				'alt' => 'CCTV Security',
				'title' => 'CCTV Security',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '109',
			],
			 
			[
				'url' => 'tours-and-travels',
				'img' => config('app.website') . 'images/tour-travels.png',
				'alt' => 'Tours & Travels',
				'title' => 'Tours & Travels',
				'type' => 'keyword',
				'rating' => '3.5',
				'count' => '49',
			],

		];

		$data['bannerKeyword'] = [

			[
				'url' => 'repair-services',
				'img' => config('app.website') . 'img/Repairs-Services.png',
				'alt' => 'Repair Services',
				'title' => 'Repair Services',
				'type' => 'child',
				'rating' => '5',
				'count' => '389',

			],
			[
				'url' => 'rent-or-buy',
				'img' => config('app.website') . 'img/Rent-buy.png',
				'alt' => 'Rent & Buy',
				'title' => 'Rent & Buy',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '329',
			],
			[
				'url' => 'packers-and-movers',
				'img' => config('app.website') . 'img/Packers-movers.png',
				'alt' => 'Packers & Movers',
				'title' => 'Packers & Movers',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '199',

			],
			[
				'url' => 'tours-and-travels',
				'img' => config('app.website') . 'images/tour-travels.png',
				'alt' => 'Tours & Travels',
				'title' => 'Tours & Travels',
				'type' => 'keyword',
				'rating' => '3.5',
				'count' => '49',
			],
			[
				'url' => 'professional-courses',
				'img' => config('app.website') . 'img/IT-Training.png',
				'alt' => 'Professional Courses',
				'title' => 'Professional Courses',
				'type' => 'categories',
				'rating' => '4',
				'count' => '434',

			],
			[
				'url' => 'doctor',
				'img' => config('app.website') . 'img/Doctor.webp',
				'alt' => 'Doctor',
				'title' => 'Doctor',
				'type' => 'keyword',
				'rating' => '4',
				'count' => '234',

			],
			[
				'url' => 'electric-services',
				'img' => config('app.website') . 'img/electric-services.png',
				'alt' => 'Electric Services',
				'title' => 'Electric Services',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '377',

			],
			[
				'url' => 'entrance-exams-coaching',
				'img' => config('app.website') . 'img/government-exam.png',
				'alt' => 'Government exam',
				'title' => 'Government exam',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '229',

			],
			[
				'url' => 'study-abroad',
				'img' => config('app.website') . 'img/study-abroad.png',
				'alt' => 'Study Abroad',
				'title' => 'Study Abroad',
				'type' => 'categories',
				'rating' => '5',
				'count' => '399',

			],
			[
				'url' => 'spa',
				'img' => config('app.website') . 'img/Spa & Beauty.png',
				'alt' => 'Spa & Beauty',
				'title' => 'Spa & Beauty',
				'type' => 'categories',
				'rating' => '5',
				'count' => '325',

			],
			[
				'url' => 'professional',
				'img' => config('app.website') . 'img/Professional.png',
				'alt' => 'Professional',
				'title' => 'Professional',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '149',

			],
			[
				'url' => 'contractors',
				'img' => config('app.website') . 'img/contractors.png',
				'alt' => 'Contractors',
				'title' => 'Contractors',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '167',

			],
			[
				'url' => 'collages-and-Institutions',
				'img' => config('app.website') . 'img/Education.png',
				'alt' => 'Education',
				'title' => 'Education',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '197',

			],

			[
				'url' => 'sports-academy',
				'img' => config('app.website') . 'img/sports.png',
				'alt' => 'Sport Academy',
				'title' => 'Sport Academy',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '539',
			],
			[
				'url' => 'medical',
				'img' => config('app.website') . 'img/Medical.png',
				'alt' => 'Medical',
				'title' => 'Medical',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '269',
			],
			[
				'url' => 'loan-service',
				'img' => config('app.website') . 'img/Loan.png',
				'alt' => 'Loan',
				'title' => 'Loan',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '69',
			],
			[
				'url' => 'dancing',
				'img' => config('app.website') . 'img/Dancing.png',
				'alt' => 'Dancing',
				'title' => 'Dancing',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '79',
			],
			[
				'url' => 'yoga-classes',
				'img' => config('app.website') . 'img/Yoga.png',
				'alt' => 'Yoga',
				'title' => 'Yoga',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '89',
			],
			[
				'url' => 'security-system',
				'img' => config('app.website') . 'img/CCTV-security.png',
				'alt' => 'CCTV Security',
				'title' => 'CCTV Security',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '109',
			],
			[
				'url' => 'web-technologies',
				'img' => config('app.website') . 'images/Web-Designers.png',
				'alt' => 'Web Designers',
				'title' => 'Web Designers',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '106',
			],


		];
		$clientsList = DB::table('clients')
			->join('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id')
			->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')

			->leftJoin(DB::raw('(
        SELECT 
            comment_client_ID,
            SUM(rating) AS rating,
            COUNT(comment_ID) AS comment_count
        FROM comments
        GROUP BY comment_client_ID
    ) c'), 'c.comment_client_ID', '=', 'clients.id')

			->select(
				'clients.*',
				'clients.id as client_id',
				'clients.business_slug',
				'clients.client_type',

				DB::raw('MAX(c.rating) as rating'),
				DB::raw('MAX(c.comment_count) as comment_count')
			)

			//	->wherein('keyword.keyword', ['hotels','car-service'])
			->where('clients.active_status', '1')
			->where('logo', '!=', '')
			->whereNotNull('logo')
			->where('pictures', '!=', '')
			->where('business_slug', '!=', '')
			->whereNotNull('pictures')
			->groupBy('clients.id')
			->orderByRaw("
			CASE MAX(clients.client_type)
				WHEN 'platinum' THEN 1
				WHEN 'diamond' THEN 2
				WHEN 'gold' THEN 3
				WHEN 'silver' THEN 4
				ELSE 5
			END
		")->limit(8)

			->get();


		$data['featuredBusinesses'] = $clientsList->map(function ($client) {

			$logoImage = 'client/images/default_pp_small.png';
			$altLogo = "Business Logo";
			if (!empty($client->logo)) {
				$cicons = unserialize($client->logo);

				if (!empty($cicons)) {
					$logoImage = config('app.website') . $cicons['large']['src'];
					$altLogo = $cicons['large']['alt'];
				}
			}

			$assignedKwds = DB::table('assigned_kwds')
				->join('keyword', 'keyword.id', '=', 'assigned_kwds.kw_id')
				->join('child_category', 'child_category.id', '=', 'assigned_kwds.child_cat_id')
				->select('keyword.keyword', 'child_category.child_category as child_category_name')
				->where('assigned_kwds.client_id', '=', $client->client_id)
				->limit(5)
				->get();




			$galleryArray = array();
			if (!empty($client->pictures)) {
				$galleryList = unserialize($client->pictures);
				if (!empty($galleryList)) {
					$i = 0;
					foreach ($galleryList as $key => $value) {

						$galleryArray[$i] = array(
							'galley' => $value
						);
						$i++;

					}
				}
			}
			$certified_img = config('app.website') . 'img/q_verified.gif';
			$trusted_img = config('app.website') . 'img/q_trust.gif';
			$gst_img = config('app.website') . 'img/q_gst.gif';
			$avgRating = "0";
			if ($client->rating) {
				$avgRating = ($client->rating / (5 * $client->comment_count)) * 5;
				$avgRating = number_format($avgRating, 1, '.', '');
			}
			return [
				'business_id' => $client->client_id,
				'business_name' => $client->business_name,
				'business_slug' => $client->business_slug,
				'logo' => $logoImage ?? '',
				'altLogo' => $altLogo ?? '',
				'gallery' => $galleryArray ?? '',
				'certifications' => $client->certifications,
				'sirName' => $client->sirName,
				'first_name' => $client->first_name,
				'middle_name' => $client->middle_name,
				'last_name' => $client->last_name,
				'certified_status' => $client->certified_status,
				'trusted_status' => $client->trusted_status,
				'gst_status' => $client->gst_status,
				'certified_img' => $certified_img,
				'trusted_img' => $trusted_img,
				'gst_img' => $gst_img,
				'website' => $client->website,
				'verified' => $client->verified,
				'trending' => $client->trending,
				'topSearch' => $client->topSearch,
				'featured' => $client->featured,
				'description' => $client->description,
				'city' => $client->city,
				'state' => $client->state,
				'area' => $client->area,
				'zone' => $client->zone,
				'address' => $client->address,
				'pincode' => $client->pincode,
				'country' => $client->country,
				'year_of_estb' => $client->year_of_estb,
				'landmark' => $client->landmark,
				'mapUrl' => "https://maps.google.com/?q=" . generate_slug($client->address),
				'whatsapp' => '7559435943',
				'call' => '917559435943',
				'rating' => $client->rating,
				'openUntil' => $client->openUntil,
				'avgRating' => $avgRating,
				'comment_count' => $client->comment_count,
				'keywords' => $assignedKwds ?? null,
			];
		});

		$clients = Client::get()->count();
		$keyword = Keyword::get()->count();
		$citieslists = Citieslists::get()->count();

		$childCategory = ChildCategory::get()->count();
		$parentCategory = ParentCategory::get()->count();


		$data['businessOwners'] = [
			
			
				'GrowClient' => $clients . ' +',
				'Suppliers' => $childCategory . ' +',
				'ProductsServices' => $citieslists . ' K+',
				'Keyword' => $keyword . ' +',
				'Store' => $parentCategory . ' +',
				'Platform' => $parentCategory . ' K+',
			

		];


		$data['popularSearches'] = [
			[
				'url' => 'computer-courses',
				'img' => config('app.website') . 'popular/IT-Training.jpg',
				'alt' => 'computer courses',
				'title' => 'computer courses',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '139',

			],
			[
				'url' => 'entrance-exams-coaching',
				'img' => config('app.website') . 'popular/Entrance-Exam.jpg',
				'alt' => 'Entrance exam',
				'title' => 'Entrance exam',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '99',

			],
			[
				'url' => 'packers-and-movers',
				'img' => config('app.website') . 'popular/Packers-Movers.jpg',
				'alt' => 'Packers & Movers',
				'title' => 'Packers & Movers',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '132',

			],
			[
				'url' => 'interior-designer',
				'img' => config('app.website') . 'popular/Interior-design.jpg',
				'alt' => 'Interior Design',
				'title' => 'Interior Design',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '192',

			],
			[
				'url' => 'real-estate-agent',
				'img' => config('app.website') . 'popular/real-estate-agent.jpg',
				'alt' => 'Real Estate Agents',
				'title' => 'Real Estate Agents',
				'type' => 'keyword',
				'rating' => '3.5',
				'count' => '239',

			],
			[
				'url' => 'carpenters',
				'img' => config('app.website') . 'popular/carpenter.jpg',
				'alt' => 'Carpenters',
				'title' => 'Carpenters',
				'type' => 'keyword',
				'rating' => '3.5',
				'count' => '123',

			],
			[
				'url' => 'wedding-pannel',
				'img' => config('app.website') . 'popular/Bridal-Wear.jpg',
				'alt' => 'Wedding pannel',
				'title' => 'Wedding pannel',
				'type' => 'keyword',
				'rating' => '3.5',
				'count' => '119',

			],
			 


		];
		$data['trending'] = [
			[
				'url' => 'ac-repair-service',
				'title' => 'AC Repair Service',
				'type' => 'keyword',
				'rating' => '3.5',
				'count' => '199',

			],
			[
				'url' => 'banquet-hall',
				'title' => 'Wedding Planning',
				'type' => 'keyword',
				'rating' => '3.5',
				'count' => '778',

			],
			[
				'url' => 'clinical-research',
				'title' => 'Clinical',
				'type' => 'keyword',
				'rating' => '4',
				'count' => '374',

			],
			[
				'url' => 'home-loan',
				'title' => 'Home Loan',
				'type' => 'keyword',
				'rating' => '4.75',
				'count' => '475',

			],

			[
				'url' => 'carpenters',
				'title' => 'Carpenters',
				'type' => 'keyword',
				'rating' => '4.75',
				'count' => '463',

			]


		];




		$blogPageList = [];
		$blogdetails = Blogdetails::where('status', '1')->limit(3)->orderBy('id', 'DESC')->get();
		if (!empty($blogdetails)) {
			foreach ($blogdetails as $key => $blog) {
				$image = "";
				$alt = "";
				if ($blog->image != '') {
					$image = unserialize($blog->image);
					$image = config('app.website') . $image['large']['src'];
					$alt = $blog->name;
				}



				$blogPageList[$key] = array(
					'url' =>  $blog->slug,
					'img' => $image,
					'alt' => $alt,
					'title' => $blog->name,
					'description' => ucfirst(substr($blog->description, 0, 220)),
				);
			}
		}

		$data['blogList'] = $blogPageList;
		if ($data) {
			$data['status'] = true;

			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;

			$data['message'] = "failed";
		}


		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);


	}

	/**
	 * @OA\Get(
	 *     path="/api/website/repairsServices",
	 *     tags={"Website"},
	 *     summary="Frontend Home Page Repairs Services",
	 *     description="Display data home page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function repairsServices(Request $request)
	{
		$url = config('app.url');
		$data['repairsServices'] = [
			[
				'url' => 'ac-repair-service',
				'img' => config('app.website') . 'popular/AC-Service.jpg',
				'alt' => 'AC Service',
				'title' => 'AC Service',
				'type' => 'keyword',
				'rating' => '4.8',
				'count' => '397',

			],
			[
				'url' => 'car-service',
				'img' => config('app.website') . 'popular/car-services.jpg',
				'alt' => 'Car Services',
				'title' => 'Car Services',
				'type' => 'keyword',
				'rating' => '4.5',
				'count' => '359',

			],
			[
				'url' => 'laundry-service',
				'img' => config('app.website') . 'popular/washing-machines.jpg',
				'alt' => 'Laundry Services',
				'title' => 'Laundry Services',
				'type' => 'keyword',
				'rating' => '3.5',
				'count' => '199',

			],
			[
				'url' => 'electricity-service',
				'img' => config('app.website') . 'popular/Electricity-Services.jpg',
				'alt' => 'Electrician Services',
				'title' => 'Electrician Services',
				'type' => 'keyword',
				'rating' => '4.8',
				'count' => '475',

			],
			[
				'url' => 'hotels',
				'img' => config('app.website') . 'popular/Hotel-Services.jpg',
				'alt' => 'Hotels',
				'title' => 'Hotels',
				'type' => 'keyword',
				'rating' => '4.8',
				'count' => '475',

			],
			[
				'url' => 'health-wellness',
				'img' => config('app.website') . 'popular/Fitness-Services.jpg',
				'alt' => 'Health & Fitness',
				'title' => 'Health & Fitness',
				'type' => 'keyword',
				'rating' => '4',
				'count' => '374',
			],

			[
				'url' => 'electrician',
				'img' => config('app.website') . 'popular/Electricity-Services.jpg',
				'alt' => 'Electrician',
				'title' => 'Electrician',
				'type' => 'keyword',
				'rating' => '4.8',
				'count' => '375',
			],
			[
				'url' => 'plumber',
				'img' => config('app.website') . 'popular/Plumber.jpg',
				'alt' => 'Plumber',
				'title' => 'Plumber',
				'type' => 'keyword',
				'rating' => '4.8',
				'count' => '90',
			],

			[
				'url' => 'carpenters',
				'img' => config('app.website') . 'popular/carpenter.jpg',
				'alt' => 'Carpenters',
				'title' => 'Carpenters',
				'type' => 'keyword',
				'rating' => '4.8',
				'count' => '463',
			],
			[
				'url' => 'washing-machine-repairs',
				'img' => config('app.website') . 'popular/washing-machines.jpg',
				'alt' => 'Washing machine repairs',
				'title' => 'Washing machine repairs',
				'type' => 'keyword',
				'rating' => '4.8',
				'count' => '463',
			],
			[
				'url' => 'cctv-installation-training',
				'img' => config('app.website') . 'img/CCTV-security.png',
				'alt' => 'CCTV Installation',
				'title' => 'CCTV installation',
				'type' => 'keyword',
				'rating' => '4.8',
				'count' => '463',
			],



		];
		if ($data) {
			$data['status'] = true;

			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;

			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}

	/**
	 * @OA\Get(
	 *     path="/api/website/weddingPlanning",
	 *     tags={"Website"},
	 *     summary="Frontend Home Page Wedding Planning",
	 *     description="Display data home page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function weddingPlanning(Request $request)
	{
		$url = config('app.url');
		$data['weddingPlanning'] = [
			[
				'url' => 'catering-services',
				'img' => config('app.website') . 'popular/Catering-Services.jpg',
				'alt' => 'Catering Services',
				'title' => 'Catering Services',
				'type' => 'keyword',

			],
			[
				'url' => 'banquet-hall',
				'img' => config('app.website') . 'popular/Banquet-Halls.jpg',
				'alt' => 'Banquet Halls',
				'title' => 'Banquet Halls',
				'type' => 'keyword',

			],
			[
				'url' => 'stage-decorators',
				'img' => config('app.website') . 'popular/Stage-Decorators.jpg',
				'alt' => 'Stage Decorators',
				'title' => 'Stage Decorators',
				'type' => 'keyword',

			],
			[
				'url' => 'makeup-artists',
				'img' => config('app.website') . 'popular/makeup-artists.jpg',
				'alt' => 'Makeup Artists',
				'title' => 'Makeup Artists',
				'type' => 'keyword',

			],
			[
				'url' => 'mehendi-artists',
				'img' => config('app.website') . 'popular/Mehendi-Artists.jpg',
				'alt' => 'Mehendi Artists',
				'title' => 'Mehendi Artists',
				'type' => 'keyword',

			],
			[
				'url' => 'bridal-wear',
				'img' => config('app.website') . 'popular/Bridal-Wear.jpg',
				'alt' => 'Bridal Wear',
				'title' => 'Bridal Wear',
				'type' => 'keyword',

			]


		];

		if ($data) {
			$data['status'] = true;

			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;

			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}
	/**
	 * @OA\Get(
	 *     path="/api/website/wedding-page",
	 *     tags={"Website"},
	 *     summary="Frontend Home Page Wedding page",
	 *     description="Display data page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function weddingPage(Request $request)
	{
		$url = config('app.url');
		$data['wedding_banner'] = config('app.website') . 'popular/wedding_banner.jpg';
		$data['wedding_planning'] = [
			[
				'url' => 'banquet-hall',
				'img' => config('app.website') . 'popular/Banquet_Hall.jpg',
				'alt' => 'Banquet Hall',
				'title' => 'Banquet Hall',
				'type' => 'keyword',

			],
			[
				'url' => 'ghoda-baggi',
				'img' => config('app.website') . 'popular/Ghoda_Baggi.jpg',
				'alt' => 'Ghoda Baggi & Rath',
				'title' => 'Ghoda Baggi & Rath',
				'type' => 'keyword',

			],
			[
				'url' => 'fire-works-and-crackers',
				'img' => config('app.website') . 'popular/Fire_Works_&_Crackers.jpg',
				'alt' => 'Fire Works Crackers',
				'title' => 'Fire Works Crackers',
				'type' => 'keyword',

			],
			[
				'url' => 'photo-and-videography',
				'img' => config('app.website') . 'popular/Photo_and_Videography.jpg',
				'alt' => 'Photo and Videography',
				'title' => 'Photo and Videography',
				'type' => 'keyword',

			],
			[
				'url' => 'flower-decoration',
				'img' => config('app.website') . 'popular/Flower_Decoration.jpg',
				'alt' => 'Flower Decoration',
				'title' => 'Flower Decoration',
				'type' => 'keyword',

			]
		];
		$data['wedding_prerequisits'] = [

			[
				'url' => 'banquet-hall',
				'img' => config('app.website') . 'popular/Banquet-Halls.jpg',
				'alt' => 'Banquet Halls',
				'title' => 'Banquet Halls',
				'type' => 'keyword',

			],
			[
				'url' => 'dj-sound-system',
				'img' => config('app.website') . 'popular/DJ_Sound_System.jpg',
				'alt' => 'DJ Sound Systems',
				'title' => 'DJ Sound Systems',
				'type' => 'keyword',

			],
			[
				'url' => 'party-organisers',
				'img' => config('app.website') . 'popular/Wedding_Organisers.jpg',
				'alt' => 'Party Organiser',
				'title' => 'Party Organiser',
				'type' => 'keyword',

			],
			[
				'url' => 'stage-decoratorss',
				'img' => config('app.website') . 'popular/stage-decoratorss.jpg',
				'alt' => 'Stage Decoration',
				'title' => 'Stage Decoration',
				'type' => 'keyword',

			],



		];


		$data['wedding_planning_for_bride'] = [

			[
				'url' => 'makeup-artists',
				'img' => config('app.website') . 'popular/Makeup_artist.jpg',
				'alt' => 'Makeup Artists',
				'title' => 'Makeup Artists',
				'type' => 'keyword',

			],
			[
				'url' => 'mehendi-artists',
				'img' => config('app.website') . 'popular/Mehendi-Artists.jpg',
				'alt' => 'Mehendi Artists',
				'title' => 'Mehendi Artists',
				'type' => 'keyword',

			],
			[
				'url' => 'bridal-wear',
				'img' => config('app.website') . 'popular/Bridal-Wear.jpg',
				'alt' => 'Bridal Wear',
				'title' => 'Bridal Wear',
				'type' => 'keyword',

			],
			[
				'url' => 'jewellery-designing',
				'img' => config('app.website') . 'popular/Jewellery.jpg',
				'alt' => 'Jewellery',
				'title' => 'Jewellery',
				'type' => 'keyword',

			],
			[
				'url' => 'salons',
				'img' => config('app.website') . 'popular/salon.jpg',
				'alt' => 'salons',
				'title' => 'salons',
				'type' => 'keyword',

			],
			[
				'url' => 'cosmetics',
				'img' => config('app.website') . 'popular/Cosmetic.jpg',
				'alt' => 'Cosmetics',
				'title' => 'Cosmetics',
				'type' => 'keyword',

			],



		];

		$data['wedding_planning_for_groom'] = [

			[
				'url' => 'wedding-suit-for-groom',
				'img' => config('app.website') . 'popular/wedding_suit_for_groom.jpg',
				'alt' => 'Wedding Suit groom',
				'title' => 'Wedding Suit groom',
				'type' => 'keyword',

			],
			[
				'url' => 'makeup-artist-for-groom',
				'img' => config('app.website') . 'popular/Makeup_artist_for_groom.jpg',
				'alt' => 'Makeup Artist',
				'title' => 'Makeup Artist',
				'type' => 'keyword',

			],
			[
				'url' => 'ghoda-baggi',
				'img' => config('app.website') . 'popular/Ghoda_Baggi.jpg',
				'alt' => 'ghoda baggi',
				'title' => 'ghoda baggi',
				'type' => 'keyword',

			],
			[
				'url' => 'hair-dressing',
				'img' => config('app.website') . 'popular/Hair_salons_for_groom.jpg',
				'alt' => 'Hair Salons',
				'title' => 'Hair Salons',
				'type' => 'keyword',

			],
			[
				'url' => 'wedding-band',
				'img' => config('app.website') . 'popular/Wedding_Band.jpg',
				'alt' => 'Wedding Band Baja',
				'title' => 'Wedding Band Baja',
				'type' => 'keyword',

			],
			[
				'url' => 'wedding-transport',
				'img' => config('app.website') . 'popular/Car_Decoration.jpg',
				'alt' => 'wedding transport',
				'title' => 'wedding transport',
				'type' => 'keyword',

			],



		];
		$data['pre-Wedding_planning'] = [

			[
				'url' => 'wedding-choreographer',
				'img' => config('app.website') . 'popular/wedding-choreographer.jpg',
				'alt' => 'Wedding choreographer',
				'title' => 'Wedding choreographer',
				'type' => 'keyword',

			],
			[
				'url' => 'wedding-astrologer',
				'img' => config('app.website') . 'popular/wedding-astrologer.jpg',
				'alt' => 'Wedding Astrologer',
				'title' => 'Wedding Astrologer',
				'type' => 'keyword',

			],
			[
				'url' => 'wedding-dancer-and-singer',
				'img' => config('app.website') . 'popular/wedding-dancer-and-singer.jpg',
				'alt' => 'Wedding Dancer And Singer',
				'title' => 'gWedding Dancer And Singer',
				'type' => 'keyword',

			],
			[
				'url' => 'pandits',
				'img' => config('app.website') . 'popular/Pandits.jpg',
				'alt' => 'Pandits',
				'title' => 'Pandits',
				'type' => 'keyword',

			],
			[
				'url' => 'honeymoon-packages',
				'img' => config('app.website') . 'popular/honeymoon-packages.jpg',
				'alt' => 'honeymoon packages',
				'title' => 'honeymoon packages',
				'type' => 'keyword',

			],
			[
				'url' => 'stage-show-organisers',
				'img' => config('app.website') . 'popular/stage-show-organisers.jpg',
				'alt' => 'Stage Show Organisers',
				'title' => 'Stage Show Organisers',
				'type' => 'keyword',

			],



		];


		if ($data) {
			$data['status'] = true;

			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;

			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}

	/**
	 * @OA\Get(
	 *     path="/api/website/entranceExams",
	 *     tags={"Website"},
	 *     summary="Frontend Home Page Entrance Exams",
	 *     description="Display data home page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function entranceExams(Request $request)
	{
		$url = config('app.url');
		$data['entranceExams'] = [
			[
				'url' => 'entrance-exams-coaching',
				'img' => config('app.website') . 'popular/air-force-navy.jpg',
				'alt' => 'coaching',
				'title' => 'Air Force & Navy / SSR / MR',
				'type' => 'keyword',

			],
			[
				'url' => 'ssc-cgl',
				'img' => config('app.website') . 'popular/SSC-CGL-JEE.jpg',
				'alt' => 'SSC CGL JEE',
				'title' => 'SSC CGL JEE',
				'type' => 'keyword',

			],
			[
				'url' => 'rrb-ntpc-coaching',
				'img' => config('app.website') . 'popular/NTPC-RRB-Railway.jpg',
				'alt' => 'NTPC & RRB Railway ',
				'title' => 'NTPC & RRB Railway ',
				'type' => 'keyword',

			],
			[
				'url' => 'cat-coaching',
				'img' => config('app.website') . 'popular/CAT-exam.jpg',
				'alt' => 'CAT/NEET',
				'title' => 'CAT/NEET',
				'type' => 'keyword',

			],
			[
				'url' => 'ctet-coaching',
				'img' => config('app.website') . 'popular/CTET-Super-TET.jpg',
				'alt' => 'CTET Super TET',
				'title' => 'CTET Super TET',
				'type' => 'keyword',

			],
			[
				'url' => 'entrance-exams-coaching',
				'img' => config('app.website') . 'popular/UPSC-IAS.jpg',
				'alt' => 'UPSC & IAS',
				'title' => 'UPSC & IAS',
				'type' => 'keyword',

			]


		];

		if ($data) {
			$data['status'] = true;

			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;

			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}

	/**
	 * @OA\Get(
	 *     path="/api/website/studyAbroad",
	 *     tags={"Website"},
	 *     summary="Website Home Page Study Abroad",
	 *     description="Display data home page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function studyAbroad(Request $request)
	{
		$url = config('app.url');
		$studyPageList = [];
		$data = [];
		$studyAbroad_id = ParentCategory::where('parent_slug', 'study-abroad')->first();
		$studyAbroad = DB::table('child_category')
			->join('parent_category', 'child_category.parent_category_id', '=', 'parent_category.id')
			->where('parent_category_id', $studyAbroad_id->id)
			->select('parent_category.*', 'child_category.*')->limit(24)
			->get();
		if (!empty($studyAbroad)) {
			foreach ($studyAbroad as $key => $study) {
				if ($study->child_slug != 'overseas-journalism-education-consultants' && $study->child_slug != 'overseas-engineering-education-consultant') {

					if ($study->pc_icon) {
						$abicons = unserialize($study->pc_icon);
						$img = config('app.website') . $abicons['pc_icon']['src'];
					} else {
						$img = config('app.website') . 'images/it-training.png';
					}



					$studyPageList[$key] = array(
						'url' => '' . $study->child_slug,
						'img' => $img,
						'alt' => $study->child_category,
						'title' => $study->child_category,
						'type' => 'child',
					);

					$data['studyAbroad'] = $studyPageList;


				}
			}
		}
		if ($data) {
			$data['status'] = true;

			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;

			$data['message'] = "failed";
		}

		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}

	/**
	 * @OA\Get(
	 *     path="/api/website/getBlog",
	 *     tags={"Website"},
	 *     summary="Website Home Page Blog",
	 *     description="Display data home page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function getBlog(Request $request)
	{
		$url = config('app.url');

		$blogdetails = Blogdetails::where('status', '1')
			->orderBy('id', 'DESC')
			->paginate(100);

		foreach ($blogdetails as $key => $blog) {
			$image = "";
			$alt = "";

			if (!empty($blog->image)) {
				$imgData = unserialize($blog->image);
				if (!empty($imgData['large']['src'])) {
					$image = config('app.website') . $imgData['large']['src'];
					$alt = $blog->name;
				}
			}

			$blogPageList[$key] = [
				'id' => $blog->id,
				'name' => $blog->name,
				'url' => $blog->slug,
				'img' => $image,
				'alt' => $alt,
				'title' => $blog->title,
				'ratingcount' => $blog->ratingcount,
				'ratingvalue' => $blog->ratingvalue,
				'created_at' => date('d, M Y',strtotime($blog->created_at)),
				'updated_at' => get_time(strtotime($blog->updated_at)),
				'description' => ucfirst(substr(strip_tags($blog->description), 0, 220)) . '...',

			];
		}

		return response()->json([
			'success' => true,
			'status' => true,
			'current_page' => $blogdetails->currentPage(),
			'last_page' => $blogdetails->lastPage(),
			'per_page' => $blogdetails->perPage(),
			'total' => $blogdetails->total(),
			'data' => $blogPageList,
		], 200);
	}


	/**
	 * @OA\Get(
	 *     path="/api/website/blog",
	 *     tags={"Website"},
	 *     summary="Frontend blog details",
	 *     description="Search records dynamically based on a business slug",
	 *      
	 *      @OA\Parameter(
	 *         name="blog_slug",
	 *         in="query",
	 *         required=false,
	 *         description="Filter by blog_slug",
	 *         @OA\Schema(type="string", example="microsoft-power-bi-data-visualization-course--master-interactive-dashboards")
	 *     ),  
	 *        
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="ABC Coaching Center"),
	 *                     @OA\Property(property="city", type="string", example="Noida"),
	 *                     @OA\Property(property="category", type="string", example="Education"),
	 *                     @OA\Property(property="rating", type="number", format="float", example=4.5)
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function getBlogDetails(Request $request)
	{
		$url = config('app.url');
		$request->validate([
			'blog_slug' => 'required|exists:blogdetails,slug',
		]);

		$slug = $request->input('blog_slug');

		$blogLists = Blogdetails::where('status', '1')->limit(30)->orderBy('id', 'DESC')->get();

		if (!empty($blogLists)) {
			foreach ($blogLists as $key => $blog) {
				$image = "";
				$alt = "";

				if (!empty($blog->image)) {
					$imgData = unserialize($blog->image);
					if (!empty($imgData['large']['src'])) {
						$image = config('app.website') . $imgData['large']['src'];
						$alt = $blog->name;
					}
				}

				$blogPageList[$key] = [
					'name' => $blog->name,
					'url' => $blog->slug,
					'id' => $blog->id,
					'img' => $image,
					'alt' => $alt,
					'title' => $blog->title,
					'created_at' => date('d, M Y', strtotime($blog->created_at)),
					'category_name' => $blog->category_name,
					'category_id' => $blog->category_id,
					'updated_at' => get_time(strtotime($blog->updated_at)),
					'description' => ucfirst(substr(strip_tags($blog->description), 0, 220)) . '...',

				];
			}
		}

		$data['blogList'] = $blogPageList;

		$blogdetails = Blogdetails::where('blogdetails.status', '1')
			->where('blogdetails.slug', $slug)
			->leftJoin('authors', 'blogdetails.author', '=', 'authors.id')
			->select('blogdetails.*', 'authors.name as author_name', 'authors.image as author_image', 'authors.comment', 'authors.linkedin_url')
			->first();
		$blogPageDetails = array();

		if (!empty($blogdetails)) {

			$blogimage = "";
			$blogalt = "";

			if (!empty($blogdetails->image)) {
				$imgData = unserialize($blogdetails->image);
				if (!empty($imgData['large']['src'])) {
					$blogimage = config('app.website') . $imgData['large']['src'];
					$blogalt = $blogdetails->name;
				}
			}
			$imageBanner = "";

			$blogaltB = "";

			if (!empty($blogdetails->image_banner)) {
				$imgBanner = unserialize($blogdetails->image_banner);
				if (!empty($imgBanner['large']['src'])) {
					$imageBanner = config('app.website') . $imgBanner['large']['src'];
					$blogaltB = $blogdetails->name;
				}
			}


			$blogPageDetails = [
				'id' => $blogdetails->id,
				'name' => $blogdetails->name,
				'url' => $blogdetails->slug,
				'category_name' => $blogdetails->category_name,
				'category_id' => $blogdetails->category_id,
				'blogImage' => $blogimage,
				'blogalt' => $blogalt,
				'imageBanner' => $imageBanner,
				'blogBannerAalt' => $blogaltB,
				'author_name' => ucfirst($blogdetails->author_name),
				'created_at' => date('d, M Y', strtotime($blog->created_at)),				 
				'updated_at' => get_time(strtotime($blog->updated_at)),
				'title' => $blogdetails->title,
				'description' => ucfirst($blogdetails->description),
				'meta_title' => ucfirst($blogdetails->meta_title),
				'meta_keywords' => ucfirst($blogdetails->meta_keywords),
				'meta_description' => ucfirst($blogdetails->meta_description),
				'top_content' => ucfirst($blogdetails->top_content),
				'bottom_content' => ucfirst($blogdetails->bottom_content),
				'top_heading' => ucfirst($blogdetails->top_heading),
				'bottom_heading' => ucfirst($blogdetails->bottom_heading),				
				'heading' => ucfirst($blogdetails->heading),
				'about_blog' => $blogdetails->about_blog,
				'paragraph1' => $blogdetails->paragraph1,
				'paragraph2' => $blogdetails->paragraph2,
				'paragraph3' => $blogdetails->paragraph3,
				'paragraph4' => $blogdetails->paragraph4,
				'paragraph5' => $blogdetails->paragraph5,
				'paragraph6' => $blogdetails->paragraph6,
				'ratingcount' => $blogdetails->ratingcount,
				'ratingvalue' => $blogdetails->ratingvalue,
				'faqq1' => $blogdetails->faqq1,
				'faqa1' => $blogdetails->faqa1,
				'faqq2' => $blogdetails->faqq2,
				'faqa2' => $blogdetails->faqa2,
				'faqq3' => $blogdetails->faqq3,
				'faqa3' => $blogdetails->faqa3,
				'faqq4' => $blogdetails->faqq4,
				'faqa4' => $blogdetails->faqa4,
				'faqq5' => $blogdetails->faqq5,
				'faqa5' => $blogdetails->faqa5,

			];
		}
		$data['blogDetails'] = $blogPageDetails;

		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);
	}



	/**
	 * @OA\Get(
	 *     path="/api/website/getKeyword",
	 *     tags={"Website"},
	 *     summary="Frontend get Keyword",
	 *     description="Search records dynamically based on a keyword or filters",
	 *            
	 *     @OA\Parameter(
	 *         name="keyword",
	 *         in="query",
	 *         required=false,
	 *         description="Search keyword",
	 *         @OA\Schema(type="string", example="php training")
	 *     ),
	 *        
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="ABC Coaching Center"),
	 *                     
	 *                     @OA\Property(property="category", type="string", example="Education"),
	 *                     @OA\Property(property="rating", type="number", format="float", example=4.5)
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */


	public function getKeyword(Request $request)
	{

		$search_kw = strtolower(str_replace(' ', '-', trim($request->input('keyword'))));


		$city = '';

		$keywordDetails = DB::table('keyword')
			->join('parent_category', 'keyword.parent_category_id', '=', 'parent_category.id')
			->join('child_category', 'keyword.child_category_id', '=', 'child_category.id')
			->where('slug', $search_kw)
			->select('keyword.*', 'parent_category.*', 'child_category.*', 'keyword.id as key_id', 'keyword.faqq1', 'keyword.faqa1', 'keyword.faqq2', 'keyword.faqa2', 'keyword.faqq3', 'keyword.faqa3', 'keyword.faqq4', 'keyword.faqa4', 'keyword.faqq5', 'keyword.faqa5', 'keyword.meta_title', 'keyword.meta_description', 'keyword.meta_keywords', 'keyword.top_description', 'keyword.bottom_description', 'keyword.ratingvalue', 'keyword.ratingcount','keyword.courseabout','keyword.heading','keyword.paragraph1','keyword.paragraph2','keyword.paragraph3','keyword.paragraph4','keyword.paragraph5','keyword.paragraph6','keyword.slug','keyword.bottom_heading','keyword.top_heading','keyword.extra_heading','keyword.extra_description'
			)
			->first();
		 
			if(!$keywordDetails){
						return response()->json([
						'success' => false,
						'status' => false,
						'data' =>'',
					], 410);

			}
		$category_banner = config('app.website') . 'client/images/computer-courses-training.jpg';
		$child_icon =config('app.website') . 'client/images/it_training.jpg';
		$key_icon =config('app.website') . 'client/images/it_training.jpg';
		$child_alt =$keywordDetails->keyword;
		$alt = "";

		if (!empty($keywordDetails->category_banner)) {
			$cicons = unserialize($keywordDetails->category_banner);

			if (!empty($cicons)) {
				$category_banner = config('app.website') . $cicons['category_banner']['src'];
				$alt = $cicons['category_banner']['name'];
			}
		}
		
		if (!empty($keywordDetails->pc_icon)) {
			$childcons = unserialize($keywordDetails->pc_icon);

			if (!empty($childcons)) {
				$child_icon = config('app.website') . $childcons['pc_icon']['src'];
				$child_alt = $childcons['pc_icon']['name'];
			}
		}
		
		if (!empty($keywordDetails->icon)) {
			$keycons = json_decode($keywordDetails->icon);

			if (!empty($keycons)) {
				$key_icon = config('app.website') . $keycons->src;
				$key_alt = $keywordDetails->keyword;
			}
		}

		if (!empty($keywordDetails->meta_title)) {
			$meta_title = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->meta_title);
		} else {
			$meta_title = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->keyword);

		}
		if (!empty($keywordDetails->meta_keywords)) {
			$meta_keywords = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->meta_keywords);


		} else {
			$meta_keywords = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->keyword);

		}


		if (!empty($keywordDetails->meta_description)) {
			$meta_description = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->meta_description);


		} else {
			$meta_description = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->keyword);

		}

		$top_description = "";
		if (!empty($keywordDetails->top_description)) {
			$top_description = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->top_description);
		}
		$bottom_description = "";
		if (!empty($keywordDetails->bottom_description)) {
			$bottom_description = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->bottom_description);
		}
		$courseabout ="";
		$heading ="";
		$paragraph1 ="";
		$paragraph2="";
		$paragraph3 ="";
		$paragraph4 ="";
		$paragraph5 ="";
		$paragraph6 ="";
		if (!empty($keywordDetails->courseabout)) {
			$courseabout = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->courseabout);
		}
		if (!empty($keywordDetails->heading)) {
			$heading = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->heading);
		}
		if (!empty($keywordDetails->paragraph1)) {
			$paragraph1 = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->paragraph1);
		}
		if (!empty($keywordDetails->paragraph2)) {
			$paragraph2 = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->paragraph2);
		}
		if (!empty($keywordDetails->paragraph3)) {
			$paragraph3 = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->paragraph3);
		}
		if (!empty($keywordDetails->paragraph4)) {
			$paragraph4 = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->paragraph4);
		}
		if (!empty($keywordDetails->paragraph5)) {
			$paragraph5 = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->paragraph5);
		}
		if (!empty($keywordDetails->paragraph6)) {
			$paragraph6 = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->paragraph6);
		}

	 
	 




		$data['keyword'] = array(
			'keyword' => $keywordDetails->keyword,
			'keyword_slug' => $keywordDetails->slug,
			'category_banner' => $category_banner,
			'alt' => $alt,
			'child_icon' => $child_icon,
			'child_alt' => $child_alt,
			'key_icon' => $key_icon,
			'key_alt' => $child_alt,
			'meta_title' => $meta_title,
			'meta_keywords' => $meta_keywords,
			'meta_description' => $meta_description,
			'top_description' => $top_description,
			'bottom_description' => $bottom_description,
		 
			'bottom_heading' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->bottom_heading),
			'top_heading' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->top_heading),
			'extra_heading' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->extra_heading),
			'extra_description' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->extra_description),
		 
			
			
			
			
			'faqa1' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqa1),
			'faqq2' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqq2),
			'faqa2' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqa2),
			'faqq3' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqq3),
			'faqa3' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqa3),
			'faqq4' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqq4),
			'faqa4' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqa4),
			'faqq5' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqq5),
			'faqa5' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqa5),
			
			'faqq6' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqq6),
			'faqa6' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqa6),
			
			'courseabout' => $courseabout,
			'heading' => $heading,
			'paragraph1' => $paragraph1,
			'paragraph2' => $paragraph2,
			'paragraph3' => $paragraph3,
			'paragraph4' => $paragraph4,
			'paragraph5' => $paragraph5,
			'paragraph6' => $paragraph6,
 		
			'ratingvalue' => $keywordDetails->ratingvalue,
			'ratingcount' => $keywordDetails->ratingcount,
			'parent_category' => $keywordDetails->parent_category,
			'parent_slug' => $keywordDetails->parent_slug,
			'child_category' => $keywordDetails->child_category,
			'child_slug' => $keywordDetails->child_slug,

		);

		$keywordName = ucwords(str_replace('-', ' ', $search_kw));


		$clientsList = DB::table('clients')
			->join('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id')
			->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')

			->leftJoin(DB::raw('(
        SELECT 
            comment_client_ID,
            SUM(rating) AS rating,
            COUNT(comment_ID) AS comment_count
        FROM comments
        GROUP BY comment_client_ID
    ) c'), 'c.comment_client_ID', '=', 'clients.id')

			->select(
				'clients.*',
				'clients.id as client_id',
				'clients.business_slug',
				'clients.client_type',

				DB::raw('MAX(c.rating) as rating'),
				DB::raw('MAX(c.comment_count) as comment_count')
			)

			->where('keyword.keyword', 'LIKE', "%{$keywordName}%")
			->where('clients.active_status', '1')
			->groupBy('clients.id')

			->orderByRaw("
        CASE MAX(clients.client_type)
            WHEN 'platinum' THEN 1
            WHEN 'diamond' THEN 2
            WHEN 'gold' THEN 3
            WHEN 'silver' THEN 4
            ELSE 5
        END
    ")

			->get();


		$data['clientsList'] = $clientsList->map(function ($client) {

			$logoImage = config('app.website') . 'client/images/default_pp_small.png';
			$altLogo = "Business Logo";
			if (!empty($client->logo)) {
				$cicons = unserialize($client->logo);

				if (!empty($cicons)) {
					$logoImage = config('app.website') . $cicons['large']['src'];
					$altLogo = $cicons['large']['alt'];
				}
			}

		 

				 
				$assignedKeywords = DB::table('assigned_kwds')
				->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
				->where('assigned_kwds.client_id', $client->client_id)
				->orderBy('keyword', 'asc')
				->distinct()
				->limit(10)
				->pluck('keyword.keyword', 'keyword.slug')
				->toArray();


				$assignedCategory = DB::table('assigned_kwds')
				->join('child_category', 'assigned_kwds.child_cat_id', '=', 'child_category.id')
				->where('assigned_kwds.client_id', $client->client_id)
				->orderBy('child_category', 'asc')
				->distinct()
				->limit(10)
				->pluck('child_category.child_category', 'child_category.child_slug')
				->toArray();
 

			$galleryArray = array();
			if (!empty($client->pictures)) {
				$galleryList = unserialize($client->pictures);
				if (!empty($galleryList)) {
					foreach ($galleryList as $key => $value) {

						$galleryArray[$key] = array(
							'galley' => $value
						);
					}
				}
			}
			$certified_img = config('app.website') . 'img/q_verified.gif';
			$trusted_img = config('app.website') . 'img/q_trust.gif';
			$gst_img = config('app.website') . 'img/q_gst.gif';
			$avgRating = "0";
			if ($client->rating) {
				$avgRating = ($client->rating / (5 * $client->comment_count)) * 5;
				$avgRating = number_format($avgRating, 1, '.', '');
			}
			
			$workingHoursHtml = '10AM to 7PM ';
					$categorySlug = $client->category_service;

					$template = $this->generate(
						$client,
						$workingHoursHtml,
						$categorySlug
					);
					
			return [
				'business_id' => $client->client_id,
				'business_name' => $client->business_name,
				'business_slug' => $client->business_slug,
				'logo' => $logoImage ?? '',
				'altLogo' => $altLogo ?? '',
				'gallery' => $galleryArray ?? '',
				'certifications' => $client->certifications,
				'sirName' => $client->sirName,
				'first_name' => $client->first_name,
				'middle_name' => $client->middle_name,
				'last_name' => $client->last_name,
				'certified_status' => $client->certified_status,
				'trusted_status' => $client->trusted_status,
				'gst_status' => $client->gst_status,
				'gst_no' => $client->gst_no,
				'dpiit_no' => $client->dpiit_no,
				'pan_no' => $client->pan_no,
				'cin_no' => $client->cin_no,
				'iso_no' => $client->iso_no,
				'msme_no' => $client->msme_no,
				'coi_no' => $client->coi_no,
				'certified_img' => $certified_img,
				'trusted_img' => $trusted_img,
				'gst_img' => $gst_img,
				'website' => $client->website,
				'verified' => $client->verified,
				'trending' => $client->trending,
				'topSearch' => $client->topSearch,
				'featured' => $client->featured,
				'description' => $client->description,
				'city' => $client->city,
				'state' => $client->state,
				'area' => $client->area,
				'zone' => $client->zone,
				'address' => $client->address,
				'pincode' => $client->pincode,
				'country' => $client->country,
				'year_of_estb' => $client->year_of_estb,
				'landmark' => $client->landmark,
				'mapUrl' => "https://maps.google.com/?q=" . generate_slug($client->address),
				'whatsapp' => '7559435943',
				'call' => '917559435943',
				'rating' => $client->rating,
				'openUntil' => $client->openUntil,
				'avgRating' => $avgRating,
				'comment_count' => $client->comment_count,
				 
				'tags' => $assignedKeywords ?? null,
				'category' => $assignedCategory ?? null,
				'overviewBusiness' => $template ?? null,
			];
		});


	$clientsAgents = DB::table('clients')
    ->join('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id')
    ->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
    ->join('assigned_zones', 'clients.id', '=', 'assigned_zones.client_id')
    ->join('citylists', 'assigned_zones.city_id', '=', 'citylists.id')
    ->leftJoin(DB::raw('(
        SELECT SUM(rating) AS rating,
               AVG(rating) AS avg_rating,
               comment_client_ID,
               COUNT(comment_ID) AS comment_count
        FROM comments
        GROUP BY comment_client_ID
    ) c'), 'c.comment_client_ID', '=', 'clients.id')
    ->select(
        'clients.*',
        'clients.id as business_id',
        'assigned_kwds.*',
        'citylists.city',
        'keyword.keyword as keywords',
        'keyword.slug as slugs',
        'clients.client_type',
        'c.rating',
        'c.avg_rating',
        'c.comment_count'
    )
    
    ->where('clients.active_status', '1')
    ->where('keyword.slug', $search_kw)
    //->where('c.comment_count', '>', 0)   // 👈 ADD THIS
    ->distinct('clients.id')
    ->orderByRaw("
        CASE clients.client_type
            WHEN 'platinum' THEN 1
            WHEN 'diamond'  THEN 2
            WHEN 'gold'     THEN 3
            WHEN 'silver'   THEN 4
            ELSE 5
        END
    ")
    ->limit(5)
    ->get();


		$data['agents'] = $clientsAgents->map(function ($client) {

			$logoImage = config('app.website') . 'client/images/default_pp_small.png';
			$altLogo = "Business Logo";
			if (!empty($client->logo)) {
				$cicons = unserialize($client->logo);

				if (!empty($cicons)) {
					$logoImage = config('app.website') . $cicons['large']['src'];
					$altLogo = $cicons['large']['alt'];
				}
			}

		 

				 
				$assignedKeywords = DB::table('assigned_kwds')
				->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
				->where('assigned_kwds.client_id', $client->client_id)
				->orderBy('keyword', 'asc')
				->distinct()
				->limit(10)
				->pluck('keyword.keyword', 'keyword.slug')
				->toArray();


				$assignedCategory = DB::table('assigned_kwds')
				->join('child_category', 'assigned_kwds.child_cat_id', '=', 'child_category.id')
				->where('assigned_kwds.client_id', $client->client_id)
				->orderBy('child_category', 'asc')
				->distinct()
				->limit(10)
				->pluck('child_category.child_category', 'child_category.child_slug')
				->toArray();
 

			$galleryArray = array();
			if (!empty($client->pictures)) {
				$galleryList = unserialize($client->pictures);
				if (!empty($galleryList)) {
					foreach ($galleryList as $key => $value) {

						$galleryArray[$key] = array(
							'galley' => $value
						);
					}
				}
			}
			$certified_img = config('app.website') . 'img/q_verified.gif';
			$trusted_img = config('app.website') . 'img/q_trust.gif';
			$gst_img = config('app.website') . 'img/q_gst.gif';
			$avgRating = "0";
			if ($client->rating) {
				$avgRating = ($client->rating / (5 * $client->comment_count)) * 5;
				$avgRating = number_format($avgRating, 1, '.', '');
			}
			
			$workingHoursHtml = '10AM to 7PM';
			$categorySlug = $client->category_service;

			$template = $this->generate(
				$client,
				$workingHoursHtml,
				$categorySlug
			);
			return [
				'business_id' => $client->client_id,
				'business_name' => $client->business_name,
				'business_slug' => $client->business_slug,
				'logo' => $logoImage ?? '',
				'altLogo' => $altLogo ?? '',
				'gallery' => $galleryArray ?? '',
				'certifications' => $client->certifications,
				'sirName' => $client->sirName,
				'first_name' => $client->first_name,
				'middle_name' => $client->middle_name,
				'last_name' => $client->last_name,
				'certified_status' => $client->certified_status,
				'trusted_status' => $client->trusted_status,
				'gst_status' => $client->gst_status,
				'gst_no' => $client->gst_no,
				'dpiit_no' => $client->dpiit_no,
				'pan_no' => $client->pan_no,
				'cin_no' => $client->cin_no,
				'iso_no' => $client->iso_no,
				'msme_no' => $client->msme_no,
				'coi_no' => $client->coi_no,
				'certified_img' => $certified_img,
				'trusted_img' => $trusted_img,
				'gst_img' => $gst_img,
				'website' => $client->website,
				'verified' => $client->verified,
				'trending' => $client->trending,
				'topSearch' => $client->topSearch,
				'featured' => $client->featured,
				'description' => $client->description,
				'city' => $client->city,
				'state' => $client->state,
				'area' => $client->area,
				'zone' => $client->zone,
				'address' => $client->address,
				'pincode' => $client->pincode,
				'country' => $client->country,
				'year_of_estb' => $client->year_of_estb,
				'landmark' => $client->landmark,
				'mapUrl' => "https://maps.google.com/?q=" . generate_slug($client->address),
				'whatsapp' => '7559435943',
				'call' => '917559435943',
				'rating' => $client->rating,
				'openUntil' => $client->openUntil,
				'avgRating' => $avgRating,
				'comment_count' => $client->comment_count,
				 
				'tags' => $assignedKeywords ?? null,
				'category' => $assignedCategory ?? null,
				'overviewBusiness' => $template ?? null,
			];
		});


		$servicesRelated = Keyword::where('child_category_id', $keywordDetails->child_category_id)
			->where('parent_category_id', $keywordDetails->parent_category_id)
			->select('keyword', 'icon', 'slug')
			->orderBy('keyword', 'asc')
			->distinct()
			->get();

		$servicesRelatedList = $servicesRelated->map(function ($keyword) {
			$img = "";
			$alt = "";

			if (!empty($keyword->icon)) {

				$data = json_decode($keyword->icon, true);
				if (is_array($data) && !empty($data['src'])) {
					$img = config('app.website') . $data['src'];
					$alt = $data['alt'] ?? $keyword->keyword;
				}

			}

			return [
				'url' => $keyword->slug,
				'img' => $img,
				'alt' => $alt,
				'title' => $keyword->keyword,
				'type' => 'keyword',
			];
		})->values()->toArray();



		$data['servicesRelated'] = $servicesRelatedList;

		$cities = City::where('popular', '1')->get();
		$cityList = array();
		if ($cities) {
			foreach ($cities as $ckey => $cvalue) {

				$cityList[$ckey] = array(
					'url' => strtolower($cvalue->city) . '/' . $keywordDetails->slug,
					'title' => $keywordDetails->keyword . ' in ' . $cvalue->city,

				);

			}
		}

		$data['findOtherLocation'] = $cityList;

		$defaultLogo = config('app.website') . 'client/images/default_pp_small.png';

		$reviewList = DB::table('clients')
		->join('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id')
		->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
		->leftJoin(DB::raw('(
		SELECT 
		comment_client_ID,
		SUM(rating) AS total_rating,
		COUNT(comment_ID) AS comment_count,
		MAX(comment_author) AS comment_author,
		MAX(comment_content) AS comment_content
		FROM comments
		GROUP BY comment_client_ID
		) c'), 'c.comment_client_ID', '=', 'clients.id')
		->select(
		'clients.id as business_id',
		'clients.business_slug as business_slug',
		'clients.business_name',
		'clients.logo',
		'clients.client_type',
		DB::raw('COALESCE(c.total_rating, 0) as rating'),
		DB::raw('COALESCE(c.comment_count, 0) as comment_count'),
		'c.comment_author',
		'c.comment_content'
		)
		->where('keyword.slug', $search_kw)
		->where('clients.active_status', '1')
		->whereNotNull('c.comment_content')
		->groupBy(
		'clients.id'       
		)
		->orderByRaw("
		CASE clients.client_type
		WHEN 'platinum' THEN 1
		WHEN 'diamond'  THEN 2
		WHEN 'gold'     THEN 3
		WHEN 'silver'   THEN 4
		ELSE 5
		END
		")
		->get()
		->map(function ($business) use ($defaultLogo) {

	 
		$cicons = @unserialize($business->logo);

		if ($cicons !== false && isset($cicons['large']['src'], $cicons['large']['name'])) {
		$business->logo_image = config('app.website') . $cicons['large']['src'];
		$business->alt_logo   = $cicons['large']['name'];
		} else {
		$business->logo_image = $defaultLogo;
		$business->alt_logo   = 'Business Logo';
		}

		
		$business->avg_rating = $business->comment_count > 0
		? round($business->rating / $business->comment_count, 1)
		: 0;

	
		unset($business->logo);

		return $business;
		});
			$data['reviewList'] = $reviewList;
		 $relatedCategory = DB::table('keyword')
    ->join('parent_category', 'keyword.parent_category_id', '=', 'parent_category.id')
    ->join('child_category', 'child_category.parent_category_id', '=', 'parent_category.id')
    ->where('keyword.slug', $search_kw)
    ->orderBy('child_category.child_category', 'asc')
    ->distinct()
    ->pluck('child_category.child_category', 'child_category.child_slug')
    ->toArray();


		$data['relatedCategory'] = $relatedCategory;

		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);
	}

	/**
	 * @OA\Get(
	 *     path="/api/website/getCategories",
	 *     tags={"Website"},
	 *     summary="Website get categories",
	 *     description="Search records dynamically based on a keyword or filters",
	 *            
	 *    
	 *        
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="ABC Coaching Center"),
	 *                     
	 *                     @OA\Property(property="category", type="string", example="Education"),
	 *                     @OA\Property(property="rating", type="number", format="float", example=4.5)
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */


	 public function getCategories(Request $request)
	{
     

	$categories = DB::table('parent_category')
    ->join('child_category', 'parent_category.id', '=', 'child_category.parent_category_id')
    ->select(
        'parent_category.id as parent_id',
        'parent_category.parent_slug',
        'parent_category.ratingvalue',
        'parent_category.ratingcount',
        'parent_category.parent_category as parent_name',
        DB::raw('COUNT(child_category.id) as child_count'),

    )
    ->where('parent_category.parent_category', '!=', '')
    ->groupBy(
        'parent_category.id',
        'parent_category.parent_slug',
        'parent_category.parent_category'
    )
    ->havingRaw('COUNT(child_category.id) > 2')	 
    ->orderBy('parent_category.parent_category', 'asc')
    ->get()
    ->map(function ($parent) {
        return [
            'url'   => "" . $parent->parent_slug,
            'slug'  => $parent->parent_slug,
            'name'  => $parent->parent_name,
            'count' => $parent->child_count,
            'type'  => 'categories',
			'rating' => $parent->ratingvalue,
			'ratingcount' => $parent->ratingcount,
        ];
    });

	 
    // 2. Child Categories (Keywords) - Fixed Join
    $childs = DB::table('parent_category')
        ->join('child_category', 'parent_category.id', '=', 'child_category.parent_category_id')  // ← Fixed: Use parent_id, not id
        ->select(
            'parent_category.parent_category as parent_name',
            'parent_category.parent_slug as parent_slug',
            'child_category.child_category as child_name',
            'child_category.child_slug',
            'child_category.pc_icon',    
            'child_category.ratingcount',    
            'child_category.ratingvalue',    
            'child_category.meta_description',    
        )
        ->where('child_category.child_category', '!=', '')
	 
        ->orderBy('parent_category.parent_category', 'asc')
        ->orderBy('child_category.child_category', 'asc')
        ->get()
        ->map(function ($item) {   // rena	med $child → $item for clarity

            $image = "";
            $alt   = "";

            // Handle serialized icon
            if (!empty($item->pc_icon)) {
                $icon = @unserialize($item->pc_icon);

                if (is_array($icon) && isset($icon['icon'])) {
                    $image = config('app.website') . ($icon['icon']['src'] ?? '');
                    $alt   = $icon['icon']['alt'] ?? '';
                }
            }

            return [
                'url'       => "" . $item->child_slug,                        
                'category'  => $item->parent_name,
                'name'  	=> $item->child_name,
                'rating'  	=> $item->ratingvalue,
                'ratingcount'  	=> $item->ratingcount,
                'description'  	=> $item->meta_description,
                'slug'  	=> $item->parent_slug,
                'img'       => $image,
                'alt'       => $alt,               
                'type'      => 'child',
            ];
        });

    // 3. Final Response Data
    $data = [
        'categoryList' => $categories,
        'childs'     => $childs,
        'meta' => [
            'category_banner'   => config('app.website') . 'client/images/computer-courses-training.jpg',
            'alt'               => '',
            'meta_title'        => "Quickdials - Categories & Sub Categories",
            'meta_keywords'     => 'quickdials categories, child categories',
            'meta_description'  => 'Browse all categories and sub-categories on Quickdials',
            'top_description'   => '',
            'bottom_description'=> '',
            'ratingvalue'       => '',
            'ratingcount'       => '',
        ]
    ];

    return response()->json([
        'success' => true,
        'status' => true,
        'data'    => $data,
    ], 200);
}

	/**
	 * @OA\Get(
	 *     path="/api/website/searchCategories",
	 *     tags={"Website"},
	 *     summary="Website get search categories",
	 *     description="Search records dynamically based on a keyword or filters",
	 *            
	 *     @OA\Parameter(
	 *         name="category-slug",
	 *         in="query",
	 *         required=true,
	 *         description="Search category-slug",
	 *         @OA\Schema(type="string", example="computer-courses")
	 *     ),
	 *        
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="ABC Coaching Center"),
	 *                     
	 *                     @OA\Property(property="category", type="string", example="Education"),
	 *                     @OA\Property(property="rating", type="number", format="float", example=4.5)
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */


	public function searchCategories(Request $request)
	{

		$request->validate([
			'category-slug' => 'required|exists:parent_category,parent_slug',
		]);

		$slug = $request->input('category-slug');
		$data['categoryList'] = DB::table('parent_category')
			->join('child_category', 'child_category.parent_category_id', '=', 'parent_category.id')
			->select(
				'parent_category.id as parent_id',
				'parent_category.parent_slug',
				'child_category.ratingvalue as rating_value',
				'child_category.ratingcount as rating_count',
				'parent_category.parent_category as parent_name',
				'child_category.id as child_id',
				'child_category.child_slug',
				'child_category.child_category as child_name',
				'child_category.pc_icon as 	pc_icon',
				
			)
			->where('parent_category.parent_slug', $slug)
			 
			->orderBy('child_category.child_category', 'asc')
			 
			->get()
			->map(function ($child) {
				$image = "";
				$alt = "";

				if (!empty($child->pc_icon)) {
					$cicons = unserialize($child->pc_icon);

					if (!empty($cicons)) {
						$image = config('app.website') . $cicons['pc_icon']['src'];
						$alt = $cicons['pc_icon']['alt'];
					}
				}
				return [
					'id' => $child->parent_id,
					'url' =>$child->child_slug,
					'img' => $image,
					'alt' => $alt,
					'title' => $child->child_name,
					'type' => 'child',
					'rating' => $child->rating_value,
					'ratingcount' => $child->rating_count,
				];
			});


		$categoryDetails = DB::table('parent_category')->where('parent_slug', $slug)->first();


		$banner_image = config('app.website') . 'client/images/computer-courses-training.jpg';
		$alt = "";

		if (!empty($categoryDetails->category_banner)) {
			$cicons = unserialize($categoryDetails->category_banner);

			if (!empty($cicons)) {
				$banner_image = config('app.website') . $cicons['category_banner']['src'];
				$alt = $cicons['category_banner']['alt'];
			}
		}
		
		
		
	 
		$data['keyword'] = array(
			'parent_category' => $categoryDetails->parent_category,
			'parent_slug' => $categoryDetails->parent_slug,
			'category_banner' => $banner_image,
			'alt' => $alt,
			'meta_title' => $categoryDetails->meta_title,
			'meta_keywords' => $categoryDetails->meta_keywords,
			'meta_description' => $categoryDetails->meta_description,
			'top_description' => $categoryDetails->top_description,
			'bottom_description' => $categoryDetails->bottom_description,
			'bottom_heading' => $categoryDetails->bottom_heading,
			'top_heading' => $categoryDetails->top_heading,
			'faqq1' => $categoryDetails->faqq1,
			'faqa1' => $categoryDetails->faqa1,
			'faqq2' => $categoryDetails->faqq2,
			'faqa2' => $categoryDetails->faqa2,
			'faqq3' => $categoryDetails->faqq3,
			'faqa3' => $categoryDetails->faqa3,
			'faqq4' => $categoryDetails->faqq4,
			'faqa4' => $categoryDetails->faqa4,
			'faqq5' => $categoryDetails->faqq5,
			'faqa5' => $categoryDetails->faqa5,
			'ratingvalue' => $categoryDetails->ratingvalue,
			'ratingcount' => $categoryDetails->ratingcount,

		);

		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);


	}


	/**
	 * @OA\Get(
	 *     path="/api/website/getChild",
	 *     tags={"Website"},
	 *     summary="Website get child ",
	 *     description="Search records dynamically based on a child",   
	 *        
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="ABC Coaching Center"),
	 *                     
	 *                     @OA\Property(property="category", type="string", example="Education"),
	 *                     @OA\Property(property="rating", type="number", format="float", example=4.5)
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */


	public function getChild(Request $request)
	{
		 
		// ✅ Parent + Child Categories
		 $childs = DB::table('child_category')
     
    ->leftJoin('keyword', 'child_category.id', '=', 'keyword.child_category_id') // Assuming keyword links to child
    ->select(
        'child_category.id as child_id',
        'child_category.child_slug',
        'child_category.child_category as child_name',
        'child_category.pc_icon',
                            // Needed for URL
        DB::raw('COUNT(keyword.id) as child_count')       // Count keywords under this child
    )
    ->where('child_category.child_category', '!=', '')
    ->groupBy(
        'child_category.id',
        'child_category.child_slug',
        'child_category.child_category',
        'child_category.pc_icon',
         
    )
	 ->havingRaw('COUNT(keyword.id) > 2')
	 
    ->orderBy('child_category.child_category', 'asc')
    ->get()
    ->map(function ($child) {

        $image = "";
        $alt = "";

        // Safe icon unserialize
        if (!empty($child->pc_icon)) {
            $cicons = @unserialize($child->pc_icon);

            if (is_array($cicons) && isset($cicons['pc_icon'])) {
                $image = config('app.website') . ($cicons['pc_icon']['src'] ?? '');
                $alt   = $cicons['pc_icon']['alt'] ?? '';
            }
        }

        return [
            'id'   => $child->child_id,      // Better to use child_slug directly
            'url'   => $child->child_slug,      // Better to use child_slug directly
            'slug'  => $child->child_slug,
            'name'  => $child->child_name,
            'count' => (int) $child->child_count ?? 0,     // Safe count
            'type'  => 'child',
            'img'   => $image,
            'alt'   => $alt,
             
        ];
    });

		// ✅ Keywords (with child category)
	$keywords = DB::table('keyword')
    ->join('child_category', 'keyword.child_category_id', '=', 'child_category.id')
    ->select(
        'keyword.id as keyword_id',
        'keyword.keyword',
        'keyword.slug as keyword_slug',
        'keyword.icon as keyword_icon',
        'child_category.child_category as child_name',
        'child_category.child_slug',
        'keyword.ratingvalue',
        'keyword.ratingcount',
        'keyword.meta_description as description',
        'keyword.seo_type',
    )
	->where('keyword.seo_type','1')
    ->orderBy('keyword.keyword', 'asc')
    ->get()
    ->map(function ($item) {

        $image = "";
        $alt = "";

        // ✅ Icon handle (same as pc_icon logic)
        if (!empty($item->keyword_icon)) {
            $icon = @unserialize($item->keyword_icon);

            if (is_array($icon) && isset($icon['icon'])) {
                $image = config('app.website') . ($icon['icon']['src'] ?? '');
                $alt = $icon['icon']['alt'] ?? '';
            }
        }

        return [
            'id'   => $item->keyword_id,
            'url'   => $item->keyword_slug,
            'slug'   => $item->keyword_slug,
            'title' => $item->keyword,
            'name' => $item->child_name,
            'description' => $item->description,
            'child_slug' => $item->child_slug,
            'img'   => $image,
            'alt'   => $alt,
            'rating'   => $item->ratingvalue,
            'ratingcount'   => $item->ratingcount,            
            'type'  => 'keyword',
        ];
    });

		// ✅ Banner + Meta
		$data = [
			'childsList' => $childs,
			'keywords' => $keywords,
			'meta' => [
				'category_banner' => config('app.website') . 'client/images/computer-courses-training.jpg',
				'alt' => '',
				'meta_title' => "Quickdials child category",
				'meta_keywords' => 'Quickdials child category',
				'meta_description' => 'Quickdials child category',
				'top_description' => 'Quickdials child category',
				'bottom_description' => 'Quickdials child category',
				'ratingvalue' => '',
				'ratingcount' => '',
			]
		];

		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}

	/**
	 * @OA\Get(
	 *     path="/api/website/searchChild",
	 *     tags={"Website"},
	 *     summary="Website get search child",
	 *     description="Search records dynamically based on a child",
	 *            
	 *     @OA\Parameter(
	 *         name="child-slug",
	 *         in="query",
	 *         required=true,
	 *         description="Search child-slug",
	 *         @OA\Schema(type="string", example="cloud-computing")
	 *     ),
	 *        
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="ABC Coaching Center"),
	 *                     
	 *                     @OA\Property(property="category", type="string", example="Education"),
	 *                     @OA\Property(property="rating", type="number", format="float", example=4.5)
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */


	public function searchChild(Request $request)
	{

		$request->validate([
			'child-slug' => 'required|exists:child_category,child_slug',
		]);
		$slug = $request->input('child-slug');


		if (empty($slug) || !is_string($slug)) {
			return response()->json([
				'success' => false,
				'status' => true,
				'message' => 'Invalid or missing child-slug parameter.',
			], 400);
		}

		$data['childLists'] = DB::table('child_category')
			->join('keyword', 'keyword.child_category_id', '=', 'child_category.id')
			->select(
				'keyword.id as keyword_id',
				'keyword.keyword',
				'keyword.icon as icon',
				'keyword.slug',
				'child_category.child_category',
			)
			->where('child_category.child_slug', $slug)
			->orderBy('keyword.keyword', 'asc')
			// ->groupBy('child_category.child_category')
			->get()
			->map(function ($keyword) {
				$image = "";
				$alt = "";

				if (!empty($keyword->icon)) {

					$cicons = json_decode($keyword->icon);

					if ($cicons !== false && !empty($cicons->src) && !empty($cicons->name)) {
						$image = config('app.website') . $cicons->src;
						$alt = $cicons->alt;
					}

				}

				return [
					'id' => $keyword->keyword_id,
					'url' => $keyword->slug,
					'category' => $keyword->child_category,
					'img' => $image ?: '',
					'alt' => $alt ?: $keyword->keyword,
					'title' => $keyword->keyword,
					'type' => 'keyword',
				];
			});

		$childDetails = ChildCategory::where('child_slug', $slug)->first();


		$banner_image = config('app.website') . 'client/images/computer-courses-training.jpg';

		$alt = "";

		if (!empty($childDetails->category_banner)) {
			$cicons = unserialize($childDetails->child_banner);

			if (!empty($cicons)) {
				$banner_image = config('app.website') . $cicons['child_banner']['src'];
				$alt = $cicons['child_banner']['alt'];
			}
		}
		$data['keyword'] = array(
			'child_category' => $childDetails->child_category,
			'child_slug' => $childDetails->child_slug,
			'category_banner' => $banner_image,
			'alt' => $alt,
			'meta_title' => $childDetails->meta_title,
			'meta_keywords' => $childDetails->meta_keywords,
			'meta_description' => $childDetails->meta_description,
			'top_description' => $childDetails->top_description,
			'bottom_description' => $childDetails->bottom_description,
			'bottom_heading' => $childDetails->bottom_heading,
			'top_heading' => $childDetails->top_heading,
			'faqq1' => $childDetails->faqq1,
			'faqa1' => $childDetails->faqa1,
			'faqq2' => $childDetails->faqq2,
			'faqa2' => $childDetails->faqa2,
			'faqq3' => $childDetails->faqq3,
			'faqa3' => $childDetails->faqa3,
			'faqq4' => $childDetails->faqq4,
			'faqa4' => $childDetails->faqa4,
			'faqq5' => $childDetails->faqq5,
			'faqa5' => $childDetails->faqa5,
			'ratingvalue' => $childDetails->ratingvalue,
			'ratingcount' => $childDetails->ratingcount,

		);

		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}
	
	
	/**
	 * @OA\Get(
	 *     path="/api/website/categoryTabsFooter",
	 *     tags={"Website"},
	 *     summary="Website get categories Footer tab",
	 *     description="Search records dynamically based on a keyword or filters",
	 *      @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="ABC Coaching Center"),
	 *                     
	 *                     @OA\Property(property="category", type="string", example="Education"),
	 *                     @OA\Property(property="rating", type="number", format="float", example=4.5)
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */


	public function categoryTabsFooter(Request $request)
	{
		 $data['categories'] = DB::table('parent_category')
    ->join('keyword', 'parent_category.id', '=', 'keyword.parent_category_id')
    ->select(
        'parent_category.id as parent_id',
        'parent_category.parent_slug as slug',
        'parent_category.parent_category as parent_category',
        DB::raw('COUNT(keyword.id) as child_count'),   
    )
    ->whereIn('parent_category.parent_category', [
        'Computer Courses',
        'Electric Services',
        'Home Services',
        'Services',
        'Professional',
        'Spa',
        'Wedding',
        'Coaching and Tuitions',
        'Collages and Institutions',
        'Entrance Exams Coaching',
        'Home Construction',
        'Hotels',
        'Professional Courses',
        'Schools and Colleges',
        'Study Abroad'
    ])
    ->groupBy(
        'parent_category.id',           // ✅ added
        'parent_category.parent_slug',
        'parent_category.parent_category',  // ✅ added
    )
    ->havingRaw('COUNT(keyword.id) > 2')
    ->orderByRaw("
        FIELD(parent_category.parent_category, 
            'Computer Courses', 
            'Electric Services', 
            'Home Services', 
            'Services', 
            'Professional', 
            'Spa', 
            'Wedding',
            'Coaching and Tuitions',
            'Collages and Institutions',
            'Entrance Exams Coaching',
            'Home Construction',
            'Hotels',
            'Professional Courses',
            'Schools and Colleges',
            'Study Abroad'
        ) ASC
    ")
    ->get();

        // Keywords
        $data['keywords'] = DB::table('keyword')
            ->select('slug', 'keyword', 'parent_category_id')
            ->where('seo_type', '1')
            ->get();
 		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
			], 200);
 
	}


	/**
	 * @OA\Get(
	 *     path="/api/website/cityTabsFooter",
	 *     tags={"Website"},
	 *     summary="Website get city Footer tab",
	 *     description="Search records dynamically based on a city or filters",
	 *      @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="ABC Coaching Center"),
	 *                     
	 *                     @OA\Property(property="category", type="string", example="Education"),
	 *                     @OA\Property(property="rating", type="number", format="float", example=4.5)
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	 public function cityTabsFooter(Request $request)
	{
		 $data['cities'] = City::select('id','city')->where('popular','1')->get();

        // Keywords
        $data['keywords'] = DB::table('keyword')
            ->select('slug', 'keyword')
            ->where('seo_type', '1')
            ->get();
 		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
			], 200);


	}

	
	/**
	 * @OA\Get(
	 *     path="/api/website/home-slider",
	 *     tags={"Website"},
	 *     summary="Website Home slider",
	 *     description="Home slider records dynamically based on a child",        
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="ABC Coaching Center"),
	 *                     
	 *                     @OA\Property(property="category", type="string", example="Education"),
	 *                     @OA\Property(property="rating", type="number", format="float", example=4.5)
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */


	public function getHomeSlider(Request $request)
	{

		$data = HomeSlider::where('status', '1')->get()
			->map(function ($slider) {
				$image = "";
				$alt = "";

				if (!empty($slider->image)) {

					$cicons = json_decode($slider->image);

					if ($cicons !== false && !empty($cicons->src) && !empty($cicons->name)) {
						$image = config('app.website') . $cicons->src;
						$alt = $cicons->alt;
					}

				}

				return [
					'img' => $image ?: '',
					'alt' => $alt ?: $slider->title,
					'title' => "",
				];
			});




		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}

	/**
	 * @OA\Get(
	 *     path="/api/website/getCityList",
	 *     tags={"Website"},
	 *     summary="Website get City List and Search Zone records by City ",
	 *     description="Search records dynamically based on a city",
	 *            
	 *     @OA\Parameter(
	 *         name="city",
	 *         in="query",
	 *         required=false,
	 *         description="Search city, area, pincode",
	 *         @OA\Schema(type="string", example="noida")
	 *     ),
	 *        
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="ABC Coaching Center"),
	 *                     
	 *                     @OA\Property(property="category", type="string", example="Education"),
	 *                     @OA\Property(property="rating", type="number", format="float", example=4.5)
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function getCityList(Request $request)
	{

		$cid = trim($request->input('city'));


		$zoneResults = collect();

		if (!empty($cid)) {

			$zoneResults = DB::table('zones')
				->join('citylists', 'citylists.id', '=', 'zones.city_id')
				->where(function ($q) use ($cid) {
					$q->where('zones.zone', 'LIKE', "{$cid}%")
						->orWhere('citylists.city', 'LIKE', "{$cid}%")
						->orWhere('zones.city_id', $cid)
						->orWhere('zones.pincode', 'LIKE', "{$cid}%");
				})
				->select(
					'zones.id as zone_id',
					'zones.zone',
					'citylists.id as city_id',
					'citylists.city as cityName',
					'zones.pincode'
				)
				->orderBy('zones.zone', 'asc')
				->distinct()
				->get();

		} else {

			$defaultCities = collect([
				'Hyderabad',
				'Patna',
				'Gorakhpur',
				'Faridabad',
				'Delhi',
				'Noida',
				'Ghaziabad',
				'Mumbai',
				'Pune',
				'Meerut',
				'Bangalore',
				'Indore',
				'Kanpur',
				'Chennai',
				'Kolkata',
				'Coimbatore',
				'Prayagraj'
			]);

			$zoneResults = DB::table('zones')
				->join('citylists', 'citylists.id', '=', 'zones.city_id')
				->whereIn('citylists.city', $defaultCities)
				->select(
					DB::raw('MIN(zones.id) as zone_id'),
					DB::raw('MIN(zones.zone) as zone'),
					'citylists.id as city_id',
					'citylists.city as cityName',
					DB::raw('NULL as pincode')
				)
				->groupBy('citylists.id', 'citylists.city')
				->orderBy('zone', 'asc')
				->orderBy('citylists.city')
				->get();
		}

		// -------- TRANSFORM USING COLLECTION --------
		$data = $zoneResults->map(function ($zone) {

			$cityDetails = collect([
				$zone->zone ?? null,
				$zone->cityName ?? null,
			])->filter()->implode(', ');

			if (!empty($zone->pincode)) {
				$cityDetails .= ' - ' . $zone->pincode;
			}

			return [
				'id' => $zone->zone_id,
				'city' => $zone->cityName,
				'cityDetails' => ucfirst($cityDetails)
			];

		})->unique('cityDetails')->values();

		return response()->json([
			'status' => true,
			'message' => 'Successfully',
			'data' => $data
		], 200);

	}


/**
	 * @OA\Get(
	 *     path="/api/website/checkCity",
	 *     tags={"Website"},
	 *     summary="Website check City by City ",
	 *     description="Search records dynamically based on a city",
	 *            
	 *     @OA\Parameter(
	 *         name="city",
	 *         in="query",
	 *         required=false,
	 *         description="Search city",
	 *         @OA\Schema(type="string", example="noida")
	 *     ),
	 *        
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="ABC Coaching Center"),
	 *                     
	 *                     @OA\Property(property="category", type="string", example="Education"),
	 *                     @OA\Property(property="rating", type="number", format="float", example=4.5)
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function checkCity(Request $request)
	{
		$cid = trim($request->input('city'));
		$results ="";
		if (!empty($cid)) {

			$results = DB::table('citylists')				 
				->where('city_slug', $cid)				 
				->orderBy('city', 'asc')				 
				->first();
		}
		
		if($results){
		return response()->json([
			'status' => true,
			'message' => 'Successfully',
			'data' => $results
		], 200);
		}else{
		    
		    return response()->json([
			'status' => false,
			'message' => false,
			'data' => null
		], 404);
		}

	}

	/**
	 * @OA\Get(
	 *     path="/api/website/getZoneList",
	 *     tags={"Website"},
	 *     summary="Website Search Zone records by City ",
	 *     description="Search records dynamically based on a zone",
	 *            
	 *     @OA\Parameter(
	 *         name="city",
	 *         in="query",
	 *         required=false,
	 *         description="Search zone, area, pincode",
	 *         @OA\Schema(type="string", example="noida")
	 *     ),
	 *        
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="ABC Coaching Center"),
	 *                     
	 *                     @OA\Property(property="category", type="string", example="Education"),
	 *                     @OA\Property(property="rating", type="number", format="float", example=4.5)
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function getZoneList(Request $request)
	{

		$city = trim($request->input('city'));

		$zones = DB::table('citylists')->join('zones', 'zones.city_id', '=', 'citylists.id')->where('citylists.city', $city)->select('zones.id', 'zones.zone')->orderBy('zones.zone', 'asc')->distinct()->get();

		return response()->json([
			'status' => true,
			'message' => 'Successfully',
			'data' => $zones
		], 200);

	}

	/**
	 * @OA\Get(
	 *     path="/api/website/get-keyword-list",
	 *     tags={"Website"},
	 *     summary="Website get Keyword List and Search Keyword records",
	 *     description="Search records dynamically based on a Keyword",
	 *            
	 *     @OA\Parameter(
	 *         name="keyword",
	 *         in="query",
	 *         required=false,
	 *         description="Search keyword",
	 *         @OA\Schema(type="string", example="java")
	 *     ),
	 *        
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="ABC Coaching Center"),
	 *                     
	 *                     @OA\Property(property="category", type="string", example="Education"),
	 *                     @OA\Property(property="rating", type="number", format="float", example=4.5)
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function getKeywordList(Request $request)
	{
		$keyword = trim($request->input('keyword'));

		// 🔹 Base Keyword Query
		$locations = DB::table('keyword')
			->when(empty($keyword), function ($q) {
				$q->whereIn('id', [
					288,
					601,
					1517,
					159,
					602,
					1624,
					166,
					536,
					1937,
					1481,
					570,
					1665
				]);
			})
			->when(!empty($keyword), function ($q) use ($keyword) {
				$q->where('keyword', 'LIKE', "%{$keyword}%");
			})
			->select(

				DB::raw("'keyword' as type"),
				'keyword',
				DB::raw("LOWER(REPLACE(keyword, ' ', '-')) as slug")
			)
			->orderBy('keyword', 'asc')
			->limit(50)
			->get();

		// 🔹 Merge client data only when searching
		if (!empty($keyword)) {

			$clientData = DB::table('clients')
				->where('business_name', 'LIKE', "%{$keyword}%")
				->where('active_status', '1')
				->select(

					DB::raw("'company' as type"),
					DB::raw("business_name as keyword"),
					DB::raw("business_slug as slug")
				)
				->orderBy('keyword', 'asc')
				
				->limit(50)
				->get();

			$locations = $locations->merge($clientData);
		}

		// 🔹 Check Empty Properly
		if ($locations->isEmpty()) {
			return response()->json([
				'success' => false,
				'status' => false,
				'message' => 'No keyword found.',
			], 404);
		}

		// 🔹 Return Response
		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $locations->values(),
		], 200);
	}

	/**
	 * @OA\Get(
	 *     path="/api/website/business-details",
	 *     tags={"Website"},
	 *     summary="Website Search business details",
	 *     description="Search records dynamically based on a business slug",
	 *      
	 *      @OA\Parameter(
	 *         name="business_slug",
	 *         in="query",
	 *         required=false,
	 *         description="Filter by business_slug",
	 *         @OA\Schema(type="string", example="test-demo")
	 *     ),  
	 *        
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="data", type="array",
	 *                 @OA\Items(
	 *                     @OA\Property(property="id", type="integer", example=101),
	 *                     @OA\Property(property="name", type="string", example="ABC Coaching Center"),
	 *                     @OA\Property(property="city", type="string", example="Noida"),
	 *                     @OA\Property(property="category", type="string", example="Education"),
	 *                     @OA\Property(property="rating", type="number", format="float", example=4.5)
	 *                 )
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */


	public function businessDetails(Request $request)
	{
 
		$request->validate([
			'business_slug' => 'required|exists:clients,business_slug',
		]);
		$business_slug = $request->input('business_slug');

		$clientscheck = DB::table('clients')
			->leftJoin('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id')
			->leftJoin('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
			->leftJoin('citylists', 'assigned_kwds.city_id', '=', 'citylists.id')
			->leftJoin(DB::raw('(
        SELECT ROUND(AVG(rating), 1) AS average_rating, comment_client_ID, COUNT(comment_ID) AS comment_count
        FROM comments GROUP BY comment_client_ID
    ) c'), 'c.comment_client_ID', '=', 'clients.id')
			->select(
				'clients.*',
				'clients.id as business_id',
				'assigned_kwds.*',
				'citylists.city',
				'assigned_kwds.sold_on_position',
				'c.average_rating',
				'c.comment_count'
			)
			->where('clients.business_slug', $business_slug)
			 ->where('clients.active_status', '1')
			->orderByRaw("
        CASE clients.client_type
            WHEN 'platinum' THEN 1
            WHEN 'diamond' THEN 2
            WHEN 'gold' THEN 3
            WHEN 'silver' THEN 4
            ELSE 5
        END
    ")
			->first();
		 
		if (!empty($clientscheck)) {

			$logoImage = config('app.website') . 'client/images/default_pp_small.png';
			$altLogo = "Business Logo";
			if (!empty($clientscheck->logo)) {
				$cicons = unserialize($clientscheck->logo);

				if (!empty($cicons)) {
					$logoImage = config('app.website') . $cicons['large']['src'];
					$altLogo = $cicons['large']['name'];
				}
			}
			$profile_pic = config('app.website') . 'client/images/default_profile_pic.jpg';
			$altbanner = "";
			if (!empty($clientscheck->profile_pic)) {
				$banner = unserialize($clientscheck->profile_pic);

				if (!empty($banner)) {
					$profile_pic = config('app.website') . $banner['large']['src'];
					$altLogo = $clientscheck->business_name;
				}
			}

			$gallery = "";
			$altbanner = "";
			$galleryArray = array();
			if (!empty($clientscheck->pictures)) {
				$galleryList = unserialize($clientscheck->pictures);
				if (!empty($galleryList)) {
					foreach ($galleryList as $pkey => $gvalue) {
						$galleryArray[] = config('app.website') . $gvalue['large']['src'];

					}
				}
			}

			$assignedKeywords = DB::table('assigned_kwds')
				->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
				->where('assigned_kwds.client_id', $clientscheck->business_id)
				->orderBy('keyword', 'asc')
				->distinct()
				->pluck('keyword.keyword')
				->toArray();
			$assignedCity = DB::table('assigned_kwds')
				->join('citylists', 'assigned_kwds.city_id', '=', 'citylists.id')
				->where('assigned_kwds.client_id', $clientscheck->business_id)
				->distinct()
				->pluck('citylists.city')
				->toArray();

			$time = "";
			if ($clientscheck->time) {
				$time = json_decode($clientscheck->time);

			}

			$certified_img = config('app.website') . 'img/q_verified.gif';
			$trusted_img = config('app.website') . 'img/q_trust.gif';
			$gst_img = config('app.website') . 'img/q_gst.gif';

			$social = array(
				'facebook_url' => $clientscheck->facebook_url,
				'facebook_img' => '',
				'instagram_url' => $clientscheck->instagram_url,
				'instagram_img' => '',
				'twitter_url' => $clientscheck->twitter_url,
				'twitter_img' => '',
				'linkedin_url' => $clientscheck->linkedin_url,
				'linkedin_img' => '',
				'pinterest_url' => $clientscheck->pinterest_url,
				'pinterest_img' => '',
				'youtube_url' => $clientscheck->youtube_url,
				'youtube_img' => '',

			);


			$businessName = !empty($client->business_name) ? $clientscheck->business_name : 'our company';
			$data['comment'] = Comment::where('comment_client_ID', $clientscheck->business_id)
				->where('comment_approved', '1')
				->orderBy('created_at', 'desc')
				->get()
				->toArray();

			$sum = Comment::where('comment_client_ID', $clientscheck->business_id)
				->where('comment_approved', '1')
				->sum('rating');

			$count = Comment::where('comment_client_ID', $clientscheck->business_id)
				->where('comment_approved', '1')
				->count();

			$avgRating = 0;
			if ($count != 0)
				$avgRating = $clientscheck->average_rating;
		 
			$addressText = !empty($clientscheck->address) ? $clientscheck->address : '';
			$mapText = !empty($clientscheck->business_map) ? '\n Directions: ' . $clientscheck->business_map : '';
			$profile_url = 'https://www.quickdials.com/business-details/' . $clientscheck->business_slug;
			$keyword = "";
			$address_data = "Greetings from {$businessName},\n"
				. "We’re following up on your enquiry made on Quickdials for {$keyword}.\n"
				. "For more information"
				. (!empty($addressText) ? ", you can visit us at {$addressText}" : "")
				. "{$mapText}";

			$for_service = "Greetings from {$businessName},\n"
				. "We’re following up on your enquiry made on Quickdials for {$keyword}.\n"
				. "For more information of the services offered by our business please refer "
				. (!empty($addressText) ? ", you can visit us at {$addressText}" : "")
				. ", Or {$profile_url}";
			$for_review = "Greetings from {$businessName}, Rated {$avgRating} Rating out of {$count} Votes.\n"
				. "We’re following up on your enquiry made on Quickdials for {$keyword}.\n"
				. "For more information about the services offered by our business"
				. (!empty($addressText) ? ", you can visit us at {$addressText}" : "")
				. ". Or visit our profile: {$profile_url}";

			$user_share = array(
				'address_share' => $address_data,
				'for_service' => $for_service,
				'for_review' => $for_review,

			);
			$data['clientsList'] = [
				'business_id' => $clientscheck->business_id,
				'meta_title' => $clientscheck->meta_title,
				'meta_description' => $clientscheck->meta_description,
				'meta_keyword' => $clientscheck->meta_keyword,
				'business_name' => $clientscheck->business_name,
				'business_slug' => $clientscheck->business_slug,
				'business_url' => config('app.website') . 'business-details/' . $clientscheck->business_slug,
				'logo' => $logoImage ?? '',
				'altLogo' => $altLogo . ' Logo' ?? '',
				'profile_banner' => $profile_pic ?? '',
				'altbanner' => $altbanner ?? '',
				'gallery' => $galleryArray ?? '',
				'business_intro' => $clientscheck->business_intro,
				'assign_keyword' => $assignedKeywords,
				'service_city' => $assignedCity,
				'certifications' => $clientscheck->certifications,
				'sirName' => $clientscheck->sirName,
				'first_name' => $clientscheck->first_name,
				'middle_name' => $clientscheck->middle_name,
				'last_name' => $clientscheck->last_name,
				'email' => $clientscheck->email,
				'mobile' => $clientscheck->mobile,
				'call' => '917559435943',
				'whatsapp' => '917559435943',
				'certified_status' => $clientscheck->certified_status,
				'trusted_status' => $clientscheck->trusted_status,
				'gst_status' => $clientscheck->gst_status,
				'certified_img' => $certified_img,
				'trusted_img' => $trusted_img,
				'gst_img' => $gst_img,
				'website' => $clientscheck->website,
				'city' => $clientscheck->city,
				'state' => $clientscheck->state,
				'area' => $clientscheck->area,
				'zone' => $clientscheck->zone,
				'address' => $clientscheck->address,
				'pincode' => $clientscheck->pincode,
				'country_id' => $clientscheck->country,
				'country' => 'India',
				'year_of_estb' => $clientscheck->year_of_estb,
				'time' => $time,
				'landmark' => $clientscheck->landmark,
				'rating' => $clientscheck->average_rating,
				'ratingCount' => $clientscheck->comment_count,				 
				'social' => $social,
				'user_share' => $user_share,
			];
			$isoImage = "";
			if (!empty($clientscheck->iso_certificate)) {
				$iso_certificate = json_decode($clientscheck->iso_certificate);

				if (!empty($iso_certificate)) {
					$isoImage = config('app.website') . $iso_certificate->large->src;
				}
			}
			$gstImage = "";
			if (!empty($clientscheck->gst_certificate)) {
				$gst_certificate = json_decode($clientscheck->gst_certificate);

				if (!empty($gst_certificate)) {
					$gstImage = config('app.website') . $gst_certificate->large->src;
				}
			}
			
			$cinImage = "";
			if (!empty($clientscheck->cin_certificate)) {
				$cin_certificate = json_decode($clientscheck->cin_certificate);

				if (!empty($cin_certificate)) {
					$cinImage = config('app.website') . $cin_certificate->large->src;
				}
			}
			$panImage = "";
			if (!empty($clientscheck->pan_certificate)) {
				$pan_certificate = json_decode($clientscheck->pan_certificate);

				if (!empty($pan_certificate)) {
					$panImage = config('app.website') . $pan_certificate->large->src;
				}
			}
			$coiImage = "";
			if (!empty($clientscheck->coi_certificate)) {
				$coi_certificate = json_decode($clientscheck->coi_certificate);

				if (!empty($coi_certificate)) {
					$coiImage = config('app.website') . $coi_certificate->large->src;
				}
			}
			$dpiitImage = "";
			if (!empty($clientscheck->dpiit_certificate)) {
				$dpiit_certificate = json_decode($clientscheck->dpiit_certificate);

				if (!empty($dpiit_certificate)) {
					$dpiitImage = config('app.website') . $dpiit_certificate->large->src;
				}
			}

			$msmeImage = "";
			if (!empty($clientscheck->msme_certificate)) {
				$msme_certificate = json_decode($clientscheck->msme_certificate);

				if (!empty($msme_certificate)) {
					$msmeImage = config('app.website') . $msme_certificate->large->src;
				}
			}

			$awardimg1 = "";
			if (!empty($clientscheck->award_img1)) {
				$award_img1 = json_decode($clientscheck->award_img1);

				if (!empty($award_img1)) {
					$awardimg1 = config('app.website') . $award_img1->large->src;
				}
			}

			$awardimg2 = "";
			if (!empty($clientscheck->award_img2)) {
				$award_img2 = json_decode($clientscheck->award_img2);

				if (!empty($award_img2)) {
					$awardimg2 = config('app.website') . $award_img2->large->src;
				}
			}


			$awardimg3 = "";
			if (!empty($clientscheck->award_img3)) {
				$award_img3 = json_decode($clientscheck->award_img3);

				if (!empty($award_img3)) {
					$awardimg3 = config('app.website') . $award_img3->large->src;
				}
			}


			$awardimg4 = "";
			if (!empty($clientscheck->award_img4)) {
				$award_img4 = json_decode($clientscheck->award_img4);

				if (!empty($award_img4)) {
					$awardimg4 = config('app.website') . $award_img4->large->src;
				}
			}


			$awardimg5 = "";
			if (!empty($clientscheck->award_img5)) {
				$award_img5 = json_decode($clientscheck->award_img5);

				if (!empty($award_img5)) {
					$awardimg5 = config('app.website') . $award_img5->large->src;
				}
			}

			$awardimg6 = "";
			if (!empty($clientscheck->award_img6)) {
				$award_img6 = json_decode($clientscheck->award_img6);

				if (!empty($award_img6)) {
					$awardimg6 = config('app.website') . $award_img6->large->src;
				}
			}
			$awardimg7 = "";
			if (!empty($clientscheck->award_img7)) {
				$award_img7 = json_decode($clientscheck->award_img7);

				if (!empty($award_img7)) {
					$awardimg7 = config('app.website') . $award_img7->large->src;
				}
			}


				$awardimg8 = "";
			if (!empty($clientscheck->award_img8)) {
				$award_img8 = json_decode($clientscheck->award_img8);

				if (!empty($award_img8)) {
					$awardimg8 = config('app.website') . $award_img8->large->src;
				}
			}

				$awardimg9 = "";
			if (!empty($clientscheck->award_img9)) {
				$award_img9 = json_decode($clientscheck->award_img9);

				if (!empty($award_img9)) {
					$awardimg9 = config('app.website') . $award_img9->large->src;
				}
			}


				$awardimg10 = "";
				if (!empty($clientscheck->award_img10)) {
				$award_img10 = json_decode($clientscheck->award_img10);

				if (!empty($award_img10)) {
				$awardimg10 = config('app.website') . $award_img10->large->src;
				}
				}



			


			$data['certificate'] = [
				'gst_no' => $clientscheck->gst_no ?? null,
				'gst_certificate' => $gstImage,
				'pan_no' => $clientscheck->pan_no,
				'pan_certificate' => $panImage,
				'cin_no' => $clientscheck->cin_no ?? null,
				'cin_certificate' => $cinImage,
				'iso_no' => $clientscheck->iso_no ?? null,
				'iso_certificate' => $isoImage ?? null,
				'msme_no' => $clientscheck->msme_no ?? null,
				'msme_certificate' => $msmeImage ?? null,
				'coi_no' => $clientscheck->coi_no ?? null,
				'coi_certificate' => $coiImage ?? null,
				'dpiit_no' => $clientscheck->dpiit_no ?? null,
				'dpiit_certificate' => $dpiitImage ?? null,
				'award_name1' => $clientscheck->award_name1,
				'award_img1' => $awardimg1,
				'award_name2' => $clientscheck->award_name2,
				'award_img2' => $awardimg2,
				'award_name3' => $clientscheck->award_name3,
				'award_img3' => $awardimg3,
				'award_name4' => $clientscheck->award_name4,
				'award_img4' => $awardimg4,
				'award_name5' => $clientscheck->award_name5,
				'award_img5' => $awardimg5,
				'award_name6' => $clientscheck->award_name6,
				'award_img6' => $awardimg6,

				'award_name7' => $clientscheck->award_name7,
				'award_img7' => $awardimg7,

				'award_name8' => $clientscheck->award_name8,
				'award_img8' => $awardimg8,

				'award_name9' => $clientscheck->award_name9,
				'award_img9' => $awardimg9,

				'award_name10' => $clientscheck->award_name10,
				'award_img10' => $awardimg10,
			];

			$defaultImg = "";
			$recentActivity = [];

			for ($i = 1; $i <= 6; $i++) {
			$imgField  = "recent_img{$i}";
			$nameField = "recent_name{$i}";
			$paraField = "recent_paragraph{$i}";

			// Default image (used if no upload)
			$imgUrl = $defaultImg;

			// If image exists, decode JSON and build full URL
			if (!empty($clientscheck->$imgField)) {
			$decoded = json_decode($clientscheck->$imgField);
			if (!empty($decoded->large->src)) {
			$imgUrl = config('app.website') . $decoded->large->src;
			}
			}

			// Add to output array (matches your original flat structure)
			$recentActivity[$nameField] = $clientscheck->$nameField;
			$recentActivity[$imgField]  = $imgUrl;
			$recentActivity[$paraField] = $clientscheck->$paraField;
			}

			$data['recentActivity'] = $recentActivity;




			if (!empty($assignedKeywords)) {
				$findKeywords = Keyword::select('child_category_id')->where('keyword', $assignedKeywords[0])->first();

				$relKeywords = Keyword::select('keyword','slug')->where('child_category_id', $findKeywords->child_category_id)
					->orderBy('keyword', 'asc')
					->pluck('keyword.keyword','keyword.slug')
					->toArray();

				$data['related_searches'] = $relKeywords;
			}
			
			
				$businessName = $clientscheck->business_name ?? 'this business';
				$area         = $clientscheck->area ?? '';
				$city         = $clientscheck->city ?? '';
				$location     = trim($area . ($area && $city ? ', ' : '') . $city);

			$area_business = [
				'heading' =>
					($clientscheck->business_name ?? '') .
					' in ' .
					($clientscheck->area ?? '') .
					', ' .
					($clientscheck->city ?? ''),

				'paragraph' => "{$businessName}, located in {$location}, has built a strong reputation as a trusted name in {$city} for delivering professional, reliable, and customer-focused services. With years of hands-on experience, a skilled team, and a strong commitment to quality, {$businessName} caters to a wide range of customer needs across {$area} and nearby areas in {$city}, ensuring timely service, transparent pricing, and lasting results every time.",
			];

			$data['area_business'] = $area_business;

			$workingHoursHtml = '';

			if (!empty($clientscheck->time)) {

				$times = json_decode($clientscheck->time);
				$today = strtolower(date('l'));

				// Today
				if (isset($times->$today)) {
					$workingHoursHtml .= $times->$today->from . ' - ' . $times->$today->to;
				}

				// Other days
				foreach ($times as $day => $time) {
					$workingHoursHtml .= ucfirst($day) . ' ' . $time->from . ' - ' . $time->to;
				}

			} else {
				$workingHoursHtml .= '';
			}

			// ─── Extract once for clarity & to avoid repetition ───
			$businessName = $clientscheck->business_name ?? 'this business';
			$area         = $clientscheck->area ?? '';
			$city         = $clientscheck->city ?? '';
			$location     = trim($area . ($area && $city ? ', ' : '') . $city);

			// ─── Paragraph 1 ───
			$overviewParagraph = "{$businessName} in {$location} is a trusted service provider in {$city}, known for quality, reliability, and customer satisfaction. With experienced professionals, modern tools, and a strong commitment to service excellence, {$businessName} delivers consistent results every time. {$workingHoursHtml} The highly experienced team caters to a wide range of customer needs across {$area} and {$city}, offering flexible scheduling and personalized service to suit individual requirements.";

			// ─── Paragraph 2 ───
			$overviewParagraph2 = "Whether you need a one-time service or ongoing support, {$businessName} in {$location} has the right solution for you. With a wide range of offerings backed by professional handling and quality workmanship, {$businessName} stands as a comprehensive choice for customers across {$city}. From first contact to job completion, the team ensures transparent pricing, on-time service, and lasting quality outcomes. Get in touch with {$businessName} today to learn more or schedule a visit.";



			$overview_business = [
				'heading' => 'Overview of Business',
				'paragraph' => $overviewParagraph,
				'paragraph1' => $overviewParagraph2
			];

			$data['overview_business'] = $overview_business;
			return response()->json([
				'success' => true,
				'status' => true,
				'data' => $data,
			], 200);

		} else {
			return response()->json([
				'success' => false,
				'status' => false,
				'data' => [],
			], 200);

		}

	}

	/**
	 * @OA\Get(
	 *     path="/api/website/footer-links",
	 *     tags={"Website"},
	 *     summary="Website Footer links",
	 *     description="Display data home page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function footerLinks(Request $request)
	{
		$url = config('app.url');
		$data['quickLinks'] = [
			[
				'url' => '/',
				'title' => 'Home',

			],
			[
				'url' => 'about-us',
				'title' => 'About Us',

			],
			[
				'url' => 'business-owners',
				'title' => 'Featured Listings',

			],
			[
				'url' => 'pricing',
				'img' => config('app.website') . 'popular/CAT-exam.jpg',
				'alt' => 'CAT/NEET',
				'title' => 'CAT/NEET',
				'type' => 'keyword',

			],
			[
				'url' => 'ctet-coaching',
				'title' => 'Premium Plans',
				'type' => 'keyword',

			],
			[
				'url' => 'careers',
				'title' => 'Careers',

			],
			[
				'url' => 'blog',
				'title' => 'Success Stories',
			],
			[
				'url' => 'blog',
				'title' => 'Blog',
			],
			[
				'url' => 'business-owners',
				'title' => 'Advertise on quickdials',
			],
			[
				'url' => 'privacy-policy',
				'title' => 'Privacy Policy',
			],
			[
				'url' => 'terms-conditions',
				'title' => 'Terms & Conditions',
			],
			[
				'url' => 'copyright-policy',
				'title' => 'Copyright Policy',
			]


		];

		$data['popularCategories'] = [
			[
				'url' => 'professional-courses',
				'title' => 'Coaching & Tuitions',
				'type' => 'categories',

			],
			[
				'url' => 'wedding-pannel',
				'title' => 'Wedding Pannel',
				'type' => 'keyword',

			],
			[
				'url' => 'category/health-wellness',
				'title' => 'Healthcare',
				'type' => 'categories',

			],
			[
				'url' => 'category/real-estate-agent',
				'title' => 'Real Estate',
				'type' => 'categories',

			],
			[
				'url' => 'electric-services',
				'title' => 'Electric Services',
				'type' => 'child',

			],
			[
				'url' => 'security-system',
				'title' => 'Security System',
				'type' => 'categories',

			],
			[
				'url' => 'medical',
				'title' => 'Medical',
				'type' => 'categories',
			],
			[
				'url' => 'packers-movers',
				'title' => 'Packers Movers',
				'type' => 'categories',
			],
			[
				'url' => 'restaurants',
				'title' => 'Restaurants',
				'type' => 'keyword',
			],
			[
				'url' => 'hotels',
				'title' => 'Hotels',
				'type' => 'keyword',
			],
			[
				'url' => 'interior-designer',
				'title' => 'interior Design',
				'type' => 'keyword',
			]
		];
		$data['businessServicesLink'] = [
			[
				'url' => 'patient-care-service',
				'title' => 'Patient Care Service',
				'type' => 'keyword',

			],
			[
				'url' => 'home-appliance-repair-training',
				'title' => 'Home Appliances Repair',
				'type' => 'keyword',

			],
			[
				'url' => 'wedding-organisers',
				'title' => 'Wedding Organisers',
				'type' => 'keyword',

			],
			[
				'url' => 'ac-service',
				'title' => 'AC Services',
				'type' => 'keyword',

			],
			[
				'url' => 'security-guards-services',
				'title' => 'Security Guards',
				'type' => 'keyword',

			],
			[
				'url' => 'cleaning-services',
				'title' => 'Cleaning Services',
				'type' => 'keyword',

			],
			[
				'url' => 'repairs-services',
				'title' => 'Repairs Services',
				'type' => 'categories',
			],
			[
				'url' => 'spa-beauty',
				'title' => 'SPA Beauty',
				'type' => 'categories',
			],
			[
				'url' => 'loan',
				'title' => 'Loan',
				'type' => 'child',
			],
			[
				'url' => 'income-tax-consultants',
				'title' => 'Tax Consultants',
				'type' => 'keyword',
			],
			[
				'url' => 'interviews',
				'title' => 'Interviews Question',
			]
		];

		if ($data) {
			$data['status'] = true;

			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;

			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}
	/**
	 * @OA\Get(
	 *     path="/api/website/about-us",
	 *     tags={"Website"},
	 *     summary="Website about-us records",
	 *     description="Display data home page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function aboutus(Request $request)
	{
		$url = config('app.url');
		$data['about-us'] =
			[
				'paragraph1' => 'Quick Dials, one of the most promising start-ups lead generation in India in 2023, offers a B2C model as a match-making admission solution to students, professionals study and services. Their quickdials.com, provide platform connects education seekers with education providers, coupled with excellent career counseling services.',
				'paragraph2' => 'Quick Dials is an extensive search engine for students, parents, professionals, and education industry players seeking information on the education sector in India. Users can rely on quickdials.com for the most relevant data on institutes, colleges, and universities.',
				'Vision' => 'Quick Dials has been created to fulfill a vision of providing quality education through certified institutions. In the modern era, we aim to unify educational societies with the help of technology to build a better nation.',
				'USPs' => 'Quick Dials is the first technology-driven education start-up in India, providing a match-making solution for students educational needs across all sectors. Quick Dials offers extensive in-house personalized counseling to understand each students needs and help them make the most informed decisions.',
				'Quick Dials For Institutions' => 'Quick Dials provides a non-conventional platform that focuses on delivering quality leads and highly motivated candidates. Our extensive in-house one-on-one personalized counseling gives us an edge in offering a highly specific and active database to our clients.',
				'Quick Dials For Students' => 'Students can use Quick Dials as a one-stop destination to search for information on coaching institutes, IT training centers, overseas education consultants, available courses, college admission processes, and much more. The website offers interactive tools to simplify the process of finding the right alma mater. Quick Dials has a repository of over 1,000 institutes, coaching centers, schools, colleges, and 10,000 courses categorized into different streams such as IT training, civil services, entrance exam preparation, management, engineering, medical, arts, distance education, and more. Users can classify their education needs based on location, reviews, and certification. Quick Dialss certified business partners ensure quality education, campus placements, top faculty, and fee refund assurance, providing students with a reliable and comprehensive platform for their educational needs.',
				'Quality Leads' => 'Generate high-quality leads for educational institutions and service providers.',
				'Targeted Marketing' => 'Reach specific demographics and target audiences based on location, interests, and educational needs.',
				'Personalized Counselling' => 'Extensive in-house one-on-one counseling helps in understanding the needs and preferences of potential students.
				Provides educational institutions with detailed insights and qualified leads.',
				'Interactive Platform' => 'Use interactive tools and features to engage with potential students.
				Facilitate direct communication between students and educational institutions.',
				'Database Access' => 'Access to a vast database of students looking for various courses and educational opportunities.
				Detailed profiles and data to help institutions tailor their offerings.',
				'Reviews and Ratings' => 'Leverage user reviews and ratings to build credibility and attract more leads.
				Positive feedback and testimonials can enhance reputation and lead generation.',
				'Certified Partnerships' => 'Being a Quick Dials Certified Business Partner boosts credibility and trust.
				Assurance of quality education and services attracts more leads.',
				'Analytics and Reporting' => 'Detailed analytics and reporting tools to track the effectiveness of lead generation efforts.
				Insights into student preferences and behavior to refine marketing strategies.',
				'Location-Based Leads' => 'Generate leads based on specific geographic locations to target local students.
				Customize offerings to meet the needs of the local student population.',
				'Engagement Tools' => 'Use forums, discussion boards, and community features to engage with potential leads.
				Foster a sense of community and belonging to attract more students.',
				'Career Counselling Integration' => 'Integrate career counseling services to attract students looking for career guidance.
				Position your institution as a comprehensive solution for education and career planning.',
				'Clearing of doubts using chat with counselo' => [
					'Real-Time Support' => 'Students can chat with certified counselors in real-time to get their queries answered promptly. Immediate assistance for any doubts regarding courses, admissions, career paths, and more',
					'Personalized Guidance' => 'One-on-one chat sessions to provide tailored advice based on individual student needs and preferences.
				Helps in making informed decisions about educational and career choices.',
					'Convenient and Accessible' => 'Accessible through both desktop and mobile platforms, ensuring students can reach counselors anytime, anywhere.
				User-friendly interface to facilitate smooth communication.',
					'Comprehensive Assistance' => 'Counselors can provide information on a wide range of topics, including course details, admission processes, scholarship opportunities, and more.
				Support for both academic and career-related inquiries.',
					'Interactive Tools' => 'Use of interactive features such as document sharing, video calls, and screen sharing to enhance the chat experience.
				Enables a more thorough and interactive counseling session.',
					'Confidential and Secure' => 'Ensures privacy and confidentiality of student information during chat sessions.
				Secure platform to protect sensitive data and maintain trust.',
					'Follow-Up Support' => 'Counselors can schedule follow-up sessions to ensure all doubts are cleared and students are on the right path.
				Continuous support throughout the decision-making process.',
					'Feedback Mechanism' => 'Students can provide feedback on their chat experience, helping to improve the quality of counseling services.
				Counselors can track the effectiveness of their guidance and make necessary adjustments.',
					'Resource Sharing' => 'Counselors can share links, documents, and other resources directly through the chat to assist students.
				Access to additional reading material, application forms, and relevant websites.',
					'Integration with Other Features' => 'Seamless integration with other platform features such as course comparisons, application tracking, and reviews.
				Comprehensive support system combining various tools for a holistic counseling experience.',
				],
				'Real interactive class room and expert faculty Techer' => [
					'Live Virtual Classes' => 'Real-time interactive sessions conducted by expert faculty members.
					Engage students with dynamic content delivery and interactive learning tools.',
					'Expertise and Experience' => 'Faculty members with extensive knowledge and experience in their respective fields.
					Provide insights, practical examples, and industry-relevant knowledge.',
					'Engagement Tools' => 'Use of interactive tools such as polls, quizzes, and live Q&A sessions to keep students engaged.
					Foster active participation and discussion among students.',
					'Personalized Learning' => 'Tailored teaching approaches to address individual learning styles and preferences.
					Adaptive learning techniques to cater to diverse student needs.',
					'Collaborative Learning Environment' => 'Facilitate group discussions, peer-to-peer interaction, and collaborative projects.
					Encourage teamwork and communication skills development.',
					'Hands-on Activities' => 'Incorporate practical demonstrations, case studies, and simulations to enhance learning outcomes.
					Bridge theoretical knowledge with real-world applications.',
					'Multimedia Integration' => 'Utilize multimedia resources such as videos, presentations, and virtual labs to enrich the learning experience.
					Enhance understanding and retention of complex concepts.',
					'Feedback and Assessment' => 'Provide immediate feedback on assignments, assessments, and student progress.
					Continuous evaluation to track learning outcomes and address areas of improvement.',
					'Accessible Learning Platform' => 'Accessible through desktop and mobile devices, ensuring flexibility in learning.
					Seamless integration with learning management systems for easy navigation and resource access.',
					'Continuous Improvement' => 'Faculty regularly update content and teaching methods based on student feedback and industry trends.
					Commitment to delivering high-quality education and enhancing the learning experience.',
				],


			];
		if ($data) {
			$data['status'] = true;

			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;

			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}


	/**
	 * @OA\Get(
	 *     path="/api/website/faq",
	 *     tags={"Website"},
	 *     summary="Website FAQ's",
	 *     description="Display data home page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function FAQ(Request $request)
	{
		$url = config('app.url');
		$data['FAQ'] = [
			[
				'q1' => 'What is Quick Dials?',
				'a1' => 'Quick Dials is an extensive search engine for the students, parents, and Professionals, Quick Dials Only Deals In Education Sector and helps students to grab their right opportunity, and helps business owners to grow their business.',
			],
			[
				'q2' => 'Why choose Quick Dials for growing your business?',
				'a2' => 'Our Work Module Is Completely Different, we work on the Conversion module, and we Provide you  Dual Manually Verified Leads to your Business.',
			],
			[
				'q3' => 'What Happen If my leads commitment did not get fulfilled?',
				'a3' => 'In case we are unable to fulfill our committed no of leads, we will refund your remaining amount.',
			],
			[
				'q4' => 'What happens if I received a lead from out of my city/category?',
				'a4' => 'If you get a lead which is either out from your Locality or Category Then we will replace it as soon as possible.',
			],
			[
				'q5' => 'How Diffrent your leads quality from others?',
				'a5' => 'our leads are dual manually verified by our expert counselors, so no need to worry about it.',
			],
			[
				'q6' => 'How do you generate leads?',
				'a6' => 'we generate leads organically as well as inorganic leads, and we have Our own channels partners.',
			],
			[
				'q7' => 'How will I get Leads?',
				'a7' => 'You will receive Leads on your Registered contact no through sms, and also on Your registered Email Id.',
			],
			[
				'q8' => 'I Need More info?',
				'a8' => 'For More Info & any Queries, you can Contact Us on +91  75-5943-5943 or reach out to us via e-mail @ info@quickdials.com, or list your business as free listing, our marketing team Will Contact you Soon.',
			],
		];
		if ($data) {
			$data['status'] = true;

			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;

			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}


	/**
	 * @OA\Get(
	 *     path="/api/website/contact-us",
	 *     tags={"Website"},
	 *     summary="Website Contact-us",
	 *     description="Display data home page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function contactus(Request $request)
	{
		$url = config('app.url');
		$data['registrad_office'] = "Registrad Office";
		$data['quick_dials'] = "Quick Dials Internet Pvt Ltd";
		$data['registrad_address'] = "UNIT 101 OXFORD TOWERS, 139/88 HAL OLD AIRPORT RD, H.A.L II Stage, Bangalore North, Bangalore- 560008, Karnataka";
		$data['phone'] = "+91-75-5943-5943";
		$data['WhatsApp'] = "+91-75-5943-5943";
		$data['email'] = "info@quickdials.com";
		$data['CIN'] = "U63112KA2026PTC215594";
		$data['head_branch'] = "UNIT 101 OXFORD TOWERS, 139/88 HAL OLD AIRPORT RD, H.A.L II Stage, Bangalore North, Bangalore- 560008, Karnataka Pin Code:- 560002, India";
		$data['branch'] = "G-13 Sector-3, Noida , india , 201301";
		if ($data) {
			$data['status'] = true;

			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;

			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}


	/**
	 * @OA\Get(
	 *     path="/api/website/pricing",
	 *     tags={"Website"},
	 *     summary="Website Pricing",
	 *     description="Display data home page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function pricing(Request $request)
	{
		$url = config('app.url');
		$data['packages'] = [
			[
				'amount' => "0",
				'coins' => "555",
				'point-1' => "Long time system login access",
				'point-2' => "Online system",
				'point-3' => "Full access",
				'point-4' => "Push Notification",
				'point-5' => "Roles & Permissions",
				'point-6' => "Coins(555)Free First Time",

			],

			[
				'amount' => "1000",
				'coins' => "1111",
				'point-1' => "Long time system login access",
				'point-2' => "Online system",
				'point-3' => "Full access",
				'point-4' => "Push Notification",
				'point-5' => "Roles & Permissions",
				'point-6' => "Coins(1111)",
			],

			[
				'amount' => "2000",
				'coins' => "2272",
				'point-1' => "Long time system login access",
				'point-2' => "Online system",
				'point-3' => "Full access",
				'point-4' => "Push Notification",
				'point-5' => "Roles & Permissions",
				'point-6' => "Coins(2272)",
			],
			[
				'amount' => "3000",
				'coins' => "3529",
				'point-1' => "Unlimited Users Access",
				'point-2' => "Online system",
				'point-3' => "Full access",
				'point-4' => "Push Notification",
				'point-5' => "Roles & Permissions",
				'point-6' => "Coins(3529)",
			],
			[
				'amount' => "5000",
				'coins' => "6099",
				'point-1' => "Unlimited Users Access",
				'point-2' => "Online system",
				'point-3' => "Full access",
				'point-4' => "Push Notification",
				'point-5' => "Roles & Permissions",
				'point-6' => "Coins(6099)",
			],
			[
				'amount' => "10000",
				'coins' => "12500",
				'point-1' => "Unlimited Users Access",
				'point-2' => "Online system",
				'point-3' => "Full access",
				'point-4' => "Push Notification",
				'point-5' => "Roles & Permissions",
				'point-6' => "Coins(12500)",
			],
			[
				'amount' => "20000",
				'coins' => "27777",
				'point-1' => "Unlimited Users Access",
				'point-2' => "Online system",
				'point-3' => "Full access",
				'point-4' => "Push Notification",
				'point-5' => "Roles & Permissions",
				'point-6' => "Coins(27777)",
			],
			[
				'amount' => "40000",
				'coins' => "57777",
				'point-1' => "Unlimited Users Access",
				'point-2' => "Online system",
				'point-3' => "Full access",
				'point-4' => "Push Notification",
				'point-5' => "Roles & Permissions",
				'point-6' => "Coins(57777)",
			],
			[
				'amount' => "50000",
				'coins' => "76923",
				'point-1' => "Unlimited Users Access",
				'point-2' => "Online system",
				'point-3' => "Full access",
				'point-4' => "Push Notification",
				'point-5' => "Roles & Permissions",
				'point-6' => "Coins(76923)",
			],





		];




		if ($data) {
			$data['status'] = true;

			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;

			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}
	/**
	 * @OA\Get(
	 *     path="/api/website/privacy-policy",
	 *     tags={"Website"},
	 *     summary="Website Privacy Policy",
	 *     description="Display data home page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function privacyPolicy(Request $request)
	{
		$url = config('app.url');

		$data['Quick Dials Terms of Use'] = "Quick Dials Terms of Use";
		$data['quick_dials'] = "Quick Dials is an online networking community that connects its members through a variety of services. This document outlines the terms and conditions governing the provision of these services.";

		$data['term_point'] = [
			"Your registration as a member of Quick Dials or the use of any of the features and services on quickdials.com constitutes acceptance of our terms and conditions. Either as a registered member or as a visitor constitutes automatic acceptance of these terms and conditions.",

			"Quick Dials reserves the right to update the terms, conditions and notices of this agreement without notice to you. It is your responsibility to periodically review the most current version of this Agreement.",

			"By accessing or using the Sites, Content, or Services, you agree to be bound by these Terms of Service.",
			"If you do not agree with any of the terms and conditions of Quick Dials, do not register and you will not be authorized to use Quick Dials services.",
			"The views expressed on the website are not those of Quick Dials, and any issues in them belong to the respective contributors.",

			"You are responsible for safeguarding the password that you use to access the Sites, Content and Services. You agree not to disclose your password to any third party. You agree to take sole responsibility for any activities or actions under your password, whether or not you have authorized such activities or actions.",
			"You may not make any content item originating from Quick Dials available for public access by any means whatsoever without obtaining prior written permission from Quick Dials.",

			"The content on the website is posted by Quick Dials, visitors and its members. Quick Dials will attempt to ensure the integrity and the accuracy of the Site in the Content and or Services but it does not guarantee that the information is accurate or complete or current. Quick Dials cannot be held liable for inaccuracy of any data listed on the website or any damage caused by the use of inaccurate data. It is Quick Dials’s policy to correct any inaccuracy reported within 7 days.",
			"The opinions and reviews expressed on the site belong to the users and Quick Dials cannot be held liable in any way about the content of the opinions and reviews. The platform allows users to express their views about different schools, teachers and training centres.",
			"Information about the schools posted on the site is obtained from their official websites, other source on the internet and in some cases by calling the schools.",
			"Quick Dials should not be thought of as the authority and the final guide in your decision making.",
			"Quick Dials at its sole discretion may edit, delete or block access to any Content including Member Posted Content, without notice and without liability. We will however make reasonable efforts to inform you of the changes.",
			"By uploading the logo of your institute, you give Quick Dials the right to use the logo on Quick Dials website as well as on any Quick Dialsmarketing material. If you are a visitor on our website and if you update any personal contact information such as phone number or e-mail address, Quick Dials reserves the right to contact you over Phone calls, SMS or E-mail. If the intention was for tutoring/learning, or seeking information about our product & services, Quick Dials team will guide you accordingly via phone call, SMS or e-mail.",
			"While using the web site and engaged in any form of communication on any of the forums, you agree not to:",
			"Post, publish or transmit any messages that is false, misleading, defamatory, harmful, threatening, abusive, harassing, defamatory, invades another's privacy, offensive, promotes racism, hatred or harm against any individual or group or religion or caste, infringes another's rights including any intellectual property rights or copyright or trademark, violates or encourages any conduct that would violate any applicable law or regulation or would give rise to civil liability.",
			"Upload or post or otherwise make available any Content that infringes any patent, trademark, trade secret, copyright or other proprietary rights of any party. You may, however, post excerpts of copyrighted material so long as they adhere to Fair Use guidelines.",
			"Collect screen names and email addresses of members for purposes of advertisement, solicitation or spam are prohibited.",
			"Quick Dials doesn't allow registration for Home Tuition Agencies & Organizations who are engaged in providing products or services similar to that of Quick Dials (or) who is engaged in collection of data from LeQuick Dials website and sharing/utilizing it for the benefit of competitors. If there are any such registrations, Quick Dials reserves the right to terminate those accounts without any prior notice & without processing the refund of subscription associated to those accounts. Quick Dials also reserves the right to initiate any legal proceedings if any 'Home Tuition Agencies or Organizations' contravenes condition as stated above.",
			"Before availing any of our advertising packages - Sponsored Listing, Branding Package and Banner Ad, kindly understand the benefits the packages offer. The payment made for any of our Branding packages is non-refundable.",
			"In case you have any doubt or query, please drop an email at help@quickind.com. We’ll get back to you within 3 working days.",
			"Attempt to probe, scan, or test the vulnerability of website or breach any security or authentication measures.",
			"Access or search the Sites Content or Services with any engine, software, or tool.",
			"Send unsolicited email, junk mail, spam, or chain letters, or promotions or advertisements for products or services.",
			"Reformat or frame any portion of the web pages that are part of the Quick Dials Site without a written agreement.",
			"Create user accounts by automated means or under false or fraudulent pertness.",
			"Post text, messages, graphics or materials that are sales offers, advertisements, or promotions for products or services.",
			"Quick Dials reserves the right at any time and from time to time to modify or discontinue, temporarily or permanently, the Services with or without notice.",

		];
		$data['venue_only'] = "Venue Only";
		$data['paragraph1'] = "If you enter into correspondence or engage in commercial transactions with third parties in connection with your use of the Quick DialsService, such activity is solely between you and the applicable third party. Quick Dialsshall have no liability, obligation or responsibility for any such activity. You hereby release Quick Dials from all claims arising from such activity.";
		$data['Ownership'] = "Except for the Content submitted by members or users, the Quick DialsService and all aspects thereof, including all copyrights, trademarks, and other intellectual property or proprietary rights therein, is owned by Quick Dials or its licensors. You acknowledge that the Quick Dials and any underlying technology or software used in connection with the Quick DialsService contain Quick Dials’S proprietary information. You may not modify, reproduce, distribute, create derivative works of, publicly display or in any way exploit, any of the content, software, and/or materials available on the Quick Dials Site, or Quick DialsServices in whole or in part except as expressly provided in Quick Dials's policies and procedures. Except as expressly and unambiguously provided herein, Quick Dials and its suppliers do not grant you any express or implied rights, and all rights in the Quick Dials Service not expressly granted by Quick Dials to you are retained by Quick Dials.";
		$data['limitation_of_liability'] = [
			"Limitation of Liability",

			"the site, content, and services are provided as is, without warranty or condition of any kind, either expressed or implied. in no event shall quick dials be liable for any direct, indirect, incidental, special, punitive, consequential damages whatsoever, including, but not limited to, damages for loss of profits, goodwill, use, data, or other intangible losses resulting from the use or the inability to use our services",
			"QuickDials makes no warranty that the sites, content, or services will meet your requirements or be available on an uninterrupted, secure, or error-free basis",
			"QuickDials makes no warranty regarding the quality of any products, services, accuracy, timeliness, truthfulness, completeness or information purchased or obtained through the sites, content or services"

		];
		$data['applicable'] = [
			"Applicable law and dispute resolution",

			"If any dispute, controversy or claim arises out of, or in relation to, or in connection with this Agreement or its termination or validity, the parties shall attempt to mutually resolve the same through mediation.",
			"However, if the parties fail to resolve the above dispute within a period of 30 days, the same shall be referred to arbitration under the Arbitration and Conciliation Act, 1996.",
			"The arbitration shall be conducted by a sole arbitrator, appointed by Quick DialsThe place of arbitration shall be in noida, India and it shall be conducted in English.",
			"The award of the arbitrator shall be final, conclusive and binding upon the parties The courts in noida shall have exclusive jurisdiction in relation to any dispute arising between the User and the Service Provider with Quick Dials.",
			"The User and Service Provider agree that regardless of any statute or law to the contrary, any claim or cause of action arising out of or related to the use of this Platform or these Terms and Conditions must be filed within one (1) year. Any claim or cause of action which may arise or is filed after a period of one year from the date of transaction shall not be entertained and shall be barred."
		];



		if ($data) {
			$data['status'] = true;

			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;

			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}

	/**
	 * @OA\Get(
	 *     path="/api/website/terms-conditions",
	 *     tags={"Website"},
	 *     summary="Website Terms Conditions",
	 *     description="Display data home page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function termsConditions(Request $request)
	{
		$url = config('app.url');

		$data['services_rovided'] = [
			"Services Provided",
			"Quick Dials is a local search and lead generation platform that connects users with businesses, service providers, and professionals.",
			"We provide information such as business listings, contact details, addresses, categories, and promotional offers.",
			"Leads generated through our platform are not guaranteed conversions. We only facilitate communication between users and service providers."

		];
		$data['user_eligibility'] = [
			"User Eligibility",
			"You must be at least 18 years old to use our services.",
			"By registering, you confirm that the information you provide is accurate, complete, and up-to-date.",
			"Any false, misleading, or fraudulent information may lead to suspension or termination of your account."

		];
		$data['payments_subscriptions'] = [
			"Payments & Subscriptions",
			"Some services may require paid subscriptions or listing fees.",
			"Payment terms, billing cycles, and refund policies will be displayed at the time of purchase.",
			"Quick Dials reserves the right to modify pricing at any time with prior notice."

		];
		$data['business_listings_leads'] = [
			"Business Listings & Leads",
			"Businesses listed on Quick Dials are responsible for ensuring their information is correct and updated.",
			"Quick Dials does not verify every listing and is not responsible for inaccuracies in business data.",
			"Leads provided are based on user inquiries; we do not guarantee quality, authenticity, or conversion of leads."

		];
		$data['intellectual_property'] = [
			"Intellectual Property",
			"All content, logos, designs, software, and trademarks on Quick Dials are our intellectual property.",
			"You may not copy, reproduce, distribute, or exploit our content without written permission.",


		];
		$data['third_party_links_services'] = [
			"Third-Party Links & Services",
			"Quick Dials may display links, ads, or listings from third-party businesses.",
			"We are not responsible for the quality, safety, legality, or accuracy of third-party products or services.",
			"Transactions between you and a third party are at your own risk.",

		];





		if ($data) {
			$data['status'] = true;

			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;

			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}

	/**
	 * @OA\Get(
	 *     path="/api/website/copyright-policy",
	 *     tags={"Website"},
	 *     summary="Website Copyright Policy",
	 *     description="Display data home page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function copyrightPolicy(Request $request)
	{
		$url = config('app.url');

		$data['intellectual_property'] = [
			"Intellectual property rights infringement claims",
			" Quick Dials Internet Pvt. Ltd. ('Quick Dials') respects the Intellectual Property Rights, including but not limited to patent, copyright, design, trademark, service mark, trade names, data and media (images, illustrations, audio clips, and video clips, among others) ('IP') of others, and prohibits users from uploading, posting, distributing or otherwise transmitting any materials on the Platform, or engaging in any activities on the Platform, which violate the copyrights of others.",
			"Any and all IP that in any manner forms part of and / or belongs to and / or associated with the Platform, shall vest in and exclusively belong to Quick Dials. All other IP on this Platform belongs to their respective owners.",
			"IP on this Platform is solely for your personal and non-commercial use. Any use of the IP for which you receive any remuneration, whether in money or otherwise would be considered to be a commercial use. Further, use of IP in any manner without the prior consent of the respective owner or Quick Dials is prohibited and the same would be considered to be a violation of the Intellectual Property Rights of Quick Dials or of the respective owner. Such violation can lead to termination of your account on this Platform. Quick Dials further reserve its right to take appropriate legal actions against such violation of its IP rights."

		];
		$data['takedown_notice'] = [
			"Takedown notice",
			"Pursuant to Rule 75(1) of the Copyright Rules, 2013 and other applicable enactments /amendments there to, in-order to report any material / work on the Platform that violates the copyrights of others, you must send Quick Dials a written communication that includes substantially the following:-.",
			"The description of the work with the adequate information to identify the work.",
			"Details establishing that the complainant is the owner or exclusive licensee of copyright in the work.",
			"Details establishing that the copy of the work which is the subject matter of transient or incidental storage is an infringing copy of the work owned by the complainant and that the allegedly infringing act is not covered under section 52 or any other act that is permitted under the Copyright Act 1957.",
			"Details of the location where transient or incidental storage of the work is taking place.",
			"Details of the person, if known, who is responsible for uploading the work infringing the copyright the copyright of the complaint.",
			"Undertaking that the complainant shall file an infringement suit in the competent court against the person responsible for uploading the infringing copy and produce orders of the competent court having jurisdiction, within a period of twenty-one days from the date of receipt of the notice.",

		];
		$data['copyright_administrator'] = [
			"Quick Dials's copyright administrator",
			"The foregoing written communications (i.e., the above-described takedown notice) must be sent to the following agent of Quick Dials:",
			" Quick Dials Internet Pvt. Ltd.",
			"UNIT 101 OXFORD TOWERS, 139/88 HAL OLD AIRPORT RD, H.A.L II Stage, Bangalore North, Bangalore- 560008, Karnataka",
			"E-mail: help@quickdials.com",
			"Website: www.quickdials.com.",
		];

		$data['disclaimers'] = [
			"Quick Dials suggests that you consult with your Advocate before you file any of the foregoing written communications (i.e., the above-described takedown notices, and the above described counter-notice). Any person who knowingly materially misrepresents that material found on the Platform is infringing, or that material was removed from the Platform by mistake or misidentification, may expose himself / herself to liability.",
			"Quick Dials understands that not everyone is a copyright expert, and that accidents can happen. However, Quick Dials has zero tolerance for wilful and repeat copyright infringers. Therefore, pursuant to a complaint, if Quick Dials determines in its sole discretion that you have wilfully violated the copyrights of others or that you have repeatedly violated the copyrights of others despite prior warning(s)",
			"Quick Dials will cancel your account and prohibit you from further accessing and using the Platform. By accessing or using the Platform, you automatically acknowledge and agree that Quick Dials has the right to cancel your account and prohibit you from further accessing and using the Platform, and your continuing access or use of the Platform reaffirms your acknowledgment and agreement in each instance.",

		];





		if ($data) {
			$data['status'] = true;

			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;

			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}


	/**
	 * @OA\Get(
	 *     path="/api/website/business-owners",
	 *     tags={"Website"},
	 *     summary="Website business signup",
	 *     description="Display data home page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function businessOwners(Request $request)
	{
		$url = config('app.url');


		$clients = Client::get()->count();
		$keyword = Keyword::get()->count();
		$citieslists = Citieslists::get()->count();

		$childCategory = ChildCategory::get()->count();
		$parentCategory = ParentCategory::get()->count();
		$lead = Lead::get()->count();

		$data['businessOwners'] = [
			
			 	'GrowClient' => $clients . ' +',
				'Suppliers' => $childCategory . ' +',
				'ProductsServices' => $citieslists . ' K+',
				'Keyword' => $keyword . ' +',
				'Store' => $parentCategory . ' +',
				'Platform' => $parentCategory . ' K+',

		];
		
		
		$data['about-us'] = [

				'paragraph' => 'Quick Dials is a fast-growing service search and lead platform in India. It helps people find the right service providers in one place. The platform works on a simple match-making idea. Users search for a service, and Quick Dials connects them with the right providers. The website QuickDialsTM makes it easy to search, compare, and contact service providers without confusion.',
				'paragraph1' =>'Quick Dials works like a search engine for everyday services and professional needs. People use it to find trusted and verified service providers across many fields. The information on the platform is clear, updated, and easy to understand.',

				];
				
				$defaultLogo = config('app.website') . 'client/images/default_pp_small.png';
				
		$success_story = DB::table('clients')
		->join('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id')
		->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
		->leftJoin(DB::raw('(
		SELECT 
		comment_client_ID,
		SUM(rating) AS total_rating,
		COUNT(comment_ID) AS comment_count,
		MAX(comment_author) AS comment_author,
		MAX(comment_content) AS comment_content
		FROM comments
		GROUP BY comment_client_ID
		) c'), 'c.comment_client_ID', '=', 'clients.id')
		->select(
		'clients.id as business_id',
		'clients.business_slug as business_slug',
		'clients.business_name',
		'clients.logo',
		'clients.client_type',
		DB::raw('COALESCE(c.total_rating, 0) as rating'),
		DB::raw('COALESCE(c.comment_count, 0) as comment_count'),
		'c.comment_author',
		'c.comment_content'
		)
	 
		->whereNotNull('c.comment_content')
		->where('clients.active_status', '1')
		->groupBy(
		'clients.id'     
		)
		->orderByRaw("
		CASE clients.client_type
		WHEN 'platinum' THEN 1
		WHEN 'diamond'  THEN 2
		WHEN 'gold'     THEN 3
		WHEN 'silver'   THEN 4
		ELSE 5
		END
		")
		->get()
		->map(function ($business) use ($defaultLogo) {

	 
		$cicons = @unserialize($business->logo);

		if ($cicons !== false && isset($cicons['large']['src'], $cicons['large']['name'])) {
		$business->logo_image = config('app.website') . $cicons['large']['src'];
		$business->alt_logo   = $cicons['large']['name'];
		} else {
		$business->logo_image = $defaultLogo;
		$business->alt_logo   = 'Business Logo';
		}

	 
		$business->avg_rating = $business->comment_count > 0
		? round($business->rating / $business->comment_count, 1)
		: 0;

	 
		unset($business->logo);

		return $business;
		});
		$data['success_story'] = $success_story;

			
		


				
		$reviewList = DB::table('clients')
		->join('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id')
		->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
		->leftJoin(DB::raw('(
		SELECT 
		comment_client_ID,
		SUM(rating) AS total_rating,
		COUNT(comment_ID) AS comment_count,
		MAX(comment_author) AS comment_author,
		MAX(comment_content) AS comment_content
		FROM comments
		GROUP BY comment_client_ID
		) c'), 'c.comment_client_ID', '=', 'clients.id')
		->select(
		'clients.id as business_id',
		'clients.business_slug as business_slug',
		'clients.business_name',
		'clients.logo',
		'clients.client_type',
		DB::raw('COALESCE(c.total_rating, 0) as rating'),
		DB::raw('COALESCE(c.comment_count, 0) as comment_count'),
		'c.comment_author',
		'c.comment_content'
		)
	 
		->whereNotNull('c.comment_content')
		->groupBy(
		'clients.id'       
		)
		->orderByRaw("
		CASE clients.client_type
		WHEN 'platinum' THEN 1
		WHEN 'diamond'  THEN 2
		WHEN 'gold'     THEN 3
		WHEN 'silver'   THEN 4
		ELSE 5
		END
		")
		->limit(10)
		->get() 
		->map(function ($business) use ($defaultLogo) {

	 
		$cicons = @unserialize($business->logo);

		if ($cicons !== false && isset($cicons['large']['src'], $cicons['large']['name'])) {
		$business->logo_image = config('app.website') . $cicons['large']['src'];
		$business->alt_logo   = $cicons['large']['name'];
		} else {
		$business->logo_image = $defaultLogo;
		$business->alt_logo   = 'Business Logo';
		}

	 
		$business->avg_rating = $business->comment_count > 0
		? round($business->rating / $business->comment_count, 1)
		: 0;

		 
		unset($business->logo);

		return $business;
		});
		$data['reviewList'] = $reviewList; 
				
				
				
		$data['grow_your_business'] = [
			
				"Grow Your Business" => "Sell to buyers anytime, anywhere",
				"Zero Cost" => "No commission or transaction fee",
				"Manage Your Business Better" => "Lead Management System & other features",
				"Create Account" => "Add your name and phone number to get started",
				"Add Business" => "Add the name, e-mail of your company, store/business.",
				"Add Products/Services" => "Minimum 3 products/services needed for your free listing page.",

			

		];
		
		
		$data['business_Features'] = [
			[
				"📍Google Maps Optimization" => "Improve your local visibility by optimizing Google Business Profile with keywords, reviews.",
				"img" => "https://www.quickdials.com/img/google-maps.png",


			],
			[
				"🏷️Local Keyword Targeting" => "Rank for city-specific or “near me” search terms to drive local traffic and qualified leads.",
				"img" => "https://www.quickdials.com/img/local-keyword.png",


			],
			[
				"📞 Call & Form Tracking" => "Monitor how many calls and form submissions come from local searches and specific landing pages.",
				"img" => "https://www.quickdials.com/img/call-form.png",


			],
			[
				"⭐ Review & Reputation Management" => "Encourage and manage customer reviews to boost credibility and local rankings.",
				"img" => "https://www.quickdials.com/img/review-putation.png",


			],
			[
				" 🛠️ Lead Capture Landing Pages" => "Create location-specific landing pages designed to convert local visitors into leads.",
				"img" => "https://www.quickdials.com/img/lead-capture.png",


			],
			[
				"🧭 Citation Building & Local Listings" => "Submit your business info to trusted local directories to improve consistency and authority.",
				"img" => "https://www.quickdials.com/img/citation-building.png",


			],



		];


		$data['grow_business'] = [
			
				"Grow business" => "Join thousands of businesses that trust Lead for their workforce management needs.",
				"Active Client" => $clients,
				"Employees Tracked" => $lead . " +",
				"Customer Satisfaction" => "100%",
				"Average Lead Increase" => "35%",
				"Business Kewyord" => $keyword,


			


		];
		$data['Quick Dials help'] = [
			"How Quick Dials help You to Grow your Business",
			"Quick Dials helps grow your business by boosting local visibility, generating quality leads, and connecting you with customers searching for your services."

		];

		$data['What is Quick Dials'] = [
			"Quick Dials is a comprehensive search platform designed for students, parents, and professionals seeking reliable information across India's diverse education landscape and industrial sectors. India offers a wide spectrum of opportunities, spanning education, manufacturing, services, and core industries.",
			"Education: From schools and coaching centers to higher education institutions.",
			"Manufacturing: Including automotive, pharmaceuticals, textiles, and chemicals.",
			"Service Industries: Such as IT, finance, tourism, and healthcare.",
			"Core Sectors: Covering India s Eight Core Industries — electricity, steel, refinery products, crude oil, coal, cement, natural gas, and fertilizers.",

		];


		$data['Benefits '] = [
			"Benefits you will get after associating with us:",
			"If the provided leads are out of your locality or category, we work on the same to replace it as soon as possible.",
			"We have the policy to refund on those leads that failed to commit.",
			"We provide end to end support to deliver the best needed.",
			"The information about leads will be distributed by SMS on your registered number and mailing on your registered email id..",

		];

		$data['Why choose Quick Dials'] = [
			"Why choose Quick Dials for growing your business?",
			"There are a few aspects that make us different from others and the aim directed towards helping the users and the client to get the best opportunity because:",
			"The work module is very different from others.",
			"We follow the conversion module.",
			"We provide dual manually verified leads to your business.",
			"These aspects make us different from others but there are few more things that make us unique and pops up the priority for you to choose us:",
			// "The leads are generated in both ways organic and inorganic",
			"We have a co-branding relationship with our own channel partners making us more capable and worthy to choose.",
			"The leads provided by us are all verified twice by our expert counselors, in order to provide you genuine candidates.",

		];

		$data['Contact Us :'] = [
			"Contact: +91-75-5943-5943, Email: info@quickdials.com, Website: www.quickdials.com.",
			"Other ways can be; by registering your business as a free listing, don’t worry, our marketing team is always happy to find you.",


		];



		$data['faq'] = [
			 
				'q1' => ' What is Quick Dials?',
				'a1' => 'Quick Dials is an extensive search engine for the students, parents, and Professionals, Quick Dials Only Deals In Education Sector and helps students to grab their right opportunity, and helps business owners to grow their business.',
		 
		 
				'q2' => 'Why choose Quick Dials for growing your business?',
				'a2' => 'Our Work Module Is Completely Different, we work on the Conversion module, and we Provide you  Dual Manually Verified Leads to your Business.',
		 
				'q3' => 'What Happen If my leads commitment did not get fulfilled?',
				'a3' => 'In case we are unable to fulfill our committed no of leads, we will refund your remaining amount.',
			 
				'q4' => 'What happens if I received a lead from out of my city/category?',
				'a4' => 'If you get a lead which is either out from your Locality or Category Then we will replace it as soon as possible.',
			 
				'q5' => 'How Diffrent your leads quality from others?',
				'a5' => 'our leads are dual manually verified by our expert counselors, so no need to worry about it.',
		 
				'q6' => 'How do you generate leads?',
				'a6' => 'we generate leads organically as well as inorganic leads, and we have Our own channels partners.',
			 
				'q7' => 'How will I get Leads?',
				'a7' => 'You will receive Leads on your Registered contact no through sms, and also on Your registered Email Id.',
			 
				'q7' => 'I Need More info?',
				'a7' => 'For More Info & any Queries, you can Contact Us on +91  75-5943-5943 or reach out to us via e-mail @ info@quickdials.com, or list your business as free listing, our marketing team Will Contact you Soon.',
			 
		];



		if ($data) {
			$data['status'] = true;

			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;

			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}


	/**
	 * @OA\Get(
	 *     path="/api/website/common-linked",
	 *     tags={"Website"},
	 *     summary="Website Common Linked",
	 *     description="Display data home page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function commonLinked(Request $request)
	{
		$url = config('app.url');
		$data['popularCategory'] = [
			[
				'url' => 'coaching-tuitions',
				'title' => 'Coaching & Tuitions',
				'type' => 'keyword',
			],
			[
				'url' => 'business-services',
				'title' => 'Business Services',
				'type' => 'keyword',
			],
			[
				'url' => 'home-construction',
				'title' => 'Home Construction & Renovation',
				'type' => 'keyword',

			],
			[
				'url' => 'personal-finance-services',
				'title' => 'Personal Finance Services',
				'type' => 'categories',

			],
			[
				'url' => 'tours-travel-services',
				'title' => 'Tours & Travels',
				'type' => 'categories',

			],
			[
				'url' => 'property-dealer',
				'title' => 'Property Dealer',
				'type' => 'categories',

			],
			[
				'url' => 'rentals',
				'title' => 'Rental Property',
				'type' => 'keyword',
			],
			[
				'url' => 'pg-hostels',
				'title' => 'PG & Hostel',
				'type' => 'keyword',
			],
			[
				'url' => 'computer-courses',
				'title' => 'Computer Courses & Training',
				'type' => 'categories',
			],
			[
				'url' => 'study-abroad',
				'title' => 'Study Abroad',
				'type' => 'keyword',
			],
			[
				'url' => 'home-services',
				'title' => 'Home Services',
				'type' => 'keyword',
			],

			[
				'url' => 'wedding-organizers',
				'title' => 'Parties, Special Occasions & Wedding',
				'type' => 'keyword',
			],
			[
				'url' => 'electric-services',
				'title' => 'Electric Services',
				'type' => 'child',
			],
			[
				'url' => 'entrance-exams-coaching',
				'title' => 'Government Exam',
				'type' => 'categories',
			],
			[
				'url' => 'web-designers',
				'title' => 'Web Designers',
				'type' => 'keyword',
			],
			[
				'url' => 'medical',
				'title' => 'Medical',
				'type' => 'keyword',
			],
			[
				'url' => 'carpenters',
				'title' => 'Carpenters',
				'type' => 'keyword',
			],
			[
				'url' => 'yoga-classes',
				'title' => 'Yoga',
				'type' => 'child',
			],
			[
				'url' => 'tax-consultants',
				'title' => 'CA & TAX Consultants',
				'type' => 'keyword',
			],
		];
		$data['businessService'] = [
			[
				'url' => 'patient-care-services',
				'title' => 'Patient Care Service',
				'type' => 'keyword',
			],
			[
				'url' => 'home-appliances-repair-services',
				'title' => 'Home Appliances Repair & Services',
				'type' => 'keyword',
			],
			[
				'url' => 'packers-and-movers',
				'title' => 'Packers and Movers',
				'type' => 'keyword',

			],
			[
				'url' => 'ac-repair-services',
				'title' => 'AC Services',
				'type' => 'keyword',

			],
			[
				'url' => 'cleaning-services',
				'title' => 'Cleaning Services',
				'type' => 'keyword',

			],
			[
				'url' => 'security-guards-services',
				'title' => 'Security Guards',
				'type' => 'keyword',

			],
			[
				'url' => 'architects',
				'title' => 'Architects',
				'type' => 'keyword',
			],
			[
				'url' => 'building-consultants-contractors',
				'title' => 'Builders & Contractors',
				'type' => 'keyword',
			],
			[
				'url' => 'interior-designers-decorators',
				'title' => 'Interior Designers & Decorators',
				'type' => 'keyword',
			],
			[
				'url' => 'housekeeping-services',
				'title' => 'Housekeeping Services',
				'type' => 'keyword',
			],
			[
				'url' => 'painting-contractors',
				'title' => 'Painting Contractors',
				'type' => 'keyword',
			],

			[
				'url' => 'modular-kitchen-dealers',
				'title' => 'Modular Kitchen Dealers',
				'type' => 'keyword',
			],
			[
				'url' => 'waterproofing-contractors',
				'title' => 'Waterproofing Contractors',
				'type' => 'keyword',
			]

		];
		$data['educationTraining'] = [
			[
				'url' => 'schools-colleges',
				'title' => 'Schools & Colleges',
				'type' => 'keyword',
			],
			[
				'url' => 'entrance-exams-coaching',
				'title' => 'Entrance Exam Coaching',
				'type' => 'categories',
			],
			[
				'url' => 'competitive-exam-coaching',
				'title' => 'Competitive Exam Coaching',
				'type' => 'keyword',

			],
			[
				'url' => 'distance-education',
				'title' => 'Distance Education',
				'type' => 'keyword',

			],
			[
				'url' => 'language-training',
				'title' => 'Language Training',
				'type' => 'keyword',

			],
			[
				'url' => 'overseas-education-consultants',
				'title' => 'Overseas Education',
				'type' => 'keyword',

			],
			[
				'url' => 'college-tuition',
				'title' => 'College & University Tuitions',
				'type' => 'keyword',
			],
			[
				'url' => 'bank-insurance-exam-coaching',
				'title' => 'Bank & Insurance Exam Coaching',
				'type' => 'keyword',
			],
			[
				'url' => 'placement-consultants',
				'title' => 'Placement Consultants',
				'type' => 'keyword',
			]
		];
		$data['personalService'] = [
			[
				'url' => 'loan',
				'title' => 'Loans',
				'type' => 'keyword',
			],
			[
				'url' => 'visa-consultants',
				'title' => 'Visa Consultants',
				'type' => 'keyword',
			],
			[
				'url' => 'beauty-parlour-services',
				'title' => 'Beauty Parlour Services',
				'type' => 'keyword',

			],
			[
				'url' => 'event-organisers',
				'title' => 'Event Organisers',
				'type' => 'keyword',

			],
			[
				'url' => 'catering-services',
				'title' => 'Catering Services',
				'type' => 'keyword',

			],
			[
				'url' => 'photographers-videographers',
				'title' => 'Photographers & Videographers',
				'type' => 'keyword',

			],
			[
				'url' => 'astrologers',
				'title' => 'Astrologers',
				'type' => 'keyword',
			],
			[
				'url' => 'vehicle-rental',
				'title' => 'Vehicle Rentals',
				'type' => 'keyword',
			],
			[
				'url' => 'massage-centres',
				'title' => 'Massage Centres',
				'type' => 'keyword',
			],
			[
				'url' => 'advocates-lawyers',
				'title' => 'Advocates & Lawyers',
				'type' => 'keyword',
			],
		];

		$cities = City::where('popular', '1')->get();
		$cityList = array();
		if ($cities) {
			foreach ($cities as $ckey => $cvalue) {

				$cityList[$ckey] = array(
					'url' => strtolower($cvalue->city),
					'city' => $cvalue->city,

				);

			}
		}
		$data['citiesofIndia'] = $cityList;

		if ($data) {
			$data['status'] = true;

			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;

			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}

	/**
	 * @OA\Get(
	 *     path="/api/website/business-services",
	 *     tags={"Website"},
	 *     summary="Website business services",
	 *     description="Display data home page",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function businessServices(Request $request)
	{
		$url = config('app.url');

		$data['businessServices'] = DB::table('parent_category')
			->join('child_category', 'child_category.parent_category_id', '=', 'parent_category.id')
			->select('parent_category.parent_category', 'child_category.child_category', 'child_category.child_slug', 'child_category.pc_icon')
			->get()

			->groupBy('parent_category') // Group by parent_category
			->map(function ($group) {
				return $group->map(function ($item) {
					 
					$image = "";
					$alt = "";
					if (!empty($item->pc_icon)) {

						$cicons = @unserialize($item->pc_icon);
						if ($cicons !== false && !empty($cicons['pc_icon']['src']) && !empty($cicons['pc_icon']['name'])) {
							$image = config('app.website') . $cicons['pc_icon']['src'];
							$alt = $item->child_category;
						}

					}
					return [
						'child_category' => $item->child_category,
						'child_slug' => $item->child_slug,
						'icon' => $image,
						'alt' => $alt,
					];
				})->toArray();
			})->toArray();



		if ($data) {
			$data['status'] = true;

			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;

			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'status' => true,
			'data' => $data,
		], 200);

	}



	/**
	 * @OA\Post(
	 *     path="/api/website/{client_id}/saveReview",
	 *     tags={"Website"},
	 *     summary="Submit a business review",
	 *     description="Allows a user to submit a review. One review per email per client per 30 days.",
	 *     
	 *
	 *     @OA\Parameter(
	 *         name="client_id",
	 *         in="path",
	 *         required=true,
	 *         @OA\Schema(type="integer", example=101)
	 *     ),
	 *
	 *     @OA\RequestBody(
	 *         required=true,
	 *         @OA\JsonContent(
	 *             required={
	 *                 "comment_author",
	 *                 "comment_author_phone",
	 *                 "comment_author_email",
	 *                 "comment_content",
	 *                 "s_rating"
	 *             },
	 *             @OA\Property(property="comment_author", type="string", example="Rahul Sharma"),
	 *             @OA\Property(property="comment_author_phone", type="string", example="9876543210"),
	 *             @OA\Property(property="comment_author_email", type="string", example="rahul@gmail.com"),
	 *             @OA\Property(property="comment_content", type="string", example="Very good service"),
	 *             @OA\Property(property="s_rating", type="integer", example=5, minimum=1, maximum=5)
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=200,
	 *         description="Review submitted successfully",
	 *         @OA\JsonContent(
	 *             @OA\Property(property="status", type="integer", example=1),
	 *             @OA\Property(property="message", type="string", example="Review successfully submitted.")
	 *         )
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=422,
	 *         description="Validation error"
	 *     ),
	 *
	 *     @OA\Response(
	 *         response=429,
	 *         description="Review limit exceeded"
	 *     )
	 * )
	 */

	public function store(Request $request, $client_id)
	{

		$request->validate([
			'comment_author' => 'required|string|regex:/^[A-Za-z ]+$/',
			'comment_author_phone' => 'required|digits:10',
			'comment_author_email' => 'required|email',
			'comment_content' => 'required|string',
			's_rating' => 'required|integer|min:1|max:5',
		]);

		// Check last review date (30 days rule)
		$lastReviewDate = DB::table('comments')
			->where('comment_author_email', $request->comment_author_email)
			->where('comment_client_ID', $client_id)
			->max(DB::raw('DATE(created_at)'));

		if ($lastReviewDate && now()->diffInDays($lastReviewDate) <= 30) {
			return response()->json([
				'status' => false,
			 
				'message' => 'Thanks for your feedback! You are already submitted a review for this business'
			], 429);
		}

		// 💾 Save review
		Comment::create([
			'comment_client_ID' => $client_id,
			'comment_author' => $request->comment_author,
			'comment_author_phone' => $request->comment_author_phone,
			'comment_author_email' => $request->comment_author_email,
			'comment_content' => $request->comment_content,
			'rating' => $request->s_rating,
			'admin_id' => '0',
			'OTP' => '0',
			'comment_author_IP' => $request->ip(),
		]);

		return response()->json([
			'status' => true,
			
			'message' => 'Review successfully submitted.'
		], 200);
	}
	
	
	
	
	
	
	 public function generate($client, string $workingHoursHtml = '', ?string $categorySlug = null): array
    {
        $business = trim($client->business_name ?? 'this business');
        $area     = trim($client->area ?? '');
        $city     = trim($client->city ?? '');
        $location = trim("{$area}, {$city}", ', ');

        $slug = strtolower($categorySlug ??  $client->category_service ?? '');
 
        $template = self::detectTemplate($slug);
 
        return self::{$template}($business, $area, $city, $location, $workingHoursHtml);
    }

    /**
     * Map a service slug → template method name.
     */
    private static function detectTemplate($slug)
    {
      $map = getOverViewBusiness($slug);
 
        foreach ($map as $needle => $template) {
            if (str_contains($slug, $needle)) {
                return $template;
            }
        }
        return 'generic';
    }



	/**
	 * @OA\Get(
	 *     path="/api/website/get-business-list",
	 *     tags={"Website"},
	 *     summary="Website business list",
	 *     description="Display data business list",
	 *     @OA\Response(
	 *         response=200,
	 *         description="Search results retrieved successfully",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=true),
	 *             @OA\Property(property="message", type="string", example="Data retrieved successfully"),
	 *             @OA\Property(
	 *                 property="data",
	 *                 type="array",
	 *                 @OA\Items(type="object")
	 *             )
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=404,
	 *         description="No results found",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="success", type="boolean", example=false),
	 *             @OA\Property(property="message", type="string", example="No records found.")
	 *         )
	 *     ),
	 *     @OA\Response(
	 *         response=401,
	 *         description="Unauthorized",
	 *         @OA\JsonContent(
	 *             type="object",
	 *             @OA\Property(property="message", type="string", example="Unauthenticated.")
	 *         )
	 *     )
	 * )
	 */
	public function getBusinessList(Request $request)
	{
		$url = config('app.url');
		$clientsAgents = DB::table('clients')
        ->leftJoin(DB::raw('(
            SELECT SUM(rating) AS rating,
                   AVG(rating) AS avg_rating,
                   comment_client_ID,
                   COUNT(comment_ID) AS comment_count
            FROM comments
            GROUP BY comment_client_ID
        ) c'), 'c.comment_client_ID', '=', 'clients.id')
        ->select(
            'clients.*',
            'clients.id as client_id',
            'clients.client_type',
            'c.rating',
            'c.avg_rating',
            'c.comment_count'
        )
        // ✅ Filter: client must have at least 1 keyword
        ->whereExists(function ($q) {
            $q->select(DB::raw(1))
              ->from('assigned_kwds')
              ->whereColumn('assigned_kwds.client_id', 'clients.id');
        })
        // ✅ Filter: client must have at least 1 zone
        ->whereExists(function ($q) {
            $q->select(DB::raw(1))
              ->from('assigned_zones')
              ->whereColumn('assigned_zones.client_id', 'clients.id');
        })
        ->where('clients.active_status', '1')
        ->whereNotNull('clients.logo')
        ->whereNotNull('clients.pictures')
        ->orderByRaw("
            CASE clients.client_type
                WHEN 'platinum' THEN 1
                WHEN 'diamond'  THEN 2
                WHEN 'gold'     THEN 3
                WHEN 'silver'   THEN 4
                ELSE 5
            END
        ")
        ->limit(20)
        ->get();
 

		$data['agents'] = $clientsAgents->map(function ($client) {

			$logoImage = config('app.website') . 'client/images/default_pp_small.png';
			$altLogo = "Business Logo";
			if (!empty($client->logo)) {
				$cicons = unserialize($client->logo);
				if (!empty($cicons)) {
					$logoImage = config('app.website') . $cicons['large']['src'];
					$altLogo = $cicons['large']['name'];
				}
			}


			$galleryArray = array();
			if (!empty($client->pictures)) {
				$galleryList = unserialize($client->pictures);
				if (!empty($galleryList)) {
					foreach ($galleryList as $key => $value) {

						$galleryArray[$key] = array(
							'galley' => $value

						);

					}
				}
			}
			$certified_img = config('app.website') . 'img/q_verified.gif';
			$trusted_img = config('app.website') . 'img/q_trust.gif';
			$gst_img = config('app.website') . 'img/q_gst.gif';
			$avgRating = "0";
			if ($client->rating) {
				$avgRating = ($client->rating / (5 * $client->comment_count)) * 5;
				$avgRating = number_format($avgRating, 1, '.', '');
			}


			$assignedKeywords = DB::table('assigned_kwds')
				->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
				->where('assigned_kwds.client_id', $client->client_id)
				->orderBy('keyword', 'asc')
				->distinct()
				->limit(10)
				->pluck('keyword.keyword', 'keyword.slug')
				->toArray();

			$assignedCategory = DB::table('assigned_kwds')
				->join('child_category', 'assigned_kwds.child_cat_id', '=', 'child_category.id')
				->where('assigned_kwds.client_id', $client->client_id)
				->orderBy('child_category', 'asc')
				->distinct()
				->limit(10)
				->pluck('child_category.child_category', 'child_category.child_slug')
				->toArray();
				
				
					$workingHoursHtml = '10AM to 7PM';
					$categorySlug = $client->category_service;

					$template = $this->generate(
						$client,
						$workingHoursHtml,
						$categorySlug
					);
				 
			return [
				'business_id' => $client->client_id,
				'business_name' => $client->business_name,
				'business_slug' => $client->business_slug,
				'logo' => $logoImage ?? '',
				'altLogo' => $altLogo ?? '',
				'gallery' => $galleryArray ?? '',
				'certifications' => $client->certifications,
				'sirName' => $client->sirName,
				'first_name' => $client->first_name,
				'middle_name' => $client->middle_name,
				'last_name' => $client->last_name,
				'certified_status' => $client->certified_status,
				'trusted_status' => $client->trusted_status,
				'gst_status' => $client->gst_status,
				'certified_img' => $certified_img,
				'trusted_img' => $trusted_img,
				'gst_img' => $gst_img,
				'website' => $client->website,
				'city' => $client->city,
				'state' => $client->state,
				'area' => $client->area,
				'zone' => $client->zone,
				'gst_no' => $client->gst_no,
				'dpiit_no' => $client->dpiit_no,
				'pan_no' => $client->pan_no,
				'cin_no' => $client->cin_no,
				'iso_no' => $client->iso_no,
				'msme_no' => $client->msme_no,
				'coi_no' => $client->coi_no,
			 
				'verified' => $client->verified,
				'trending' => $client->trending,
				'topSearch' => $client->topSearch,
				'featured' => $client->featured,
				'description' => $client->description,
				'mapUrl' => "https://maps.google.com/?q=" . generate_slug($client->address),
				'openUntil' => $client->openUntil,
				'address' => $client->address,
				'pincode' => $client->pincode,
				'country' => $client->country,
				'year_of_estb' => $client->year_of_estb,
				'landmark' => $client->landmark,
				'rating' => $client->rating,
				'avgRating' => $avgRating,
				'call' => "917559435943",
				'whatsapp' => "917559435943",
				'comment_count' => $client->comment_count,
				'tags' => $assignedKeywords,
				'category' => $assignedCategory ?? null,
				'overviewBusiness' => $template ?? null,

			];
		});

		return response()->json([
			'success' => true,
			'status' => true,
			 
			'data' => $data,
		], 200);
	}



	
 
 
    private static function training($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a leading training institute in {$city}, offering a wide range of professional courses and skill-development programs designed for today's competitive job market. From technical certifications to soft-skill workshops, the institute helps students, working professionals, and career changers build job-ready expertise.{$hours}Flexible batch timings — weekday, weekend, and fast-track — make it easy to learn while managing work or studies. The experienced faculty at {$business} provides hands-on training, real-world projects, doubt-clearing sessions, and dedicated placement support to ensure measurable career growth.",

            "Whether you want to upskill in IT, finance, management, digital marketing, or any vocational discipline, {$business} in {$location} has the right program for you. With a comprehensive course catalog, expert mentors, modern infrastructure, and a strong placement record, {$business} stands as a trusted destination for skill development in {$city}. Enrol today and take the next step in your professional journey."
        ];
    }

    // ── 2. AC REPAIR ──
    private static function acRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a trusted AC repair and service provider in {$city}, delivering reliable solutions for all major brands including Voltas, LG, Samsung, Daikin, Hitachi, Carrier, Blue Star, Whirlpool, Panasonic, and more. The team handles split AC, window AC, and central AC servicing, gas refilling, installation, uninstallation, deep cleaning, PCB repair, compressor replacement, and annual maintenance contracts (AMC).{$hours}With certified technicians, genuine spare parts, and same-day doorstep service in {$area}, {$business} ensures your AC runs efficiently and consumes less power.",

            "Whether you need an emergency AC repair, a one-time service, or a yearly AMC, {$business} in {$location} offers transparent pricing, no hidden charges, and a service warranty on every job. Customers across {$city} trust {$business} for professional handling, quick response times, and long-lasting repairs. Book a technician today and enjoy cool, hassle-free comfort all summer long."
        ];
    }

    // ── 3. REFRIGERATOR REPAIR ──
    private static function fridgeRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a reliable refrigerator repair service in {$city}, fixing all types of fridges — single door, double door, side-by-side, French door, mini fridge, and deep freezers — across leading brands like LG, Samsung, Whirlpool, Godrej, Haier, Bosch, Voltas Beko, and Panasonic. The skilled technicians at {$business} diagnose and resolve issues such as cooling problems, water leakage, ice build-up, compressor failure, gas charging, thermostat issues, and noisy operation.{$hours}",

            "From routine servicing to complex repairs, {$business} in {$location} provides doorstep service across {$area} with quick response, genuine spare parts, and an honest pricing structure. Customers in {$city} prefer {$business} for prompt service, skilled handling, and lasting results. Schedule a fridge repair today and avoid food spoilage with an expert touch."
        ];
    }

   

    // ── 6. WATER PURIFIER REPAIR ──
    private static function waterPurifierRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a trusted water purifier repair and service provider in {$city}, covering RO, UV, UF, and gravity-based water purifiers from brands like Kent, Aquaguard, Pureit, Livpure, Eureka Forbes, Blue Star, AO Smith, and Havells. Services include filter replacement, candle change, membrane replacement, motor repair, TDS adjustment, leakage fixing, and annual maintenance contracts (AMC).{$hours}",

            "With certified technicians and genuine spare parts, {$business} in {$location} ensures clean, safe drinking water for every home in {$area}. Quick service response, transparent pricing, and post-service warranty make {$business} the preferred choice for water purifier care across {$city}. Book your service today for healthier hydration."
        ];
    }

    // ── 7. LAPTOP REPAIR ──
    private static function laptopRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} delivers professional laptop repair service in {$city} for all brands — Dell, HP, Lenovo, Acer, Asus, Apple MacBook, Microsoft Surface, MSI, and more. Common services include screen replacement, keyboard repair, battery replacement, motherboard fault, hinge repair, virus removal, OS installation (Windows, macOS, Linux), SSD upgrade, RAM upgrade, and data recovery.{$hours}",

            "Whether your laptop won't power on, runs slow, or has hardware damage, {$business} in {$location} offers honest diagnostics, genuine parts, and quick turnaround. With doorstep service across {$area} and reliable warranty, {$business} is the go-to laptop repair expert in {$city}. Book a free diagnosis today."
        ];
    }

    // ── 8. COMPUTER REPAIR ──
    private static function computerRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} provides comprehensive computer and desktop repair services in {$city}, handling hardware faults, OS installation, virus and malware removal, network and Wi-Fi setup, data recovery, hardware upgrades (RAM, SSD, GPU), printer setup, and home/office IT support.{$hours}From PC slowdowns to total system failures, the technicians at {$business} resolve issues quickly with reliable solutions.",

            "Trusted by homes, offices, and small businesses across {$area}, {$business} in {$location} offers AMC plans, on-site service, and remote support across {$city}. With transparent rates and skilled engineers, {$business} keeps your computer running smoothly so you can focus on what matters."
        ];
    }

    // ── 9. CAR REPAIR ──
    private static function carRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a trusted car repair and servicing centre in {$city}, offering periodic maintenance, engine repair, AC service, brake and clutch repair, denting and painting, battery replacement, tyre alignment and balancing, oil change, and complete bodywork. The garage serves all car brands — Maruti Suzuki, Hyundai, Tata, Mahindra, Honda, Toyota, Kia, Renault, Skoda, Volkswagen, Ford, and luxury cars.{$hours}",

            "With skilled mechanics, advanced diagnostic tools, and genuine spare parts, {$business} in {$location} delivers professional car care at honest prices. Customers across {$area} trust {$business} for transparent estimates, on-time delivery, and post-service warranty. Visit today for a free vehicle health check-up in {$city}."
        ];
    }

    // ── 10. BIKE REPAIR ──
    private static function bikeRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a reliable bike and two-wheeler repair shop in {$city}, offering periodic servicing, engine work, tyre change, chain repair, brake adjustment, battery replacement, electrical repairs, denting, painting, and accessory fitting. The team services all brands — Hero, Bajaj, TVS, Honda, Yamaha, Suzuki, Royal Enfield, KTM, and electric bikes.{$hours}",

            "Riders across {$area} trust {$business} in {$location} for skilled mechanics, genuine parts, and quick turnaround. With transparent service charges and pickup-drop options in {$city}, {$business} keeps your bike road-ready, fuel-efficient, and safe. Book your bike service today."
        ];
    }

    // ── 11. BANQUET HALL ──
    private static function banquetHall($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a premium banquet hall in {$city}, ideal for weddings, receptions, sangeet, engagement ceremonies, birthday parties, corporate events, and social gatherings. The venue offers spacious seating, air-conditioned halls, modern lighting, sound systems, ample parking, valet service, dedicated bridal rooms, and in-house catering with multi-cuisine options.{$hours}",

            "From small intimate functions to grand celebrations, {$business} in {$location} provides flexible packages, customized décor, and professional event support to make every occasion memorable. With elegant interiors, attentive staff, and a prime location in {$area}, {$business} is among the most preferred banquet halls in {$city}. Book your date today."
        ];
    }

    

    // ── 14. WEDDING PHOTOGRAPHY ──
    private static function weddingPhotography($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a leading wedding photography and videography service in {$city}, capturing every emotion of your big day with cinematic style. Specializations include candid photography, traditional photography, pre-wedding shoots, post-wedding shoots, drone videography, cinematic wedding films, photo albums, same-day edits, and live event coverage.{$hours}",

            "Working with the latest camera equipment, drones, gimbals, and editing software, the creative team at {$business} in {$location} crafts stunning visual stories that you will treasure forever. Couples across {$area} and {$city} choose {$business} for its artistic eye, professional handling, and timely delivery. Book your wedding photographer today and preserve memories that last a lifetime."
        ];
    }

    // ── 15. WEDDING DECORATION / FLOWER DECORATION ──
    private static function weddingDecoration($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a creative wedding and event decoration specialist in {$city}, offering stunning stage décor, mandap decoration, entrance gates, fresh flower decoration, themed décor, fairy light setups, balloon decoration, and customized backdrops for weddings, engagements, receptions, birthdays, and corporate events.{$hours}",

            "From traditional Indian themes to modern minimalist setups, {$business} in {$location} blends fresh flowers, fabric drapes, premium lighting, and on-trend styling to transform any venue into a dream space. Trusted by families across {$area} and {$city}, {$business} delivers turnkey decoration with creative concepts and on-time setup. Book a free consultation today."
        ];
    }

    // ── 16. VARMALA / JAIMALA ──
    private static function varmala($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} provides spectacular varmala and jaimala entry services in {$city} — from elegant manual entries to grand mechanical varmala setups, hydraulic platforms, revolving stages, rotating jaimala thaalis, dry ice effects, LED-lit garlands, and themed bride-groom entries. Every setup is custom-designed to match your wedding theme and venue.{$hours}",

            "Make your varmala ceremony the highlight of your wedding with {$business} in {$location}. Trusted by couples across {$area} and {$city}, the experienced team handles design, installation, special effects, and safety with precision. Add cinematic magic to your jaimala moment — enquire today for available designs and packages."
        ];
    }

    // ── 17. WEDDING CHOREOGRAPHER ──
    private static function weddingChoreographer($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers professional wedding choreography services in {$city} for sangeet, mehndi, haldi, reception, and engagement functions. The choreographers specialize in couple dance, family group dance, Bollywood choreography, classical, hip-hop, lyrical, and themed performances — designed to match every skill level from beginners to dance enthusiasts.{$hours}",

            "With customized song selection, easy-to-learn routines, costume guidance, and stage presentation tips, {$business} in {$location} makes every performance unforgettable. Couples and families across {$area} and {$city} trust {$business} for energetic, joyful, and rehearsal-friendly choreography. Book your sessions today and shine on the dance floor."
        ];
    }

    // ── 18. WEDDING ORGANISER / PLANNER ──
    private static function weddingOrganiser($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a complete wedding planning and event management company in {$city}, offering end-to-end services — venue booking, decoration, catering, photography, hospitality, transport, guest management, choreography, baraat services, return gifts, invitations, and destination wedding coordination.{$hours}",

            "From intimate weddings to grand celebrations, {$business} in {$location} delivers stress-free planning, on-budget execution, and creative direction tailored to every culture and tradition. Families across {$area} and {$city} count on {$business} to bring their dream wedding to life. Schedule a planning consultation today and let the experts handle the rest."
        ];
    }

    // ── 19. ASTROLOGER ──
    private static function astrologer($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a renowned astrology and Vedic consultation service in {$city}, offering marriage matching (kundli milan), guna milan, birth chart analysis, horoscope reading, gemstone recommendations, vastu consultancy, palmistry, numerology, muhurat selection, and remedies for life challenges.{$hours}",

            "With years of experience in Vedic astrology, {$business} in {$location} provides accurate, ethical, and confidential guidance to clients across {$area} and {$city}. From wedding date selection to compatibility checks, get trusted insights that bring clarity and peace of mind. Book a session today — in-person or online consultations available."
        ];
    }

    // ── 20. GHODA BAGGI ──
    private static function ghodaBaggi($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers premium ghoda baggi (horse carriage) and decorated horse services in {$city} for weddings, baraats, processions, ring ceremonies, and special events. Choose from royal Rajasthani-style baggis, vintage carriages, decorated horses with traditional ornaments, and themed setups complete with sherwani-clad coachmen.{$hours}",

            "Make your baraat unforgettable with the regal arrival of a beautifully adorned ghoda baggi from {$business} in {$location}. Trusted by families across {$area} and {$city}, the team ensures well-groomed horses, on-time service, and safety throughout the procession. Book your wedding baggi today."
        ];
    }

    

    // ── 22. COLD FIRE / FOG EFFECT ──
    private static function coldFire($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} specializes in cold fire (cold pyro), fog effects, dry ice setups, LED confetti blasters, sparkular machines, smoke jets, and special-effect lighting for weddings, varmala entries, sangeet events, corporate functions, and stage shows in {$city}.{$hours}",

            "Create breathtaking entry moments with the safe, smoke-free, indoor-friendly effects from {$business} in {$location}. Trusted by event planners across {$area} and {$city}, every setup is operated by trained technicians ensuring safety, timing, and visual brilliance. Book cold fire and fog effects today for an unforgettable celebration."
        ];
    }

     

    // ── 25. GENERIC FALLBACK (any uncategorized service) ──
    private static function generic($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a trusted service provider in {$city}, known for quality, reliability, and customer satisfaction. With experienced professionals, modern tools, and a strong commitment to service excellence, {$business} caters to a wide range of customer needs across {$area} and {$city}.{$hours}",

            "From first contact to job completion, {$business} in {$location} ensures transparent pricing, on-time service, and quality outcomes that customers in {$city} can count on. Whether for one-time service or ongoing requirements, {$business} stands as a reliable choice. Get in touch today to learn more or schedule a visit."
        ];
    }


 // ── 1. DOCTORS ──
    private static function doctors($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a trusted clinic in {$city} offering expert consultation by experienced doctors across general medicine, paediatrics, gynaecology, orthopaedics, ENT, cardiology, dermatology, diabetes care, and chronic disease management. Patients receive accurate diagnosis, prescribed treatment plans, lab test referrals, second opinions, and structured follow-up care all under one roof.{$hours}",

            "Trusted by families across {$area} and {$city}, {$business} in {$location} is known for qualified MBBS and MD specialists, hygienic facilities, minimal waiting time, transparent consultation fees, and complete medical guidance for every age group. Book your appointment today for reliable, patient-first medical care from doctors near you."
        ];
    }

    // ── 2. HOSPITAL ──
    private static function hospital($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a leading multi-speciality hospital in {$city} offering 24×7 emergency care, ICU, surgery, maternity services, diagnostics, pathology lab, pharmacy, and inpatient facilities. With specialists in cardiology, neurology, orthopaedics, paediatrics, oncology, and general surgery, the hospital is equipped to handle both routine treatments and critical care.{$hours}",

            "Patients across {$area} and {$city} choose {$business} in {$location} for its experienced doctors, modern equipment, cashless insurance support, ambulance services, and dedicated nursing staff. Whether you need a planned procedure, emergency admission, or specialist consultation, {$business} ensures safe, affordable, and high-quality healthcare for every patient."
        ];
    }

    // ── 3. DENTIST ──
    private static function dentist($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a leading dental clinic in {$city} offering complete oral care including teeth cleaning, scaling, root canal treatment, dental implants, braces, aligners, cosmetic dentistry, kids dentistry, wisdom tooth removal, and full-mouth rehabilitation. Modern sterilisation protocols, digital X-rays, and painless procedures make every visit comfortable.{$hours}",

            "Patients across {$area} and {$city} trust {$business} in {$location} for honest treatment plans, transparent pricing, EMI options, and skilled BDS and MDS dentists. Book your dental check-up today and get expert care for healthier teeth and a brighter smile."
        ];
    }

    // ── 4. MEDICAL SHOP ──
    private static function medicalShop($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a trusted medical store in {$city} offering a wide range of prescription medicines, OTC drugs, generic alternatives, surgical items, ayurvedic products, baby care, and personal hygiene essentials. All medicines are sourced directly from authorised distributors and stored under proper temperature conditions for full efficacy.{$hours}",

            "Customers across {$area} and {$city} rely on {$business} in {$location} for genuine medicines, fast home delivery, competitive prices, monthly discounts on chronic medication, and friendly pharmacist support. Visit or call today to refill prescriptions or order essential health products."
        ];
    }

    // ── 5. MEDICAL EQUIPMENT ──
    private static function medicalEquipment($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} supplies a complete range of medical equipment in {$city} including hospital beds, wheelchairs, oxygen concentrators, BiPAP and CPAP machines, nebulisers, BP monitors, glucometers, walking aids, and surgical instruments. Equipment is available for both sale and rent with installation and after-sales support.{$hours}",

            "Hospitals, clinics, and home users across {$area} and {$city} trust {$business} in {$location} for branded products, warranty coverage, doorstep delivery, and on-site service. Contact today for personalised quotes on home care or hospital-grade medical equipment."
        ];
    }

    // ── 6. PATIENT CARE ──
    private static function patientCare($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers professional patient care services in {$city} including trained attendants, nurses, post-surgery care, elderly care, bedridden patient support, physiotherapy assistance, and 24-hour home nursing. Caregivers are background-verified and trained in hygiene, medication schedules, mobility support, and emergency response.{$hours}",

            "Families across {$area} and {$city} rely on {$business} in {$location} for compassionate, reliable, and affordable home care that respects patient dignity. Book a caregiver today for short-term recovery support or long-term elderly care at home."
        ];
    }

    // ── 7. YOGA ──
    private static function yoga($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a popular yoga studio in {$city} offering classes in Hatha yoga, Ashtanga, Vinyasa flow, Power yoga, Pranayama, meditation, prenatal yoga, and therapeutic yoga for back pain, stress, diabetes, and weight loss. Sessions are led by certified yoga teachers in small batches for personalised attention.{$hours}",

            "Students across {$area} and {$city} choose {$business} in {$location} for its peaceful environment, flexible morning and evening batches, online classes, and structured progress tracking. Start your yoga journey today and experience better flexibility, strength, focus, and overall well-being."
        ];
    }

    // ── 8. GYM ──
    private static function gym($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a fully equipped gym in {$city} offering strength training, cardio, CrossFit, Zumba, HIIT, personal training, weight loss programs, muscle gain plans, and sports-specific conditioning. The facility features modern equipment, certified trainers, diet consultation, and clean changing rooms with showers.{$hours}",

            "Fitness enthusiasts across {$area} and {$city} pick {$business} in {$location} for affordable membership plans, flexible timings, group classes, and a motivating community. Sign up today for a free trial and start your transformation with expert coaching and a results-driven workout plan."
        ];
    }

    // ── 9. HEALTH WELLNESS (catch-all) ──
    private static function healthWellness($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a trusted health and wellness centre in {$city} offering therapies, fitness coaching, weight management, holistic healing, ayurveda, naturopathy, physiotherapy, and lifestyle counselling. Services are designed to improve physical fitness, mental well-being, and long-term health.{$hours}",

            "Clients across {$area} and {$city} visit {$business} in {$location} for expert practitioners, personalised wellness plans, clean facilities, and visible results. Book your first wellness consultation today and start a healthier, balanced lifestyle."
        ];
    }

    // ── 10. SPA ──
    private static function spa($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a premium spa in {$city} offering Swedish massage, deep tissue therapy, Thai massage, Balinese massage, aromatherapy, foot reflexology, body scrubs, couple spa packages, and detox treatments. The relaxing ambience, trained therapists, and hygienic linens deliver a complete rejuvenation experience.{$hours}",

            "Guests across {$area} and {$city} prefer {$business} in {$location} for genuine therapies, transparent pricing, no upselling, and exclusive monthly memberships. Book a spa session today and unwind from stress, body pain, and a busy lifestyle."
        ];
    }

    // ── 11. BEAUTY PARLOUR ──
    private static function beautyParlour($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a well-known beauty parlour in {$city} offering facials, threading, waxing, hair spa, hair colour, keratin treatment, manicure, pedicure, bridal makeup, party makeup, and skin clean-ups. Premium products from L'Oréal, MAC, O3+, and Lotus Herbals are used for safe, long-lasting results.{$hours}",

            "Women across {$area} and {$city} trust {$business} in {$location} for skilled beauticians, hygienic equipment, on-time service, and budget-friendly combo packages. Walk in today or book your appointment for a complete beauty makeover."
        ];
    }

    // ── 12. SALON ──
    private static function salon($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a top unisex salon in {$city} offering haircut, hair styling, beard grooming, hair colour, hair smoothening, head massage, facials, clean-ups, and pre-wedding grooming packages. The salon uses branded products and follows strict hygiene with sanitised tools and single-use disposables.{$hours}",

            "Clients across {$area} and {$city} prefer {$business} in {$location} for stylish cuts, trending hair colours, friendly staff, and value-for-money pricing. Book your slot online or walk in to experience a fresh, modern grooming session."
        ];
    }

     
    // ── 13. AC SERVICE ──
    private static function acService($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers professional AC service in {$city} including AC installation, gas refilling, deep cleaning, repair, uninstallation, PCB and compressor replacement, and annual maintenance contracts (AMC) for split, window, cassette, and central AC units across all brands like LG, Samsung, Daikin, Voltas, Hitachi, and Blue Star.{$hours}",

            "Customers across {$area} and {$city} trust {$business} in {$location} for trained technicians, original spare parts, transparent pricing, same-day service, and post-service warranty. Book your AC service today for efficient cooling, lower power bills, and longer machine life."
        ];
    }

    // ── 14. CAR SERVICE ──
    private static function carService($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a full-service car workshop in {$city} offering general service, oil and filter changes, brake repair, AC servicing, battery replacement, denting and painting, wheel alignment, balancing, suspension work, and computerised diagnostics for hatchbacks, sedans, SUVs, and luxury cars.{$hours}",

            "Car owners across {$area} and {$city} rely on {$business} in {$location} for genuine OEM spares, certified mechanics, pickup and drop service, transparent estimates, and post-service warranty. Book your car service today and keep your vehicle running smoothly and safely."
        ];
    }

    // ── 15. ELECTRIC CAR SERVICE ──
    private static function electricCarService($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} specialises in electric car service in {$city} covering battery diagnostics, BMS (Battery Management System) inspection, motor and controller repair, charging port issues, software updates, brake servicing, suspension checks, and full periodic maintenance for Tata, MG, Mahindra, Hyundai, BYD, and Tesla EVs.{$hours}",

            "EV owners across {$area} and {$city} trust {$business} in {$location} for trained EV technicians, certified diagnostic tools, original parts, doorstep pickup, and transparent service estimates. Book your electric car service today and ensure peak range, battery health, and long-term performance."
        ];
    }

    // ── 16. ELECTRIC BIKE SERVICE ──
    private static function electricBikeService($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers expert electric bike service in {$city} including battery testing, motor repair, controller replacement, charger diagnostics, brake service, tyre replacement, and full periodic maintenance for Ola, Ather, TVS iQube, Bajaj Chetak, Hero Electric, and other top EV scooter brands.{$hours}",

            "EV scooter owners across {$area} and {$city} choose {$business} in {$location} for certified technicians, genuine spares, doorstep service options, and transparent service costs. Schedule your electric bike service today for smooth rides, better range, and longer battery life."
        ];
    }

    // ── 17. CAR TOWING ──
    private static function carTowing($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} provides reliable 24×7 car towing services in {$city} including flatbed towing, accident vehicle recovery, breakdown assistance, jump start, on-spot tyre change, fuel delivery, and inter-city towing for cars, SUVs, vintage cars, and luxury vehicles.{$hours}",

            "Motorists across {$area} and {$city} rely on {$business} in {$location} for fast response times, insured towing, trained crew, and damage-free transport. Save the number — call {$business} the moment you face a breakdown or accident."
        ];
    }

    // ── 18. LAPTOP REPAIR ──
      

    // ── 20. MOBILE REPAIR ──
    private static function mobileRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a leading mobile phone repair centre in {$city} offering display replacement, battery change, charging port repair, water damage recovery, motherboard repair, software flashing, and unlocking services for iPhone, Samsung, OnePlus, Vivo, Oppo, Realme, Xiaomi, and Nothing phones.{$hours}",

            "Mobile users across {$area} and {$city} prefer {$business} in {$location} for original spare parts, ISO-grade tools, transparent quotes, same-day service, and up to 6-month repair warranty. Bring your phone in today and walk out with a fully functional device."
        ];
    }

    // ── 21. REFRIGERATOR REPAIR ──
    private static function refrigeratorRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} provides expert refrigerator repair in {$city} for single-door, double-door, side-by-side, and triple-door fridges from LG, Samsung, Whirlpool, Godrej, Haier, Bosch, and Panasonic. Services include gas refilling, compressor replacement, thermostat issues, water leakage, ice formation, and PCB repair.{$hours}",

            "Households across {$area} and {$city} call {$business} in {$location} for trained technicians, doorstep service, transparent rates, and 90-day service warranty. Book a refrigerator repair today and stop food spoilage with same-day fixes."
        ];
    }

    // ── 22. TV REPAIR ──
    private static function tvRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} specialises in TV repair in {$city} for LED, LCD, OLED, QLED, plasma, and smart TVs across Sony, Samsung, LG, MI, OnePlus, TCL, and Panasonic. Common services include display replacement, backlight repair, power supply issues, motherboard repair, smart features setup, and HDMI port fixes.{$hours}",

            "TV owners across {$area} and {$city} rely on {$business} in {$location} for free diagnosis, fast doorstep service, original spare parts, and clear service quotes. Call today and get your TV back to perfect picture and sound quality."
        ];
    }

    // ── 23. WASHING MACHINE REPAIR ──
    private static function washingMachineRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers reliable washing machine repair services in {$city} for fully automatic, semi-automatic, top-load, and front-load machines from IFB, LG, Samsung, Whirlpool, Bosch, Godrej, and Haier. Services cover drum noise, water leakage, drainage issues, PCB faults, motor repair, and door lock problems.{$hours}",

            "Households across {$area} and {$city} choose {$business} in {$location} for skilled technicians, original spares, transparent pricing, and 90-day repair warranty. Book your washing machine repair today and avoid laundry pile-ups."
        ];
    }

    // ── 24. SOFA REPAIR ──
    private static function sofaRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} provides complete sofa repair and refurbishment services in {$city} including foam replacement, cushion repair, frame fixing, recliner mechanism repair, leather restoration, fabric change, and full sofa re-upholstery in trending fabrics and premium leatherette.{$hours}",

            "Customers across {$area} and {$city} trust {$business} in {$location} for skilled craftsmen, doorstep estimation, branded fabrics, and on-time delivery. Bring your old sofa back to life — book a free home inspection today and choose from hundreds of design options."
        ];
    }

    // ── 25. COOLER REPAIR ──
    private static function coolerRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers fast air cooler repair services in {$city} including motor replacement, pump repair, water leakage fixing, fan blade replacement, cooling pad change, and full servicing for desert, tower, personal, and industrial coolers across Symphony, Bajaj, Crompton, Kenstar, Usha, and Voltas.{$hours}",

            "Households and shopkeepers across {$area} and {$city} rely on {$business} in {$location} for same-day doorstep service, original spares, transparent pricing, and post-service warranty. Book a cooler repair today and stay cool through the summer."
        ];
    }

    // ── 26. WATER GEYSER REPAIR ──
    private static function waterGyserRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} provides expert water geyser repair services in {$city} covering instant geysers, storage geysers, gas geysers, and solar water heaters from Bajaj, Havells, AO Smith, Racold, Crompton, and Venus. Services include heating element replacement, thermostat repair, leakage fixing, and full descaling.{$hours}",

            "Homeowners across {$area} and {$city} rely on {$business} in {$location} for trained technicians, genuine parts, fast same-day service, and transparent service costs. Book your geyser repair today and enjoy uninterrupted hot water at home."
        ];
    }

    // ── 27. KITCHEN CHIMNEY REPAIR ──
    private static function kitchenChimneyRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers professional kitchen chimney repair and cleaning services in {$city} including filter replacement, deep degreasing, motor repair, switch and LED replacement, and complete servicing for auto-clean, baffle, and cassette filter chimneys from Faber, Elica, Glen, Hindware, Kaff, and Sunflame.{$hours}",

            "Homemakers across {$area} and {$city} prefer {$business} in {$location} for trained chimney technicians, eco-friendly cleaning chemicals, doorstep service, and transparent pricing. Book a chimney service today and restore strong suction and a clean, smoke-free kitchen."
        ];
    }

    // ── 28. GAS STOVE REPAIR ──
    private static function gasStoveRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} provides quick and safe gas stove repair services in {$city} including burner cleaning, knob replacement, auto-ignition repair, gas leakage check, regulator issues, hose replacement, and complete servicing for 2-burner, 3-burner, 4-burner, glass top, and built-in hobs.{$hours}",

            "Households across {$area} and {$city} trust {$business} in {$location} for trained technicians, original spares, fast doorstep service, and full safety inspection at every visit. Book a gas stove repair today for safe and efficient cooking."
        ];
    }

    // ── 29. WATER PUMP REPAIR ──
    private static function waterPumpRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers complete water pump repair services in {$city} covering submersible pumps, monoblock pumps, jet pumps, borewell pumps, and pressure boosters from Kirloskar, Crompton, Havells, CRI, KSB, and Texmo. Services include motor rewinding, impeller replacement, capacitor repair, and full overhauling.{$hours}",

            "Homeowners and farmers across {$area} and {$city} rely on {$business} in {$location} for skilled mechanics, genuine parts, on-site service, and AMC plans for societies and farms. Book your water pump repair today for an uninterrupted water supply."
        ];
    }

    // ── 30. INDUCTION STOVE REPAIR ──
    private static function inductionStoveRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} provides expert induction stove and cooktop repair services in {$city} including glass top replacement, sensor repair, IGBT and PCB issues, error code troubleshooting, and full servicing for Prestige, Bajaj, Pigeon, Philips, Havells, and Sunflame induction units.{$hours}",

            "Households across {$area} and {$city} trust {$business} in {$location} for trained technicians, original parts, doorstep service, and clear service warranties. Book your induction stove repair today and keep your kitchen running without interruption."
        ];
    }

    // ── 31. HOME APPLIANCES REPAIR (catch-all) ──
    private static function homeAppliancesRepair($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a one-stop home appliance repair service in {$city} covering AC, refrigerator, washing machine, microwave, geyser, chimney, cooler, gas stove, induction stove, water purifier, and TV repairs across all major brands including LG, Samsung, Whirlpool, Bosch, IFB, and Haier.{$hours}",

            "Customers across {$area} and {$city} rely on {$business} in {$location} for trained multi-brand technicians, doorstep service, original parts, AMC packages, and transparent pricing. Call once and get every appliance fixed under one roof."
        ];
    }

    // ── 32. ELECTRIC SERVICES ──
    private static function electricServices($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers complete electrical services in {$city} including new wiring, rewiring, switchboard repair, MCB and ELCB installation, inverter setup, fan installation, light fitting, geyser installation, AC point work, and full electrical maintenance for homes, offices, and shops.{$hours}",

            "Property owners across {$area} and {$city} trust {$business} in {$location} for licensed electricians, ISI-marked materials, safe earthing, fair pricing, and post-service warranty. Call today for any electrical repair, installation, or full house wiring requirement."
        ];
    }

    // ── 33. CARPENTER ──
    private static function carpenter($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} provides professional carpentry services in {$city} including modular wardrobe making, modular kitchen, TV unit, study table, bed and headboard, door and window repair, hinge and lock fitting, and complete custom furniture in plywood, MDF, particle board, and solid wood.{$hours}",

            "Homeowners across {$area} and {$city} hire {$business} in {$location} for skilled carpenters, accurate measurements, branded hardware, on-time delivery, and transparent quotes. Book a site visit today and transform your space with quality woodwork."
        ];
    }

    // ── 34. PAINTER ──
    private static function painter($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers professional painting contractor services in {$city} including interior wall painting, exterior weatherproof painting, wood polishing, metal painting, texture paints, distemper, emulsion, enamel, and waterproof coatings using Asian Paints, Berger, Nerolac, and Dulux products.{$hours}",

            "Homeowners across {$area} and {$city} choose {$business} in {$location} for skilled painters, dust-free finishes, on-time project delivery, free shade consultation, and transparent labour rates. Get a free site visit today for a fresh, modern paint job."
        ];
    }

    // ── 35. CIVIL CONTRACTOR ──
    private static function civilContractor($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a reliable civil contractor in {$city} offering full house construction, renovation, slab casting, flooring, tiling, plastering, brickwork, waterproofing, and turnkey project execution for residential, commercial, and industrial sites. Materials, labour, and supervision are all handled in-house.{$hours}",

            "Clients across {$area} and {$city} trust {$business} in {$location} for licensed engineers, ISI-grade materials, structured timelines, transparent BOQs, and quality control at every stage. Schedule a site survey today and get a detailed construction or renovation quote."
        ];
    }

    // ── 36. HOME CONSTRUCTION ──
    private static function homeConstruction($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers turnkey home construction services in {$city} from plot survey, architectural design, structural drawings, foundation, RCC slab, plumbing, electrical, plastering, flooring, painting, and final finishing. Every project is executed under licensed civil engineer supervision with ISI-grade materials.{$hours}",

            "Homeowners across {$area} and {$city} trust {$business} in {$location} for transparent per-square-foot pricing, milestone-based payments, BOQ-driven estimates, on-time delivery, and post-handover support. Book a free site visit today and start building your dream home with confidence."
        ];
    }

    // ── 37. HOME FURNITURE ──
    private static function homeFurniture($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a premium home furniture and decoration store in {$city} offering modular sofas, dining sets, beds, wardrobes, study tables, TV units, recliners, kids furniture, and complete home furnishing solutions in solid wood, engineered wood, and metal finishes.{$hours}",

            "Customers across {$area} and {$city} love {$business} in {$location} for modern designs, durable build, free home delivery, expert installation, and EMI options. Visit the showroom or order online today and give your home a refreshed, stylish look."
        ];
    }

    // ── 38. INTERIOR DESIGNER ──
    private static function interiorDesigner($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a top interior designer in {$city} offering modular kitchens, walk-in wardrobes, false ceilings, TV units, wall panelling, lighting design, 3D visualisation, and full home and office interior turnkey solutions in modern, contemporary, minimalist, and luxury styles.{$hours}",

            "Homeowners across {$area} and {$city} choose {$business} in {$location} for award-winning designers, branded hardware (Hettich, Hafele, Blum), transparent BOQs, on-time delivery, and 5-year service warranty. Book a free design consultation today and transform your home or office."
        ];
    }

    // ── 39. CLEANING SERVICES ──
    private static function cleaningServices($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers professional cleaning services in {$city} including deep home cleaning, kitchen and bathroom scrubbing, sofa and carpet shampooing, mattress cleaning, office cleaning, post-construction cleaning, and full sanitisation using eco-friendly chemicals and machine-based methods.{$hours}",

            "Homes and offices across {$area} and {$city} trust {$business} in {$location} for trained crews, branded chemicals, fixed-price packages, and same-day booking. Book a deep cleaning today and enjoy a fresh, hygienic, and germ-free space."
        ];
    }

    // ── 40. HOUSEKEEPING ──
    private static function housekeeping($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} provides reliable housekeeping services in {$city} for residential societies, corporate offices, hotels, hospitals, schools, and retail outlets. Services include daily cleaning, floor mopping, toilet sanitisation, glass cleaning, pantry maintenance, and uniformed deployment of trained staff.{$hours}",

            "Clients across {$area} and {$city} choose {$business} in {$location} for background-verified staff, supervisor visits, EPF/ESI compliance, and structured monthly billing. Request a quote today for daily, weekly, or full-time housekeeping deployment."
        ];
    }

    // ── 41. MAID SERVICE ──
    private static function maidService($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers verified maid and servant services in {$city} including part-time maids, full-time maids, 24-hour live-in helpers, cooks, babysitters, elderly care attendants, and househelp for cleaning, washing, and kitchen work.{$hours}",

            "Families across {$area} and {$city} trust {$business} in {$location} for background-verified, Aadhaar-validated staff, free replacement guarantee, and transparent service contracts. Book a maid today and bring reliable, professional help into your home."
        ];
    }

    // ── 42. LAUNDRY SERVICE ──
    private static function laundryService($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a professional laundry and dry cleaning service in {$city} offering wash and fold, steam iron, dry cleaning of suits, sarees, lehengas, blazers, curtains, blankets, sneakers, and leather goods. Pickup and delivery are available across {$area}.{$hours}",

            "Customers across {$area} and {$city} prefer {$business} in {$location} for fabric-safe chemicals, hygienic packaging, transparent per-piece pricing, and quick turnaround. Schedule a pickup today and free yourself from laundry hassles."
        ];
    }

    // ── 43. LIFT & ELEVATOR ──
    private static function liftElevator($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers complete lift and elevator solutions in {$city} including new installation, AMC service, modernisation, breakdown repair, rope and roller replacement, ARD installation, and 24×7 emergency support for passenger lifts, home lifts, hospital lifts, freight lifts, and capsule elevators.{$hours}",

            "Builders, RWAs, and businesses across {$area} and {$city} trust {$business} in {$location} for certified engineers, OEM-grade spares, safety audits, and government-approved installation. Request a site survey today for a custom lift quote or AMC plan."
        ];
    }

    // ── 44. WELDER & FABRICATION ──
    private static function welder($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} provides expert welding and fabrication services in {$city} for grills, gates, railings, staircases, MS steel doors, stainless steel works, balcony grills, structural fabrication, and on-site arc and TIG welding for homes, shops, and industrial sites.{$hours}",

            "Property owners and contractors across {$area} and {$city} rely on {$business} in {$location} for skilled welders, ISI-grade material, accurate measurements, durable finishing, and on-time delivery. Get a free site quote today for your fabrication project."
        ];
    }
   
    // ── 45. SECURITY SYSTEM ──
    private static function securitySystem($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} provides advanced security system solutions in {$city} including CCTV camera installation, biometric access control, intercom systems, video door phones, motion sensors, burglar alarms, fire alarm panels, and cloud-based DVR/NVR setups for homes, shops, offices, and societies.{$hours}",

            "Property owners across {$area} and {$city} trust {$business} in {$location} for branded products (CP Plus, Hikvision, Dahua), certified installers, AMC plans, and 24×7 technical support. Book a free security audit today and secure your premises with modern surveillance."
        ];
    }

    // ── 46. SECURITY GUARD ──
    private static function securityGuard($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a reliable security guard agency in {$city} providing trained security personnel for residential societies, corporate offices, factories, malls, schools, hospitals, and events. Services include armed guards, unarmed guards, bouncers, gunmen, lady guards, and supervisors with full uniform and equipment.{$hours}",

            "Clients across {$area} and {$city} trust {$business} in {$location} for PSARA-licensed services, background-verified staff, EPF/ESI compliance, supervisor patrolling, and 24×7 control room support. Request a quote today for short-term events or long-term security deployment."
        ];
    }

    // ── 47. FIRE SAFETY ──
    private static function fireSafety($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers complete fire safety solutions in {$city} including fire extinguisher sales and refilling, fire alarm panel installation, smoke detectors, sprinkler systems, hydrant systems, fire NOC consultancy, fire audits, and fire safety training for offices, factories, malls, and schools.{$hours}",

            "Businesses across {$area} and {$city} rely on {$business} in {$location} for ISI-certified equipment, licensed engineers, AMC packages, and government-approved fire NOC documentation. Schedule a fire safety inspection today and stay 100% compliant and protected."
        ];
    }
     
    // ── 48. REAL ESTATE ──
    private static function realEstate($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a trusted real estate agency in {$city} dealing in residential flats, builder floors, independent houses, plots, farmhouses, commercial shops, office spaces, and rental properties. Verified listings, RERA-approved projects, and end-to-end documentation support are offered for buyers, sellers, and tenants.{$hours}",

            "Property seekers across {$area} and {$city} trust {$business} in {$location} for honest deals, on-site visits, home loan assistance, legal verification, and registry support. Connect today to buy, sell, or rent the right property at the right price."
        ];
    }

    // ── 49. PG HOSTEL ──
    private static function pgHostel($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a well-maintained PG and hostel in {$city} offering single, double, and triple-sharing rooms for students and working professionals. Amenities include home-cooked meals, hot water, attached washrooms, AC and non-AC options, Wi-Fi, laundry, weekly housekeeping, and 24×7 security with CCTV.{$hours}",

            "Residents from across {$area} and {$city} pick {$business} in {$location} for safe environment, verified staff, hygienic kitchens, and budget-friendly rent plans. Book your PG room today with no brokerage and zero hidden charges."
        ];
    }

    // ── 50. HOTEL ──
    private static function hotel($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a popular hotel in {$city} offering comfortable AC rooms, deluxe and executive suites, family rooms, in-room dining, free Wi-Fi, conference halls, and banquet facilities. The hotel is well connected to major landmarks, railway station, and the airport.{$hours}",

            "Travellers across {$area} and {$city} pick {$business} in {$location} for spotless rooms, friendly staff, on-time check-in, multi-cuisine restaurant, and best-rate online bookings. Reserve your stay today and enjoy a relaxed, comfortable visit."
        ];
    }

    // ── 51. 5-STAR HOTEL ──
    private static function fiveStarHotel($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a luxurious 5-star hotel in {$city} offering premium suites, plush rooms, multi-cuisine fine-dining restaurants, world-class spa, swimming pool, fitness centre, business lounges, and grand banquet halls for weddings, conferences, and corporate events.{$hours}",

            "Discerning guests across {$area} and {$city} pick {$business} in {$location} for impeccable hospitality, personalised butler service, gourmet dining, and award-winning facilities. Reserve your luxury experience today for an unforgettable stay."
        ];
    }

    // ── 52. BUDGET HOTEL ──
    private static function budgetHotel($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a comfortable budget hotel in {$city} offering clean AC and non-AC rooms, hot water, complimentary breakfast, free Wi-Fi, 24×7 reception, and CCTV-secured premises at pocket-friendly prices for students, families, and business travellers.{$hours}",

            "Guests across {$area} and {$city} prefer {$business} in {$location} for honest pricing, hygienic rooms, helpful staff, and easy online booking with no hidden charges. Book your stay today and travel smart without overspending."
        ];
    }

    // ── 53. RESTAURANT ──
    private static function restaurant($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a popular restaurant in {$city} serving a delicious menu of North Indian, South Indian, Chinese, Mughlai, Continental, and tandoori specialities along with desserts, beverages, and family combo meals. The hygienic kitchen, friendly staff, and cosy ambience make every meal memorable.{$hours}",

            "Food lovers across {$area} and {$city} pick {$business} in {$location} for fresh ingredients, generous portions, value-for-money pricing, online ordering, and party booking options. Visit today or order online for a complete dining experience."
        ];
    }
 
    // ── 54. FINANCE SERVICE ──
    private static function financeService($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a trusted finance services provider in {$city} offering mutual funds, SIPs, insurance (life, health, motor), tax planning, GST filing, ITR filing, business loans, and personal financial advisory for salaried professionals, business owners, and NRIs.{$hours}",

            "Clients across {$area} and {$city} pick {$business} in {$location} for SEBI-registered advisors, transparent commissions, goal-based planning, and end-to-end documentation support. Book a free consultation today and start building long-term wealth."
        ];
    }

    // ── 55. LOAN SERVICE ──
    private static function loanService($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a trusted loan service provider in {$city} offering personal loans, home loans, business loans, education loans, car loans, loan against property, and balance transfers with minimum documentation and competitive interest rates from top banks and NBFCs.{$hours}",

            "Borrowers across {$area} and {$city} trust {$business} in {$location} for quick eligibility checks, CIBIL improvement guidance, doorstep documentation, and fast disbursal. Apply today and get the right loan with full transparency and zero hidden charges."
        ];
    }

    // ── 56. ATM ──
    private static function atm($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a conveniently located ATM in {$city} supporting cash withdrawals, mini statements, balance enquiry, PIN change, and card-to-card transfers for all major banks and debit/credit card networks including Visa, MasterCard, RuPay, and American Express.{$hours}",

            "Residents and visitors across {$area} and {$city} use {$business} in {$location} for 24×7 access, well-lit premises, CCTV-secured cabins, and reliable uptime. Visit anytime for fast, safe banking transactions near you."
        ];
    }

    // ── 57. TOUR & TRAVEL ──
    private static function tourTravel($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a leading tour and travel agency in {$city} offering customised holiday packages, group tours, religious yatras, honeymoon trips, international tours, visa assistance, flight bookings, hotel reservations, and cab and bus rentals for domestic and overseas destinations.{$hours}",

            "Travellers across {$area} and {$city} trust {$business} in {$location} for transparent itineraries, verified hotels, professional tour managers, and 24×7 on-trip support. Plan your next holiday today and travel stress-free with expertly curated packages."
        ];
    }

    // ── 58. PACKERS & MOVERS ──
    private static function packersMovers($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers professional packers and movers services in {$city} including local shifting, intercity relocation, household goods packing, car and bike transportation, office relocation, and warehousing. Trained crews use bubble wrap, corrugated sheets, wooden crates, and GPS-tracked trucks.{$hours}",

            "Families and businesses across {$area} and {$city} rely on {$business} in {$location} for IBA-approved transit, insured cargo, transparent quotations, and on-time delivery. Get a free moving estimate today for a stress-free, damage-free shift."
        ];
    }

    // ── 59. JOB CONSULTANT ──
    private static function jobConsultant($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a leading job consultancy in {$city} offering placement services for freshers and experienced professionals in IT, BPO, sales, banking, finance, healthcare, engineering, manufacturing, and government-sector roles. Resume building, interview prep, and skill assessment are part of the placement process.{$hours}",

            "Job seekers across {$area} and {$city} trust {$business} in {$location} for verified employers, no upfront fees, fast interview scheduling, and post-placement support. Register your profile today and accelerate your career with the right job match."
        ];
    }

    // ── 60. LAWYER ──
    private static function lawyer($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is an experienced lawyer and advocate in {$city} handling civil, criminal, family, matrimonial, divorce, property, cheque bounce, consumer, cyber, corporate, taxation, and high court matters. Legal services include case filing, drafting, notices, agreements, court representation, and out-of-court settlements.{$hours}",

            "Clients across {$area} and {$city} trust {$business} in {$location} for honest legal advice, confidentiality, strong courtroom representation, and transparent fees. Book a confidential consultation today and get expert guidance for your legal matter."
        ];
    }

     
    // ── 61. PROFESSIONAL TRAINING (general) ──
    private static function professionalTraining($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a well-known training institute in {$city} offering professional certification courses with practical projects, expert mentors, industry-relevant curriculum, hands-on assignments, mock interviews, and 100% placement assistance for students and working professionals.{$hours}",

            "Learners across {$area} and {$city} pick {$business} in {$location} for small batches, flexible timings, online and offline modes, EMI fee options, and lifetime course access. Enrol today and turn your learning into a real career boost."
        ];
    }

    // ── 62. COMPUTER COURSES ──
    private static function computerCourses($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a top computer training institute in {$city} offering courses in Python, Java, C/C++, full-stack web development, MERN, MEAN, data science, machine learning, cloud computing (AWS/Azure), DevOps, SQL, Tally, MS Office, and graphic designing. Every program includes live projects, certifications, and placement support.{$hours}",

            "Students and professionals across {$area} and {$city} trust {$business} in {$location} for industry-trained mentors, modern labs, recorded sessions, and verified placement records. Enrol today to build job-ready IT skills and accelerate your tech career."
        ];
    }

    // ── 63. ENGINEERING COURSES ──
    private static function engineeringCourses($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers specialised engineering training courses in {$city} for civil, electrical, electronics, mechanical, embedded systems, robotics, and IoT. Courses cover AutoCAD, Revit, STAAD Pro, MATLAB, PLC/SCADA, PCB design, Arduino, Raspberry Pi, and hands-on industry projects.{$hours}",

            "Engineering students and graduates across {$area} and {$city} pick {$business} in {$location} for experienced faculty, certified labs, project guidance, and placement assistance. Enrol today and add high-demand technical skills to your engineering profile."
        ];
    }

    // ── 64. DIGITAL MARKETING TRAINING ──
    private static function digitalMarketingTraining($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a leading digital marketing training institute in {$city} offering courses in SEO, Google Ads, Meta Ads, social media marketing, email marketing, content marketing, e-commerce marketing, analytics, and AI-powered marketing tools. Every module includes live campaigns and real client projects.{$hours}",

            "Students, freelancers, and business owners across {$area} and {$city} pick {$business} in {$location} for Google-certified trainers, hands-on practice, internship opportunities, and placement assistance with leading agencies. Enrol today and turn digital marketing into a high-income career or business growth engine."
        ];
    }

    // ── 65. SHARE MARKET TRAINING ──
    private static function shareMarketTraining($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers practical share market training in {$city} covering stock market basics, technical analysis, fundamental analysis, intraday trading, swing trading, options strategies, futures, commodity, and currency trading using charting platforms like TradingView, Zerodha Kite, and Upstox.{$hours}",

            "Traders and investors across {$area} and {$city} trust {$business} in {$location} for SEBI-aware curriculum, live market sessions, mentor support, and risk-management focused strategies. Join the next batch and start making smarter, data-driven trades."
        ];
    }

    // ── 66. ENTRANCE EXAM COACHING (general) ──
    private static function entranceExamCoaching($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a result-oriented entrance exam coaching centre in {$city} preparing students for banking exams, SSC, RRB, state PCS, defence, NDA, CDS, GATE, NTSE, KVPY, polytechnic, teachers' eligibility, and other competitive exams with full classroom, doubt sessions, and weekly tests.{$hours}",

            "Aspirants across {$area} and {$city} pick {$business} in {$location} for expert faculty, structured study material, free mock tests, performance tracking, and proven selection record. Enrol today and turn your exam preparation into a guaranteed result."
        ];
    }

    // ── 67. UPSC COACHING ──
    private static function upscCoaching($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a top UPSC and civil services coaching institute in {$city} offering full courses for Prelims (GS + CSAT), Mains (GS 1-4, essay, optional subjects), interview, and current affairs. The programme includes daily answer writing, NCERT revision, test series, mentorship, and study planners.{$hours}",

            "IAS aspirants across {$area} and {$city} pick {$business} in {$location} for ex-IAS mentors, comprehensive notes, doubt-clearing sessions, and a proven Prelims-to-Mains-to-Interview roadmap. Enrol today and start your civil services journey with expert guidance."
        ];
    }

    // ── 68. IIT-JEE COACHING ──
    private static function iitJeeCoaching($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a leading IIT-JEE coaching institute in {$city} preparing students for JEE Main, JEE Advanced, and other engineering entrance exams. The curriculum covers Physics, Chemistry, and Mathematics with concept building, problem-solving, doubt sessions, weekly tests, and full-length mocks.{$hours}",

            "Students and parents across {$area} and {$city} trust {$business} in {$location} for IIT-alumni faculty, NCERT-based foundation classes (8th-10th), focused 11th-12th programmes, performance analytics, and a strong selection record. Enrol today and start preparing for top IITs and NITs."
        ];
    }

    // ── 69. NEET COACHING ──
    private static function neetCoaching($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a top NEET coaching institute in {$city} preparing students for MBBS, BDS, AYUSH, and allied medical courses. The programme covers Physics, Chemistry, and Biology with NCERT-based teaching, daily practice problems, doubt clearing, weekly tests, and AIIMS-pattern mock exams.{$hours}",

            "Medical aspirants across {$area} and {$city} pick {$business} in {$location} for experienced MBBS faculty, micro-topic test series, biology focus modules, and a proven NEET selection record. Enrol today and start your journey towards a top medical college."
        ];
    }

    // ── 70. CAT COACHING ──
    private static function catCoaching($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a leading CAT and MBA entrance coaching institute in {$city} preparing students for CAT, XAT, SNAP, NMAT, MAT, CMAT, IIFT, and state-level management entrance exams. Quant, VARC, LRDI, GD/PI, and WAT modules are taught by IIM and top B-school alumni.{$hours}",

            "MBA aspirants across {$area} and {$city} trust {$business} in {$location} for adaptive mock tests, sectional analysis, mentor support, and personalised B-school admission counselling. Enrol today and accelerate your path to IIMs and top management institutes."
        ];
    }

    // ── 71. LAW ENTRANCE COACHING ──
    private static function lawEntranceCoaching($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a top law entrance coaching institute in {$city} preparing students for CLAT, AILET, LSAT, SLAT, MH-CET Law, and other top law entrance exams. The programme covers English, Logical Reasoning, Legal Reasoning, GK and Current Affairs, and Quant with classroom teaching and weekly mocks.{$hours}",

            "Law aspirants across {$area} and {$city} pick {$business} in {$location} for NLU-alumni faculty, structured study material, current affairs digests, and a proven selection record into NLUs. Enrol today and start preparing for a successful legal career."
        ];
    }

    // ── 72. HOTEL MGMT ENTRANCE COACHING ──
    private static function hotelMgmtEntranceCoaching($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a specialised hotel management entrance coaching institute in {$city} preparing students for NCHMCT JEE, IIHM eCHAT, IHM, and other hospitality entrance exams. The programme covers Reasoning, GK, English, Aptitude, and personality development for GD/PI rounds.{$hours}",

            "Hospitality aspirants across {$area} and {$city} pick {$business} in {$location} for expert mentors, mock interviews, grooming sessions, and a proven IHM selection record. Enrol today and launch your career in hotel and hospitality management."
        ];
    }

    // ── 73. DESIGN ENTRANCE COACHING ──
    private static function designEntranceCoaching($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a leading design entrance coaching institute in {$city} preparing students for NID, NIFT, UCEED, CEED, and architecture entrance exams. The programme covers drawing, creative aptitude, design thinking, material handling, situation tests, and portfolio building under expert mentors.{$hours}",

            "Aspiring designers across {$area} and {$city} trust {$business} in {$location} for NID/NIFT-alumni faculty, studio environment, mock interviews, and a strong record of NID, NIFT, and IIT design selections. Enrol today and launch your career in design."
        ];
    }

    // ── 74. CA COACHING (CA / CS / CMA / ICWA / CFA) ──
    private static function caCoaching($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a top professional coaching institute in {$city} for CA, CS, CMA, ICWA, CFA, CFP, and other finance and accounting certifications. The programme covers Foundation, Intermediate, and Final levels with concept-based teaching, MCQ practice, test series, and revision modules.{$hours}",

            "Commerce students across {$area} and {$city} pick {$business} in {$location} for qualified CA/CS/CMA faculty, structured study planner, doubt sessions, and a strong all-India rank record. Enrol today and start your journey into India's most rewarding finance careers."
        ];
    }

    // ── 75. SCHOOL TUITION ──
    private static function schoolTuition($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers expert school and college tuitions in {$city} for classes 6 to 12 across CBSE, ICSE, and state board curricula. Subjects include Maths, Physics, Chemistry, Biology, English, Accounts, Economics, Business Studies, Hindi, and Social Science with regular tests and parent-teacher reviews.{$hours}",

            "Students and parents across {$area} and {$city} pick {$business} in {$location} for qualified teachers, small batches, doubt-clearing sessions, NCERT-focused teaching, and proven board exam results. Book a free demo class today and improve grades steadily."
        ];
    }

    // ── 76. LANGUAGE CLASSES ──
    private static function languageClasses($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a popular foreign language institute in {$city} offering classes in English, French, German, Spanish, Japanese, Mandarin, Korean, Arabic, Russian, and Italian. Courses follow CEFR levels (A1 to C2) with native-style speaking practice, grammar, writing, and exam preparation.{$hours}",

            "Students, working professionals, and travellers across {$area} and {$city} pick {$business} in {$location} for certified trainers, small batches, online and offline modes, and global certifications like DELF, TestDaF, DELE, and JLPT. Enrol today and add a new language to your skill set."
        ];
    }

    // ── 77. TEST PREP ABROAD ──
    private static function testPrepAbroad($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a trusted study-abroad test prep institute in {$city} offering coaching for IELTS, TOEFL, PTE, GRE, GMAT, SAT, ACT, and Duolingo English Test. Programs include strategy sessions, sectional drills, full-length mocks, speaking practice, and personalised score improvement plans.{$hours}",

            "Study-abroad aspirants across {$area} and {$city} pick {$business} in {$location} for certified trainers, 90+ verified score record, university shortlisting support, and visa documentation guidance. Book a free demo class today and start your international education journey."
        ];
    }

    // ── 78. SCHOOLS ──
    private static function schools($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a reputed school in {$city} offering quality education from kindergarten to class 12 under CBSE, ICSE, IB, or state board curricula. The school focuses on academic excellence, sports, arts, music, robotics, debate, and overall personality development with experienced teachers and modern infrastructure.{$hours}",

            "Parents across {$area} and {$city} trust {$business} in {$location} for safe campus, smart classrooms, transport, mid-day meals, regular PTMs, and strong board exam results. Schedule a school visit today to explore admissions and curriculum details."
        ];
    }

    // ── 79. COLLEGES ──
    private static function colleges($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a respected college in {$city} offering UG and PG programmes in engineering, management, science, commerce, arts, law, hotel management, pharmacy, nursing, journalism, and design with NAAC/AICTE/UGC-approved curriculum, experienced faculty, and modern labs.{$hours}",

            "Students across {$area} and {$city} pick {$business} in {$location} for strong placement records, scholarship programmes, industry tie-ups, hostel facilities, and extracurricular exposure. Apply today for the upcoming admission cycle and shape a rewarding career."
        ];
    }

    // ── 80. PLAYSCHOOL ──
    private static function playSchool($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a well-known playschool and day-care in {$city} offering structured learning for kids aged 1.5 to 5 years through play-based curriculum, phonics, story time, music, dance, art, and outdoor activities in safe, CCTV-monitored classrooms with trained teachers and caregivers.{$hours}",

            "Parents across {$area} and {$city} trust {$business} in {$location} for child-safe environment, hygienic meals, parent communication apps, and a smooth transition to formal schooling. Book a free school tour today and explore admissions."
        ];
    }

    // ── 81. DISTANCE EDUCATION ──
    private static function distanceEducation($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a UGC-DEB approved distance education centre in {$city} offering BA, BCom, BBA, BCA, MA, MCom, MBA, MCA, and PG diploma programmes from top universities like IGNOU, NMIMS, Symbiosis, Manipal, Amity, and Annamalai University with full admission, study material, and exam support.{$hours}",

            "Working professionals and students across {$area} and {$city} pick {$business} in {$location} for verified university tie-ups, EMI fee options, online classes, and continuous admission counselling. Get a free programme suggestion today and continue your education without quitting your job."
        ];
    }

    // ── 82. OVERSEAS EDUCATION ──
    private static function overseasEducation($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a top overseas education consultant in {$city} offering admission assistance to universities in the USA, UK, Canada, Australia, New Zealand, Germany, Ireland, Singapore, Dubai, France, Italy, and more. Services cover course selection, SOP, LOR, visa, education loan, and pre-departure support.{$hours}",

            "Aspirants across {$area} and {$city} pick {$business} in {$location} for certified counsellors, transparent fees, scholarship guidance, and a proven visa-success record. Book a free counselling session today and start your study-abroad journey with confidence."
        ];
    }

    // ── 83. DEGREE PROGRAMS ──
    private static function degreePrograms($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers a wide range of accredited degree, diploma, certificate, and doctorate programmes in {$city} across engineering, management, IT, design, healthcare, finance, and arts streams. Programmes are UGC-recognised, with flexible online and on-campus options, dual specialisations, and industry internships.{$hours}",

            "Students and working professionals across {$area} and {$city} choose {$business} in {$location} for credible universities, scholarship support, EMI fee structures, and dedicated career mentors. Book a free admission counselling session today and pick the right programme."
        ];
    }

    // ── 84. MUSIC CLASSES ──
    private static function musicClasses($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a popular music academy in {$city} offering classes in vocals (Hindustani, Carnatic, Western), guitar, keyboard, piano, drums, violin, harmonium, tabla, ukulele, and music production. Trinity, Rockschool, and ABRSM exam preparation are available with regular recitals and certifications.{$hours}",

            "Students of all ages across {$area} and {$city} pick {$business} in {$location} for experienced gurus, structured curriculum, one-on-one attention, and stage performance exposure. Book a free trial class today and start your musical journey."
        ];
    }

    // ── 85. DANCE CLASSES ──
    private static function danceClasses($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a leading dance academy in {$city} offering classes in classical (Bharatanatyam, Kathak, Odissi, Kuchipudi), Bollywood, hip-hop, contemporary, salsa, Zumba, freestyle, K-pop, and wedding choreography for kids, teens, adults, and brides.{$hours}",

            "Dancers across {$area} and {$city} pick {$business} in {$location} for professional choreographers, structured batches, stage performance opportunities, and competition exposure. Join a free trial class today and start dancing your passion into a craft."
        ];
    }

    // ── 86. DRIVING SCHOOL ──
    private static function drivingSchool($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a government-approved driving school in {$city} offering car and two-wheeler driving lessons for beginners, women, senior citizens, and licence-renewal candidates. The course covers traffic rules, signals, parking, highway driving, night driving, and RTO test preparation.{$hours}",

            "Learners across {$area} and {$city} pick {$business} in {$location} for dual-control cars, patient instructors, flexible class timings, RTO licence assistance, and affordable packages. Enrol today and learn to drive safely and confidently."
        ];
    }

    // ── 87. TRANSLATOR SERVICE ──
    private static function translatorService($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a professional translator and interpreter service in {$city} offering certified translations for legal documents, passports, marriage certificates, academic transcripts, business contracts, medical records, and immigration paperwork in English, Hindi, French, German, Spanish, Arabic, Chinese, and more.{$hours}",

            "Clients across {$area} and {$city} pick {$business} in {$location} for embassy-accepted certifications, notarised translations, fast turnaround, and confidential handling. Submit your document today for an accurate, certified translation."
        ];
    }

     
    // ── 88. WEDDING PLANNER ──
    private static function weddingPlanner($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a top wedding planning company in {$city} offering end-to-end wedding management including venue selection, theme decor, catering, makeup, photography, mehendi, sangeet, cocktail, bridal entry, baraat coordination, and destination weddings.{$hours}",

            "Couples across {$area} and {$city} pick {$business} in {$location} for creative themes, vendor management, on-day execution, budget planning, and stress-free coordination. Book a free consultation today and let your dream wedding unfold flawlessly."
        ];
    }

    // ── 89. EVENT ORGANIZER ──
    private static function eventOrganizer($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a professional event management company in {$city} handling corporate events, product launches, conferences, exhibitions, college fests, birthday parties, baby showers, anniversaries, retirement parties, and theme-based celebrations with full creative and logistics support.{$hours}",

            "Clients across {$area} and {$city} pick {$business} in {$location} for experienced event managers, branded equipment, on-time execution, and transparent quotations. Book a free planning session today and turn your next event into a memorable success."
        ];
    }

    // ── 90. BIRTHDAY PARTY ──
    private static function birthdayParty($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} specialises in birthday party planning in {$city} offering kids' theme parties, princess and superhero setups, balloon decorations, magic shows, anchors, character mascots, return gifts, and customised cakes for 1st birthdays, milestone birthdays, and surprise parties.{$hours}",

            "Parents across {$area} and {$city} trust {$business} in {$location} for creative themes, hygienic catering, on-time setup, and end-to-end party coordination. Book a free consultation today and make your child's birthday truly unforgettable."
        ];
    }

     

    // ── 92. CATERING ──
    private static function catering($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a trusted catering service in {$city} offering customised menus for weddings, receptions, birthdays, corporate events, house parties, and religious functions. Cuisines include North Indian, South Indian, Mughlai, Chinese, Continental, Rajasthani, live counters, and elaborate dessert spreads.{$hours}",

            "Hosts across {$area} and {$city} pick {$business} in {$location} for hygienic kitchens, FSSAI compliance, uniformed serving staff, premium crockery, and on-time food delivery. Book a free menu tasting today and serve a meal your guests will remember."
        ];
    }

    // ── 93. STAGE DECORATOR ──
    private static function stageDecorator($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a leading stage decoration specialist in {$city} creating breathtaking setups for weddings, sangeet, mehendi, varmala, receptions, baby showers, and corporate events. Themes include royal, floral, pastel, traditional, mandap-style, and Insta-worthy backdrop designs.{$hours}",

            "Hosts across {$area} and {$city} trust {$business} in {$location} for skilled designers, premium fresh and artificial flowers, on-time setup, and creative theme customisation. Book a free site visit today and bring your event venue to life."
        ];
    }

    // ── 94. MAKEUP ARTIST ──
    private static function makeupArtist($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a professional makeup artist in {$city} offering bridal makeup, engagement makeup, reception looks, party makeup, HD makeup, airbrush makeup, pre-wedding shoots, and family member makeovers using premium brands like MAC, Huda Beauty, Bobbi Brown, and Charlotte Tilbury.{$hours}",

            "Brides and clients across {$area} and {$city} pick {$business} in {$location} for skin-friendly products, custom looks, on-location services, and trial makeup options. Book your slot today and look stunning on your big day with flawless, long-lasting makeup."
        ];
    }

    // ── 95. MEHENDI ARTIST ──
    private static function mehendiArtist($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a renowned mehendi artist in {$city} offering bridal mehendi, dulhan-style intricate designs, Arabic mehendi, minimal designs, family mehendi, and group mehendi services for weddings, sangeet, karwa chauth, teej, and Eid celebrations.{$hours}",

            "Brides and families across {$area} and {$city} pick {$business} in {$location} for organic cone mehendi, deep colour stain, fine detailing, on-time service, and on-location convenience. Book your mehendi artist today and adorn your hands with stunning designs."
        ];
    }

    // ── 96. BRIDAL WEAR / WEDDING DRESS ──
    private static function bridalWear($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a top bridal wear and wedding dress store in {$city} offering lehengas, sarees, gowns, sherwanis, indo-westerns, kurta sets, and family wedding outfits in designer collections from leading labels along with customised tailoring, alterations, and bridal styling.{$hours}",

            "Brides, grooms, and families across {$area} and {$city} pick {$business} in {$location} for premium fabrics, trending designs, accurate fitting, accessory pairing, and budget-friendly to luxury price ranges. Visit the showroom today and pick your perfect wedding outfit."
        ];
    }

    // ── 97. GHODI BAGGI ──
    private static function ghodiBaggi($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} provides traditional ghodi and baggi rental in {$city} for baraat and groom entry with decorated horses, royal buggies, vintage horse-drawn carriages, themed chariots, and elephant booking for grand wedding processions.{$hours}",

            "Grooms and families across {$area} and {$city} pick {$business} in {$location} for trained horses, ornate decorations, experienced handlers, on-time arrival, and safe baraat coordination. Book your ghodi-baggi today and add a royal touch to the groom's entry."
        ];
    }

    // ── 98. ROAD LIGHT ──
    private static function roadLight($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers professional road light and baraat lighting services in {$city} including LED gas lights, fancy chandeliers, RGB strips, themed lighting panels, generator-backed setups, and live electrician support for wedding processions, street decorations, and DJ baraats.{$hours}",

            "Wedding planners and grooms across {$area} and {$city} trust {$business} in {$location} for power-safe wiring, eye-catching designs, on-time setup, and well-coordinated baraat lighting. Book your wedding road light service today for a bright and grand procession."
        ];
    }

    // ── 99. FIREWORK & CRACKERS ──
    private static function firework($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers safe and licensed fireworks and crackers services in {$city} for weddings, receptions, varmala, baraat, baby showers, brand launches, and corporate events. Setups include sky shots, aerial fireworks, fountains, sparklers, ground spinners, and theme-based pyro shows.{$hours}",

            "Event hosts across {$area} and {$city} pick {$business} in {$location} for licensed pyro-technicians, safety briefing, fire-safety crew, and tested products. Book your firework display today and end your event with a spectacular finale."
        ];
    }

    // ── 100. HONEYMOON PACKAGE ──
    private static function honeymoonPackage($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers customised honeymoon packages from {$city} to top destinations like Maldives, Bali, Thailand, Dubai, Europe, Mauritius, Switzerland, Andaman, Goa, Kerala, Kashmir, and Himachal. Packages include flights, hotels, transfers, candle-light dinners, sightseeing, and visa assistance.{$hours}",

            "Newlyweds across {$area} and {$city} pick {$business} in {$location} for romantic itineraries, verified resorts, transparent pricing, EMI options, and 24×7 on-trip support. Book your honeymoon today and start married life with unforgettable memories."
        ];
    }

    // ── 101. COURT MARRIAGE ──
    private static function courtMarriage($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers complete court marriage services in {$city} under the Special Marriage Act and Hindu Marriage Act, including document preparation, marriage notice, affidavits, witness arrangement, registrar coordination, marriage certificate, and tatkal court marriage where permitted.{$hours}",

            "Couples across {$area} and {$city} trust {$business} in {$location} for licensed advocates, end-to-end legal handling, fast registration, and confidential service. Book your court marriage consultation today and complete the process legally and stress-free."
        ];
    }

    // ── 102. DHOL SHEHNAI BAJA ──
    private static function dholShehnai($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} provides traditional dhol, shehnai, and band baja services in {$city} for baraat, sangeet, mehendi, varmala entry, religious functions, and corporate cultural events. Performers include Punjabi dhol players, professional brass bands, shehnai maestros, and folk drummers.{$hours}",

            "Hosts across {$area} and {$city} pick {$business} in {$location} for energetic performances, traditional attire, on-time arrival, and customised song requests. Book your dhol-shehnai team today and bring authentic festive sound to your celebration."
        ];
    }

    // ── 103. WEDDING SINGER & DANCER ──
    private static function weddingSinger($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers professional wedding singers, dancers, and live performance artists in {$city} for sangeet, mehendi, cocktail, reception, and DJ nights. The roster includes Bollywood singers, Punjabi performers, Sufi vocalists, anchor hosts, choreographers, and live band setups.{$hours}",

            "Couples and event planners across {$area} and {$city} pick {$business} in {$location} for high-energy performances, themed costumes, sound equipment, and curated song lists. Book your wedding singer or dance troupe today and turn every function into a stage show."
        ];
    }

    // ── 104. WEDDING ASTROLOGER ──
    private static function weddingAstrologer($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is an experienced wedding astrologer in {$city} offering kundali matching, gun milan, manglik dosha analysis, shubh muhurat for marriage, vivah panchami, gemstone consultation, and remedies for a happy married life. Online and in-person consultations are available.{$hours}",

            "Families across {$area} and {$city} trust {$business} in {$location} for accurate kundali analysis, honest guidance, ritual recommendations, and confidential consultations. Book your astrology session today and start your marriage on auspicious grounds."
        ];
    }

    // ── 105. FLOWER DECORATION ──
    private static function flowerDecoration($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers stunning flower decoration services in {$city} for weddings, mandap, reception stage, varmala, mehendi, sangeet, car decoration, home pooja, and corporate events using fresh roses, marigolds, orchids, lilies, jasmine, and seasonal exotic flowers.{$hours}",

            "Wedding planners and hosts across {$area} and {$city} pick {$business} in {$location} for premium fresh flowers, creative theme designs, on-time setup, and budget-friendly to luxury packages. Book a free site visit today and transform your venue with floral magic."
        ];
    }

     
    // ── 107. TENT HOUSE ──
    private static function tentHouse($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a trusted tent house in {$city} offering complete event setups including shamiana, kanat, German hangars, dome tents, stage backdrops, chairs, tables, sofas, carpets, cutlery, generators, and lighting for weddings, receptions, religious functions, and corporate events.{$hours}",

            "Hosts across {$area} and {$city} pick {$business} in {$location} for clean tents, branded utensils, prompt setup and dismantling, and affordable per-event pricing. Book your tent house today and get a full venue setup from one reliable team."
        ];
    }

     

    // ── 109. CAR DECORATION ──
    private static function carDecoration($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers stunning wedding car decoration services in {$city} using fresh flowers, artificial flowers, ribbons, themed setups, royal styles, and personalized name plates. Whether it's a vintage car, luxury sedan, SUV, or wedding limousine, every decoration is tailored to match the wedding theme.{$hours}",

            "Trusted by hundreds of couples across {$area} and {$city}, {$business} in {$location} ensures timely setup, premium flowers, and elegant designs that turn the bridal car into a memorable centerpiece. Book your wedding car decoration today and add a beautiful finishing touch to your big day."
        ];
    }

    // ── 110. WEDDING CAR RENTAL ──
    private static function weddingCarRental($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} offers premium wedding car rental services in {$city} including luxury sedans (Mercedes, BMW, Audi), classic vintage cars, limousines, Range Rovers, decorated SUVs, and royal couple-entry cars with experienced chauffeurs for baraat, bidaai, and reception.{$hours}",

            "Couples across {$area} and {$city} pick {$business} in {$location} for spotlessly clean cars, professional drivers, on-time pickup, and flexible per-day or per-event packages. Book your wedding car today and arrive in royal style."
        ];
    }

    // ── 111. JAGRAN PARTY ──
    private static function jagranParty($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a renowned jagran and bhajan party in {$city} offering Mata ki Chowki, Devi jagran, Sai sandhya, Krishna leela, kirtan, and devotional musical nights with experienced singers, musicians, harmonium, dholak, tabla, and a complete devotional ambience.{$hours}",

            "Devotees across {$area} and {$city} pick {$business} in {$location} for soulful performances, traditional setup, on-time arrival, and customised devotional song requests. Book your jagran party today and host a divine, memorable night of devotion."
        ];
    }

  
    // ── 112. SPORTS ACADEMY ──
    private static function sportsAcademy($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a leading sports academy in {$city}, offering professional coaching for all age groups — kids, juniors, teens, and adults. The academy provides certified trainers, structured curriculum, modern equipment, fitness conditioning, match practice, and tournament exposure to help every player reach their full potential.{$hours}",

            "Whether you are a beginner looking to learn the basics or an aspiring athlete training for state and national tournaments, {$business} in {$location} offers personalized coaching plans and supportive learning environments. Parents and players across {$area} and {$city} trust {$business} for skill development, discipline, and competitive growth. Enrol today for a free trial session."
        ];
    }

    // ── 113. HOME SERVICES (generic) ──
    private static function homeServices($business, $area, $city, $location, $hours): array
    {
        return [
            "{$business} in {$location} is a trusted home services provider in {$city} offering deep cleaning, electrical work, plumbing, painting, pest control, appliance repair, carpenter, and handyman services under one roof. Skilled professionals, branded tools, and transparent pricing make every visit hassle-free.{$hours}",

            "Households across {$area} and {$city} pick {$business} in {$location} for verified staff, on-time service, post-service warranty, and easy online booking. Schedule a service today and keep your home spotless and fully functional."
        ];
    }



}
