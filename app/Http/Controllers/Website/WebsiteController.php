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

		$search_kw = $request->input('keyword');
		$city = $request->input('city');
		$cityName = ucwords(str_replace('-', ' ', $city));
		$keywordName = ucwords(str_replace('-', ' ', $search_kw));

		$keywordDetails = DB::table('keyword')
			->join('parent_category', 'keyword.parent_category_id', '=', 'parent_category.id')
			->join('child_category', 'keyword.child_category_id', '=', 'child_category.id')
			->where('keyword', 'LIKE', '' . ucwords(str_replace('-', ' ', $search_kw)) . '')
			->select('keyword.*', 'parent_category.*', 'child_category.*', 'keyword.id as key_id', 'keyword.faqq1', 'keyword.faqa1', 'keyword.faqq2', 'keyword.faqa2', 'keyword.faqq3', 'keyword.faqa3', 'keyword.faqq4', 'keyword.faqa4', 'keyword.faqq5', 'keyword.faqa5', 'keyword.meta_title', 'keyword.meta_description', 'keyword.meta_keywords', 'keyword.top_description', 'keyword.bottom_description', 'keyword.ratingvalue', 'keyword.ratingcount')
			->first();
		if (!$keywordDetails) {
			return response()->json([
				'status' => false,
				'message' => 'Keyword not found'
			], 404);
		}

		$category_banner = config('app.website') . 'client/images/computer-courses-training.jpg';

		$alt = "";

		if (!empty($keywordDetails->category_banner)) {
			$cicons = unserialize($keywordDetails->category_banner);

			if (!empty($cicons)) {
				$category_banner = config('app.website') . $cicons['category_banner']['src'];
				$alt = $cicons['category_banner']['name'];
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

		$top_description = "";
		if (!empty($keywordDetails->top_description)) {
			$top_description = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->top_description);
		}
		$bottom_description = "";
		if (!empty($keywordDetails->bottom_description)) {
			$bottom_description = preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->bottom_description);
		}

		$zones = DB::table('citylists')->join('zones', 'zones.city_id', '=', 'citylists.id')->where('citylists.city', 'LIKE', $city)->select('zones.id', 'zones.zone')->orderBy('zones.zone','asc')->distinct()->get();
		 
		 
		$data['keyword'] = array(
			'keyword' => $keywordDetails->keyword,
			'keyword_slug' => generate_slug($keywordDetails->keyword),
			'category_banner' => $category_banner,
			'alt' => $alt,
			'meta_title' => $meta_title,
			'meta_keywords' => $meta_keywords,
			'meta_description' => $meta_description,
			'top_description' => $top_description,
			'bottom_description' => $bottom_description,
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
			'ratingvalue' => $keywordDetails->ratingvalue,
			'ratingcount' => $keywordDetails->ratingcount,
			'parent_category' => $keywordDetails->parent_category,
			'parent_slug' => $keywordDetails->parent_slug,
			'child_category' => $keywordDetails->child_category,
			'child_slug' => $keywordDetails->child_slug,
			'zone' => $zones,
			'city' => $cityName,
			 

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
			->where('citylists.city', 'LIKE', "{$cityName}")
			// ->where('clients.active_status', '1')
			->where('keyword.keyword', 'LIKE', "{$keywordName}")
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
					 'keyword.keyword as keyword',
					 'keyword.slug as slug',
					'clients.client_type',
					'c.rating',
					'c.comment_count'
				)
				->where('keyword.keyword', 'LIKE', '' . $keywordName . '')
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
				->pluck('keyword.keyword','keyword.slug')
				->toArray();
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
				'website' => $client->website,
				'verified' => $client->verified,
				'trending' => $client->trending,
				'topSearch' => $client->topSearch,
				'featured' => $client->featured,
				'description' => $client->description,
				'mapUrl' => "https://maps.google.com/?q=".generate_slug($client->address),		 
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
				'url' => '/' . $keyword->slug,
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

		$data['findOtherLocation'] = $cityList;

		return response()->json([
			'success' => true,
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
				'url' => 'categories/professional-courses',
				'img' => config('app.website') . 'img/IT-Training.png',
				'alt' => 'computer courses',
				'title' => 'computer courses',
				'type' => 'categories',
				'rating' => '4',
				'count' => '434',

			],
			[
				'url' => 'child/wedding-planning',
				'img' => config('app.website') . 'img/wedding.png',
				'alt' => 'Wedding Planning',
				'title' => 'Wedding Planning',
				'type' => 'child',
				'rating' => '4',
				'count' => '234',

			],
			[
				'url' => 'categories/electric-services',
				'img' => config('app.website') . 'img/electric-services.png',
				'alt' => 'Electric Services',
				'title' => 'Electric Services',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '377',

			],
			[
				'url' => 'categories/entrance-exams-coaching',
				'img' => config('app.website') . 'img/government-exam.png',
				'alt' => 'Government exam',
				'title' => 'Government exam',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '229',

			],
			[
				'url' => 'categories/study-abroad',
				'img' => config('app.website') . 'img/study-abroad.png',
				'alt' => 'Study Abroad',
				'title' => 'Study Abroad',
				'type' => 'categories',
				'rating' => '5',
				'count' => '399',

			],
			[
				'url' => 'categories/spa',
				'img' => config('app.website') . 'img/Spa & Beauty.png',
				'alt' => 'Spa & Beauty',
				'title' => 'Spa & Beauty',
				'type' => 'categories',
				'rating' => '5',
				'count' => '325',

			],
			[
				'url' => 'child/repair-services',
				'img' => config('app.website') . 'img/Repairs-Services.png',
				'alt' => 'Repair Services',
				'title' => 'Repair Services',
				'type' => 'child',
				'rating' => '5',
				'count' => '389',

			],
			[
				'url' => 'child/packers-and-movers',
				'img' => config('app.website') . 'img/Packers-movers.png',
				'alt' => 'Packers & Movers',
				'title' => 'Packers & Movers',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '199',

			],
			[
				'url' => 'categories/professional',
				'img' => config('app.website') . 'img/Professional.png',
				'alt' => 'Professional',
				'title' => 'Professional',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '149',

			],
			[
				'url' => 'child/contractors',
				'img' => config('app.website') . 'img/contractors.png',
				'alt' => 'Contractors',
				'title' => 'Contractors',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '167',

			],
			[
				'url' => 'categories/collages-and-Institutions',
				'img' => config('app.website') . 'img/Education.png',
				'alt' => 'Education',
				'title' => 'Education',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '197',

			],
			[
				'url' => 'child/rent-or-buy',
				'img' => config('app.website') . 'img/Rent-buy.png',
				'alt' => 'Rent & Buy',
				'title' => 'Rent & Buy',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '329',
			],
			[
				'url' => 'child/sports-academy',
				'img' => config('app.website') . 'img/sports.png',
				'alt' => 'Sport Academy',
				'title' => 'Sport Academy',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '539',
			],
			[
				'url' => 'child/medical',
				'img' => config('app.website') . 'img/Medical.png',
				'alt' => 'Medical',
				'title' => 'Medical',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '269',
			],
			[
				'url' => 'child/loan-service',
				'img' => config('app.website') . 'img/Loan.png',
				'alt' => 'Loan',
				'title' => 'Loan',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '69',
			],
			[
				'url' => 'categories/dancing',
				'img' => config('app.website') . 'img/Dancing.png',
				'alt' => 'Dancing',
				'title' => 'Dancing',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '79',
			],
			[
				'url' => 'child/yoga-classes',
				'img' => config('app.website') . 'img/Yoga.png',
				'alt' => 'Yoga',
				'title' => 'Yoga',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '89',
			],
			[
				'url' => 'child/security-system',
				'img' => config('app.website') . 'img/CCTV-security.png',
				'alt' => 'CCTV Security',
				'title' => 'CCTV Security',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '109',
			],
			[
				'url' => 'child/web-technologies',
				'img' => config('app.website') . 'images/Web-Designers.png',
				'alt' => 'Web Designers',
				'title' => 'Web Designers',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '106',
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
				'url' => 'child/repair-services',
				'img' => config('app.website') . 'img/Repairs-Services.png',
				'alt' => 'Repair Services',
				'title' => 'Repair Services',
				'type' => 'child',
				'rating' => '5',
				'count' => '389',

			],
			[
				'url' => 'child/rent-or-buy',
				'img' => config('app.website') . 'img/Rent-buy.png',
				'alt' => 'Rent & Buy',
				'title' => 'Rent & Buy',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '329',
			],
			[
				'url' => 'child/packers-and-movers',
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
				'url' => 'categories/professional-courses',
				'img' => config('app.website') . 'img/IT-Training.png',
				'alt' => 'computer courses',
				'title' => 'computer courses',
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
				'url' => 'categories/electric-services',
				'img' => config('app.website') . 'img/electric-services.png',
				'alt' => 'Electric Services',
				'title' => 'Electric Services',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '377',

			],
			[
				'url' => 'categories/entrance-exams-coaching',
				'img' => config('app.website') . 'img/government-exam.png',
				'alt' => 'Government exam',
				'title' => 'Government exam',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '229',

			],
			[
				'url' => 'categories/study-abroad',
				'img' => config('app.website') . 'img/study-abroad.png',
				'alt' => 'Study Abroad',
				'title' => 'Study Abroad',
				'type' => 'categories',
				'rating' => '5',
				'count' => '399',

			],
			[
				'url' => 'categories/spa',
				'img' => config('app.website') . 'img/Spa & Beauty.png',
				'alt' => 'Spa & Beauty',
				'title' => 'Spa & Beauty',
				'type' => 'categories',
				'rating' => '5',
				'count' => '325',

			],
			[
				'url' => 'categories/professional',
				'img' => config('app.website') . 'img/Professional.png',
				'alt' => 'Professional',
				'title' => 'Professional',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '149',

			],
			[
				'url' => 'child/contractors',
				'img' => config('app.website') . 'img/contractors.png',
				'alt' => 'Contractors',
				'title' => 'Contractors',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '167',

			],
			[
				'url' => 'categories/collages-and-Institutions',
				'img' => config('app.website') . 'img/Education.png',
				'alt' => 'Education',
				'title' => 'Education',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '197',

			],
			
			[
				'url' => 'child/sports-academy',
				'img' => config('app.website') . 'img/sports.png',
				'alt' => 'Sport Academy',
				'title' => 'Sport Academy',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '539',
			],
			[
				'url' => 'child/medical',
				'img' => config('app.website') . 'img/Medical.png',
				'alt' => 'Medical',
				'title' => 'Medical',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '269',
			],
			[
				'url' => 'child/loan-service',
				'img' => config('app.website') . 'img/Loan.png',
				'alt' => 'Loan',
				'title' => 'Loan',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '69',
			],
			[
				'url' => 'categories/dancing',
				'img' => config('app.website') . 'img/Dancing.png',
				'alt' => 'Dancing',
				'title' => 'Dancing',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '79',
			],
			[
				'url' => 'child/yoga-classes',
				'img' => config('app.website') . 'img/Yoga.png',
				'alt' => 'Yoga',
				'title' => 'Yoga',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '89',
			],
			[
				'url' => 'child/security-system',
				'img' => config('app.website') . 'img/CCTV-security.png',
				'alt' => 'CCTV Security',
				'title' => 'CCTV Security',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '109',
			],
			[
				'url' => 'child/web-technologies',
				'img' => config('app.website') . 'images/Web-Designers.png',
				'alt' => 'Web Designers',
				'title' => 'Web Designers',
				'type' => 'categories',
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
					$i =0;
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
				'mapUrl' => "https://maps.google.com/?q=".generate_slug($client->address),
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
			[
				'Grow Client' => $clients . ' +',
				'Suppliers' => $childCategory . ' +',
				'Products & Services' => $citieslists . ' K+',
				'Keyword' => $keyword . ' +',
				'Store' => $parentCategory . ' +',
				'Platform' => $parentCategory . ' K+',
			],

		];
		
		 
		$data['popularSearches'] = [
			[
				'url' => 'categories/computer-courses',
				'img' => config('app.website') . 'popular/IT-Training.jpg',
				'alt' => 'computer courses',
				'title' => 'computer courses',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '139',

			],
			[
				'url' => 'categories/entrance-exams-coaching',
				'img' => config('app.website') . 'popular/Entrance-Exam.jpg',
				'alt' => 'Entrance exam',
				'title' => 'Entrance exam',
				'type' => 'categories',
				'rating' => '3.5',
				'count' => '99',

			],
			[
				'url' => 'child/packers-and-movers',
				'img' => config('app.website') . 'popular/Packers-Movers.jpg',
				'alt' => 'Packers & Movers',
				'title' => 'Packers & Movers',
				'type' => 'child',
				'rating' => '3.5',
				'count' => '132',

			],
			[
				'url' => 'categories/interior-designer',
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
				'img' => config('app.website') . 'popular/carpenter.jpg',
				'alt' => 'Wedding Planning',
				'title' => 'Wedding Planning',
				'type' => 'keyword',
				'rating' => '3.5',
				'count' => '119',

			],[
				'url' => 'carpenters',
				'img' => config('app.website') . 'popular/carpenter.jpg',
				'alt' => 'Carpenters',
				'title' => 'Carpenters',
				'type' => 'keyword',
				'rating' => '3.5',
				'count' => '16',

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
					'url' => '/blog/' . $blog->slug,
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
				'url' => 'clinical-research',
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
				'img' => config('app.website') . 'popular/Carpenters.jpg',
				'alt' => 'Carpenters',
				'title' => 'Carpenters',
				'type' => 'keyword',
				'rating' => '4.8',
				'count' => '463',
			],
			[
				'url' => 'washing-machine-repairs',
				'img' => config('app.website') . 'popular/Carpenters.jpg',
				'alt' => 'Carpenters',
				'title' => 'Carpenters',
				'type' => 'keyword',
				'rating' => '4.8',
				'count' => '463',
			],[
				'url' => 'cctv-installation-training',
				'img' => config('app.website') . 'popular/Carpenters.jpg',
				'alt' => 'Carpenters',
				'title' => 'Carpenters',
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
				'url' => 'categories/entrance-exams-coaching',
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
				'url' => 'categories/entrance-exams-coaching',
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
						'url' => 'child/' . $study->child_slug,
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
			->paginate(10);

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
				'name' => $blog->name,
				'url' => 'blog/' . $blog->slug,
				'img' => $image,
				'alt' => $alt,
				'title' => $blog->name,
				'description' => ucfirst(substr(strip_tags($blog->description), 0, 220)) . '...',

			];
		}

		return response()->json([
			'success' => true,
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

		$blogLists = Blogdetails::where('status', '1')->limit(8)->orderBy('id', 'DESC')->get();

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
					'url' => 'blog/' . $blog->slug,
					'img' => $image,
					'alt' => $alt,
					'title' => $blog->name,
					'description' => ucfirst(substr(strip_tags($blog->description), 0, 220)) . '...',

				];
			}
		}

		$data['blogList'] = $blogPageList;
		$blogdetails = Blogdetails::where('slug', $slug)->first();
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
				'name' => $blogdetails->name,
				'url' => 'blog/' . $blogdetails->slug,
				'blogImage' => $blogimage,
				'blogalt' => $blogalt,
				'imageBanner' => $imageBanner,
				'blogBannerAalt' => $blogaltB,
				'title' => $blogdetails->name,
				'description' => ucfirst($blogdetails->description),
				'meta_title' => ucfirst($blogdetails->meta_title),
				'meta_keywords' => ucfirst($blogdetails->meta_keywords),
				'meta_description' => ucfirst($blogdetails->meta_description),
				'top_content' => ucfirst($blogdetails->top_content),
				'bottom_content' => ucfirst($blogdetails->bottom_content),

			];
		}
		$data['blogDetails'] = $blogPageDetails;

		return response()->json([
			'success' => true,
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
	 *         required=true,
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
			->select('keyword.*', 'parent_category.*', 'child_category.*', 'keyword.id as key_id', 'keyword.faqq1', 'keyword.faqa1', 'keyword.faqq2', 'keyword.faqa2', 'keyword.faqq3', 'keyword.faqa3', 'keyword.faqq4', 'keyword.faqa4', 'keyword.faqq5', 'keyword.faqa5', 'keyword.meta_title', 'keyword.meta_description', 'keyword.meta_keywords', 'keyword.top_description', 'keyword.bottom_description', 'keyword.ratingvalue', 'keyword.ratingcount')
			->first();

 

		$category_banner = config('app.website') . 'client/images/computer-courses-training.jpg';

		$alt = "";

		if (!empty($keywordDetails->category_banner)) {
			$cicons = unserialize($keywordDetails->category_banner);

			if (!empty($cicons)) {
				$category_banner = config('app.website') . $cicons['category_banner']['src'];
				$alt = $cicons['category_banner']['name'];
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


		$data['keyword'] = array(
			'keyword' => $keywordDetails->keyword,
			'keyword_slug' => $keywordDetails->slug,
			'category_banner' => $category_banner,
			'alt' => $alt,
			'meta_title' => $meta_title,
			'meta_keywords' => $meta_keywords,
			'meta_description' => $meta_description,
			'top_description' => $top_description,
			'bottom_description' => $bottom_description,
			'faqq1' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqq1),
			'faqa1' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqa1),
			'faqq2' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqq2),
			'faqa2' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqa2),
			'faqq3' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqq3),
			'faqa3' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqa3),
			'faqq4' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqq4),
			'faqa4' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqa4),
			'faqq5' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqq5),
			'faqa5' => preg_replace('/{{city}}/i', ucfirst($city), $keywordDetails->faqa5),
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

			$logoImage = config('app.website') .'client/images/default_pp_small.png';
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
				'mapUrl' => "https://maps.google.com/?q=".generate_slug($client->address),
				'whatsapp' => '7559435943',
				'call' => '917559435943',
				'rating' => $client->rating,
				'openUntil' => $client->openUntil,
				'avgRating' => $avgRating,
				'comment_count' => $client->comment_count,
				'keywords' => $assignedKwds ?? null,
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

		return response()->json([
			'success' => true,
			'data' => $data,
		], 200);
	}

	/**
	 * @OA\Get(
	 *     path="/api/website/categories",
	 *     tags={"Website"},
	 *     summary="Website get categories",
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


	public function getCategories(Request $request)
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
				'parent_category.parent_category as parent_name',
				'child_category.id as child_id',
				'child_category.child_slug',
				'child_category.child_category as child_name',
				'child_category.pc_icon as 	pc_icon'
			)
			->where('parent_category.parent_slug', $slug)
			// ->where('parent_category.parent_slug', 'LIKE', '%' . $slug . '%')
			->orderBy('child_category.child_category', 'asc')
			->groupBy(
				'parent_category.id',
				'parent_category.parent_slug',
				'parent_category.parent_category',
				'child_category.id',
				'child_category.child_slug',
				'child_category.child_category'
			)
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
					'url' => "child/".$child->child_slug,
					'img' => $image,
					'alt' => $alt,
					'title' => $child->child_name,
					'type' => 'child',
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
			'data' => $data,
		], 200);


	}
	/**
	 * @OA\Get(
	 *     path="/api/website/child",
	 *     tags={"Website"},
	 *     summary="Website get child",
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


	public function getChild(Request $request)
	{

		$request->validate([
			'child-slug' => 'required|exists:child_category,child_slug',
		]);
		$slug = $request->input('child-slug');


		if (empty($slug) || !is_string($slug)) {
			return response()->json([
				'success' => false,
				'message' => 'Invalid or missing child-slug parameter.',
			], 400);
		}

		$data['childLists'] = DB::table('child_category')
			->join('keyword', 'keyword.child_category_id', '=', 'child_category.id')
			->select(
				'keyword.keyword',
				'keyword.icon as icon',
				'keyword.slug'
			)
			->where('child_category.child_slug', $slug)
			->orderBy('keyword.keyword', 'asc')
			->groupBy('keyword.keyword', 'keyword.icon', 'keyword.slug')
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
					'url' => $keyword->slug,
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
				'message' => 'No keyword found.',
			], 404);
		}

		// 🔹 Return Response
		return response()->json([
			'success' => true,
			'data' => $locations->values(),
		], 200);
	}

	/**
	 * @OA\Get(
	 *     path="/api/website/business-details/{slug}",
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


	public function businessDetails(Request $request, $slug)
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
        SELECT SUM(rating) AS rating, comment_client_ID, COUNT(comment_ID) AS comment_count
        FROM comments GROUP BY comment_client_ID
    ) c'), 'c.comment_client_ID', '=', 'clients.id')
			->select(
				'clients.*',
				'clients.id as business_id',
				'assigned_kwds.*',
				'citylists.city',
				'assigned_kwds.sold_on_position',
				'c.rating',
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
// dd($clientscheck);
		if (!empty($clientscheck)) {

			$logoImage = config('app.website').'client/images/default_pp_small.png';
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

			$gallery = config('app.website') . 'client/images/default_profile_pic.jpg';
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
				$avgRating = round(($sum / ($count * 5)) * 5, 1);
			$data['sum'] = $sum;
			$data['avgRating'] = $avgRating;
			$data['count'] = $count;
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
				'rating' => $clientscheck->rating,
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


			$data['certificate'] = [
				'gst_no' => $clientscheck->gst_no ?? '',
				'gst_certificate' => $gstImage,
				'pan_no' => $clientscheck->pan_no,
				'cin_no' => $clientscheck->cin_no ?? '',
				'cin_certificate' => $cinImage,
				'iso_no' => $clientscheck->iso_no ?? '',
				'iso_certificate' => $isoImage ?? '',
				'msme_no' => $clientscheck->msme_no ?? '',
				'msme_certificate' => $msmeImage ?? '',
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
			];


			if (!empty($assignedKeywords)) {
				$findKeywords = Keyword::select('child_category_id')->where('keyword', $assignedKeywords[0])->first();

				$relKeywords = Keyword::select('keyword')->where('child_category_id', $findKeywords->child_category_id)
					->orderBy('keyword', 'asc')
					->pluck('keyword.keyword')
					->toArray();

				$data['related_searches'] = $relKeywords;
			}
			$area_business = [
				'heading' =>
					($clientscheck->business_name ?? '') .
					' in ' .
					($clientscheck->area ?? '') .
					', ' .
					($clientscheck->city ?? ''),

				'paragraph' =>
					($clientscheck->business_name ?? '') .
					', located in ' .
					($clientscheck->area ?? '') .
					', ' .
					($clientscheck->city ?? '') .
					', has been a leader in skill development for many years. The company specializes in providing a comprehensive range of training programs designed to equip individuals with practical knowledge and expertise.'
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

			$overviewParagraph = ($clientscheck->business_name ?? '') . ' in ' .
				($clientscheck->area ?? '') . ', ' .
				($clientscheck->city ?? '') . ' is a prominent institution in the SAP Training Institutes sector, offering various skill-building programs tailored to meet the demands of today’s competitive job market.' . $workingHoursHtml . 'The company provides flexible scheduling options for individuals looking to enhance their skills while managing other responsibilities.The highly experienced team at ' . ($clientscheck->business_name ?? '') . ' is committed to delivering high-quality training to each participant.';
			$overviewParagraph2 = 'Whether you’re looking to improve your technical skills, leadership capabilities, or industry-specific knowledge,' . ($clientscheck->business_name ?? '') . ' in ' . ($clientscheck->area ?? '') . ', ' . ($clientscheck->city ?? '') . ' has the right program for you. With a wide range of offerings, including IT, management, soft skills, and vocational training, ' . ($clientscheck->business_name ?? '') . ' stands as a comprehensive solution for all your skill development needs.';

			$overview_business = [
				'heading' => 'Overview of Business',
				'paragraph' => $overviewParagraph,
				'paragraph1' => $overviewParagraph2
			];

			$data['overview_business'] = $overview_business;
			return response()->json([
				'success' => true,
				'data' => $data,
			], 200);

		} else {
			return response()->json([
				'success' => false,
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
				'url' => 'categories/professional-courses',
				'title' => 'Coaching & Tuitions',
				'type' => 'categories',

			],
			[
				'url' => '/child/wedding-planning',
				'title' => 'Wedding Planning',
				'type' => 'child',

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
				'url' => 'categories/electric-services',
				'title' => 'Electric Services',
				'type' => 'categories',

			],
			[
				'url' => 'categories/security-system',
				'title' => 'Security System',
				'type' => 'categories',

			],
			[
				'url' => 'categories/medical',
				'title' => 'Medical',
				'type' => 'categories',
			],
			[
				'url' => 'categories/packers-movers',
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
				'url' => 'categories/repairs-services',
				'title' => 'Repairs Services',
				'type' => 'categories',
			],
			[
				'url' => 'categories/spa-beauty',
				'title' => 'SPA Beauty',
				'type' => 'categories',
			],
			[
				'url' => 'child/loan',
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
			[
				'Grow Client' => $clients . ' +',
				'Suppliers' => $childCategory . ' +',
				'Products & Services' => $citieslists . ' K+',
				'Keyword' => $keyword . ' +',
				'Store' => $parentCategory . ' +',
				'Platform' => $parentCategory . ' K+',
			],

		];
		$data['grow_your_business'] = [
			[
				"Grow Your Business" => "Sell to buyers anytime, anywhere",
				"Zero Cost" => "No commission or transaction fee",
				"Manage Your Business Better" => "Lead Management System & other features",
				"Create Account" => "Add your name and phone number to get started",
				"Add Business" => "Add the name, e-mail of your company, store/business.",
				"Add Products/Services" => "Minimum 3 products/services needed for your free listing page.",

			]

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
			[
				"Grow business" => "Join thousands of businesses that trust Lead for their workforce management needs.",
				"Active Client" => $clients,
				"Employees Tracked" => $lead . " +",
				"Customer Satisfaction" => "100%",
				"Average Lead Increase" => "35%",
				"Business Kewyord" => $keyword,


			],


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
			[
				'q1' => ' What is Quick Dials?',
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
				'q7' => 'I Need More info?',
				'a7' => 'For More Info & any Queries, you can Contact Us on +91  75-5943-5943 or reach out to us via e-mail @ info@quickdials.com, or list your business as free listing, our marketing team Will Contact you Soon.',
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
				'url' => 'categories/personal-finance-services',
				'title' => 'Personal Finance Services',
				'type' => 'categories',

			],
			[
				'url' => 'categories/tours-travel-services',
				'title' => 'Tours & Travels',
				'type' => 'categories',

			],
			[
				'url' => 'categories/property-dealer',
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
				'url' => 'categories/computer-courses',
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
				'url' => 'categories/electric-services',
				'title' => 'Electric Services',
				'type' => 'categories',
			],
			[
				'url' => 'categories/entrance-exams-coaching',
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
				'url' => 'child/yoga-classes',
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
				'url' => 'categories/entrance-exams-coaching',
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
					// dd($item);
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

}
