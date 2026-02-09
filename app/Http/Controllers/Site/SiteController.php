<?php

namespace App\Http\Controllers\Site;

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
use Session;
use App\Models\ParentCategory;
use App\Models\Client\Comment;
use App\Models\HomeSlider;
class SiteController extends Controller
{

	/**
	 * @OA\Get(
	 *     path="/api/site/city/keyword",
	 *     tags={"Frontend Search city and keyword"},
	 *     summary="Search records",
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
			->where('keyword', 'LIKE', '%' . ucwords(str_replace('-', ' ', $search_kw)) . '%')
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

		$zones = DB::table('citylists')->join('zones', 'zones.city_id', '=', 'citylists.id')->where('citylists.city', 'LIKE', $city)->select('zones.id', 'zones.zone')->distinct()->get();

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

		);




		$clientscheck = DB::table('clients')
			->join('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id')
			->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
			->join('citylists', 'assigned_kwds.city_id', '=', 'citylists.id')
			->leftJoin(DB::raw('(
        SELECT SUM(rating) AS rating, comment_client_ID, COUNT(comment_ID) AS comment_count
        FROM comments GROUP BY comment_client_ID
    ) c'), 'c.comment_client_ID', '=', 'clients.id')
			->select(
				'clients.*',
				'assigned_kwds.*',
				'citylists.city',
				'assigned_kwds.sold_on_position',
				'c.rating',
				'c.comment_count'
			)
			->where('citylists.city', 'LIKE', "%{$cityName}%")
			// ->where('clients.active_status', '1')
			->where('keyword.keyword', 'LIKE', "%{$keywordName}%")
			->orderByRaw("
        CASE assigned_kwds.sold_on_position
            WHEN 'platinum' THEN 1
            WHEN 'diamond' THEN 2
            WHEN 'FreeListing' THEN 3
            ELSE 4
        END
    ")
			->get();

		if ($clientscheck->count() > 0) {
			$clientsList = $clientscheck;
		} else {
			$clientsList = DB::table('clients')
				->join('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id')
				->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
				->join('citylists', 'assigned_kwds.city_id', '=', 'citylists.id')
				->leftJoin(DB::raw('(
            SELECT SUM(rating) AS rating, comment_client_ID, COUNT(comment_ID) AS comment_count
            FROM comments GROUP BY comment_client_ID
        ) c'), 'c.comment_client_ID', '=', 'clients.id')
				->select(
					'clients.*',
					'assigned_kwds.*',
					'citylists.city',
					'assigned_kwds.sold_on_position',
					'c.rating',
					'c.comment_count'
				)
				->where('keyword.keyword', 'LIKE', '%' . $keywordName . '%')
				->orderByRaw("
            CASE assigned_kwds.sold_on_position
                WHEN 'platinum' THEN 1
                WHEN 'diamond' THEN 2
                WHEN 'FreeListing' THEN 3
                ELSE 4
            END
        ")
				->get();
		}

		$data['clientsList'] = $clientsList->map(function ($client) {

			$logoImage = config('app.website') . 'client/images/default_pp_small.jpg';
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
			return [
				'business_id' => $client->id,
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
				'address' => $client->address,
				'pincode' => $client->pincode,
				'country' => $client->country,
				'year_of_estb' => $client->year_of_estb,
				'landmark' => $client->landmark,
				'rating' => $client->rating,
				'avgRating' => $avgRating,
				'comment_count' => $client->comment_count,
			];
		});


		$servicesRelated = Keyword::where('child_category_id', $keywordDetails->child_category_id)
			->where('parent_category_id', $keywordDetails->parent_category_id)
			->select('keyword', 'icon')
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
				'url' => '/' . generate_slug($keyword->keyword),
				'img' => $img,
				'alt' => $alt,
				'title' => $keyword->keyword,
			];
		})->values()->toArray();



		$data['servicesRelated'] = $servicesRelatedList;

		$cities = City::where('popular', '1')->get();
		$cityList = array();
		if ($cities) {
			foreach ($cities as $ckey => $cvalue) {

				$cityList[$ckey] = array(
					'url' => '/' . strtolower($cvalue->city) . '/' . generate_slug($keywordDetails->keyword),
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
	 *     path="/api/site/homePage",
	 *     tags={"Frontend Home Page"},
	 *     summary="Search records",
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
				'url' => '/categories/professional-courses',
				'img' => config('app.website') . 'img/IT-Training.png',
				'alt' => 'computer courses',
				'title' => 'computer courses',

			],
			[
				'url' => '/child/wedding-planning',
				'img' => config('app.website') . 'img/wedding.png',
				'alt' => 'Wedding Planning',
				'title' => 'Wedding Planning',

			],
			[
				'url' => '/categories/electric-services',
				'img' => config('app.website') . 'img/electric-services.png',
				'alt' => 'Electric Services',
				'title' => 'Electric Services',

			],
			[
				'url' => '/categories/entrance-exams-coaching',
				'img' => config('app.website') . 'img/government-exam.png',
				'alt' => 'Government exam',
				'title' => 'Government exam',

			],
			[
				'url' => '/categories/study-abroad',
				'img' => config('app.website') . 'img/study-abroad.png',
				'alt' => 'Study Abroad',
				'title' => 'Study Abroad',

			],
			[
				'url' => '/categories/spa-beauty',
				'img' => config('app.website') . 'img/Spa & Beauty.png',
				'alt' => 'Spa & Beauty',
				'title' => 'Spa & Beauty',

			],
			[
				'url' => '/categories/repairs-services',
				'img' => config('app.website') . 'img/Repairs-Services.png',
				'alt' => 'Repair Services',
				'title' => 'Repair Services',

			],
			[
				'url' => '/categories/packers-movers',
				'img' => config('app.website') . 'img/Packers-movers.png',
				'alt' => 'Packers & Movers',
				'title' => 'Packers & Movers',

			],
			[
				'url' => '/categories/professional',
				'img' => config('app.website') . 'img/Professional.png',
				'alt' => 'Professional',
				'title' => 'Professional',

			],
			[
				'url' => '/categories/contractors',
				'img' => config('app.website') . 'img/contractors.png',
				'alt' => 'Contractors',
				'title' => 'Contractors',

			],
			[
				'url' => '/categories/distance-education',
				'img' => config('app.website') . 'img/Education.png',
				'alt' => 'Education',
				'title' => 'Education',

			],
			[
				'url' => '/categories/rent-buy',
				'img' => config('app.website') . 'img/Rent-buy.png',
				'alt' => 'Rent & Buy',
				'title' => 'Rent & Buy',
			],
			[
				'url' => '/child/sports-academy',
				'img' => config('app.website') . 'img/sports.png',
				'alt' => 'Sport Academy',
				'title' => 'Sport Academy',
			],
			[
				'url' => '/categories/medical',
				'img' => config('app.website') . 'img/Medical.png',
				'alt' => 'Medical',
				'title' => 'Medical',
			],
			[
				'url' => '/categories/loan',
				'img' => config('app.website') . 'img/Loan.png',
				'alt' => 'Loan',
				'title' => 'Loan',
			],
			[
				'url' => '/categories/dancing',
				'img' => config('app.website') . 'img/Dancing.png',
				'alt' => 'Dancing',
				'title' => 'Dancing',
			],
			[
				'url' => '/categories/yoga',
				'img' => config('app.website') . 'img/Yoga.png',
				'alt' => 'Yoga',
				'title' => 'Yoga',
			],
			[
				'url' => '/categories/security-system',
				'img' => config('app.website') . 'img/CCTV-security.png',
				'alt' => 'CCTV Security',
				'title' => 'CCTV Security',
			],
			[
				'url' => '/categories/web-technologies',
				'img' => config('app.website') . 'images/Web-Designers.png',
				'alt' => 'Web Designers',
				'title' => 'Web Designers',
			],
			[
				'url' => '/tours-and-travels',
				'img' => config('app.website') . 'images/tour-travels.png',
				'alt' => 'Tours & Travels',
				'title' => 'Tours & Travels',
			],

		];
		$data['popularSearches'] = [
			[
				'url' => '/categories/computer-courses',
				'img' => config('app.website') . 'popular/IT-Training.jpg',
				'alt' => 'computer courses',
				'title' => 'computer courses',

			],
			[
				'url' => '/categories/entrance-exams-coaching',
				'img' => config('app.website') . 'popular/Entrance-Exam.jpg',
				'alt' => 'Entrance exam',
				'title' => 'Entrance exam',

			],
			[
				'url' => '/categories/packers-movers',
				'img' => config('app.website') . 'popular/Packers-Movers.jpg',
				'alt' => 'Packers & Movers',
				'title' => 'Packers & Movers',

			],
			[
				'url' => '/categories/interior-designer',
				'img' => config('app.website') . 'popular/Interior-design.jpg',
				'alt' => 'Interior Design',
				'title' => 'GInterior Design',

			],
			[
				'url' => '/real-estate-agent',
				'img' => config('app.website') . 'popular/real-estate-agent.jpg',
				'alt' => 'Real Estate Agents',
				'title' => 'Real Estate Agents',

			],
			[
				'url' => '/carpenters',
				'img' => config('app.website') . 'popular/carpenter.jpg',
				'alt' => 'Carpenters',
				'title' => 'Carpenters',

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
			$data['code'] = 200;
			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;
			$data['code'] = 200;
			$data['message'] = "failed";
		}


		return response()->json([
			'success' => true,
			'data' => $data,
		], 200);


	}

	/**
	 * @OA\Get(
	 *     path="/api/site/repairsServices",
	 *     tags={"Frontend Home Page Repairs Services"},
	 *     summary="Repairs Services records",
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
				'url' => '/ac-service',
				'img' => config('app.website') . 'popular/AC-Service.jpg',
				'alt' => 'AC Service',
				'title' => 'AC Service',

			],
			[
				'url' => '/car-service',
				'img' => config('app.website') . 'popular/car-services.jpg',
				'alt' => 'Car Services',
				'title' => 'Car Services',

			],
			[
				'url' => '/laundry-service',
				'img' => config('app.website') . 'popular/washing-machines.jpg',
				'alt' => 'Laundry Services',
				'title' => 'Laundry Services',

			],
			[
				'url' => '/electricity-service',
				'img' => config('app.website') . 'popular/Electricity-Services.jpg',
				'alt' => 'Electrician Services',
				'title' => 'Electrician Services',

			],
			[
				'url' => '/hotels',
				'img' => config('app.website') . 'popular/Hotel-Services.jpg',
				'alt' => 'Hotels',
				'title' => 'Hotels',

			],
			[
				'url' => '/categories/clinical-research-training',
				'img' => config('app.website') . 'popular/Fitness-Services.jpg',
				'alt' => 'Health & Fitness',
				'title' => 'Health & Fitness',

			]


		];
		if ($data) {
			$data['status'] = true;
			$data['code'] = 200;
			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;
			$data['code'] = 200;
			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'data' => $data,
		], 200);

	}

	/**
	 * @OA\Get(
	 *     path="/api/site/weddingPlanning",
	 *     tags={"Frontend Home Page Wedding Planning"},
	 *     summary="Wedding Planning records",
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
				'url' => '/catering-services',
				'img' => config('app.website') . 'popular/Catering-Services.jpg',
				'alt' => 'Catering Services',
				'title' => 'Catering Services',

			],
			[
				'url' => '/banquet-hall',
				'img' => config('app.website') . 'popular/Banquet-Halls.jpg',
				'alt' => 'Banquet Halls',
				'title' => 'Banquet Halls',

			],
			[
				'url' => '/stage-decorators',
				'img' => config('app.website') . 'popular/Stage-Decorators.jpg',
				'alt' => 'Stage Decorators',
				'title' => 'Stage Decorators',

			],
			[
				'url' => '/makeup-artists',
				'img' => config('app.website') . 'popular/makeup-artists.jpg',
				'alt' => 'Makeup Artists',
				'title' => 'Makeup Artists',

			],
			[
				'url' => '/mehendi-artists',
				'img' => config('app.website') . 'popular/Mehendi-Artists.jpg',
				'alt' => 'Mehendi Artists',
				'title' => 'Mehendi Artists',

			],
			[
				'url' => '/bridal-wear',
				'img' => config('app.website') . 'popular/Bridal-Wear.jpg',
				'alt' => 'Bridal Wear',
				'title' => 'Bridal Wear',

			]


		];

		if ($data) {
			$data['status'] = true;
			$data['code'] = 200;
			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;
			$data['code'] = 200;
			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'data' => $data,
		], 200);

	}
	/**
	 * @OA\Get(
	 *     path="/api/site/wedding-page",
	 *     tags={"Frontend Home Page Wedding page"},
	 *     summary="Wedding Page records",
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
				'url' => '/banquet-hall',
				'img' => config('app.website') . 'popular/Banquet_Hall.jpg',
				'alt' => 'Banquet Hall',
				'title' => 'Banquet Hall',

			],
			[
				'url' => '/ghoda-baggi',
				'img' => config('app.website') . 'popular/Ghoda_Baggi.jpg',
				'alt' => 'Ghoda Baggi & Rath',
				'title' => 'Ghoda Baggi & Rath',

			],
			[
				'url' => '/fire-works-and-crackers',
				'img' => config('app.website') . 'popular/Fire_Works_&_Crackers.jpg',
				'alt' => 'Fire Works Crackers',
				'title' => 'Fire Works Crackers',

			],
			[
				'url' => '/photo-and-videography',
				'img' => config('app.website') . 'popular/Photo_and_Videography.jpg',
				'alt' => 'Photo and Videography',
				'title' => 'Photo and Videography',

			],
			[
				'url' => '/flower-decoration',
				'img' => config('app.website') . 'popular/Flower_Decoration.jpg',
				'alt' => 'Flower Decoration',
				'title' => 'Flower Decoration',

			]
		];
		$data['wedding_prerequisits'] = [

			[
				'url' => '/banquet-hall',
				'img' => config('app.website') . 'popular/Banquet-Halls.jpg',
				'alt' => 'Banquet Halls',
				'title' => 'Banquet Halls',

			],
			[
				'url' => '/dj-sound-system',
				'img' => config('app.website') . 'popular/DJ_Sound_System.jpg',
				'alt' => 'DJ Sound Systems',
				'title' => 'DJ Sound Systems',

			],
			[
				'url' => '/party-organisers',
				'img' => config('app.website') . 'popular/Wedding_Organisers.jpg',
				'alt' => 'Party Organiser',
				'title' => 'Party Organiser',

			],
			[
				'url' => '/stage-decoratorss',
				'img' => config('app.website') . 'popular/stage-decoratorss.jpg',
				'alt' => 'Stage Decoration',
				'title' => 'Stage Decoration',

			],



		];


		$data['wedding_planning_for_bride'] = [

			[
				'url' => '/makeup-artists',
				'img' => config('app.website') . 'popular/Makeup_artist.jpg',
				'alt' => 'Makeup Artists',
				'title' => 'Makeup Artists',

			],
			[
				'url' => '/mehendi-artists',
				'img' => config('app.website') . 'popular/Mehendi-Artists.jpg',
				'alt' => 'Mehendi Artists',
				'title' => 'Mehendi Artists',

			],
			[
				'url' => '/bridal-wear',
				'img' => config('app.website') . 'popular/Bridal-Wear.jpg',
				'alt' => 'Bridal Wear',
				'title' => 'Bridal Wear',

			],
			[
				'url' => '/jewellery-designing',
				'img' => config('app.website') . 'popular/Jewellery.jpg',
				'alt' => 'Jewellery',
				'title' => 'Jewellery',

			],
			[
				'url' => '/salons',
				'img' => config('app.website') . 'popular/salon.jpg',
				'alt' => 'salons',
				'title' => 'salons',

			],
			[
				'url' => '/cosmetics',
				'img' => config('app.website') . 'popular/Cosmetic.jpg',
				'alt' => 'Cosmetics',
				'title' => 'Cosmetics',

			],



		];

		$data['wedding_planning_for_groom'] = [

			[
				'url' => '/wedding-suit-for-groom',
				'img' => config('app.website') . 'popular/wedding_suit_for_groom.jpg',
				'alt' => 'Wedding Suit groom',
				'title' => 'Wedding Suit groom',

			],
			[
				'url' => '/makeup-artist-for-groom',
				'img' => config('app.website') . 'popular/Makeup_artist_for_groom.jpg',
				'alt' => 'Makeup Artist',
				'title' => 'Makeup Artist',

			],
			[
				'url' => '/ghoda-baggi',
				'img' => config('app.website') . 'popular/Ghoda_Baggi.jpg',
				'alt' => 'ghoda baggi',
				'title' => 'ghoda baggi',

			],
			[
				'url' => '/hair-dressing',
				'img' => config('app.website') . 'popular/Hair_salons_for_groom.jpg',
				'alt' => 'Hair Salons',
				'title' => 'Hair Salons',

			],
			[
				'url' => '/wedding-band',
				'img' => config('app.website') . 'popular/Wedding_Band.jpg',
				'alt' => 'Wedding Band Baja',
				'title' => 'Wedding Band Baja',

			],
			[
				'url' => '/wedding-transport',
				'img' => config('app.website') . 'popular/Car_Decoration.jpg',
				'alt' => 'wedding transport',
				'title' => 'wedding transport',

			],



		];
		$data['pre-Wedding_planning'] = [

			[
				'url' => '/wedding-choreographer',
				'img' => config('app.website') . 'popular/wedding-choreographer.jpg',
				'alt' => 'Wedding choreographer',
				'title' => 'Wedding choreographer',

			],
			[
				'url' => '/wedding-astrologer',
				'img' => config('app.website') . 'popular/wedding-astrologer.jpg',
				'alt' => 'Wedding Astrologer',
				'title' => 'Wedding Astrologer',

			],
			[
				'url' => '/wedding-dancer-and-singer',
				'img' => config('app.website') . 'popular/wedding-dancer-and-singer.jpg',
				'alt' => 'Wedding Dancer And Singer',
				'title' => 'gWedding Dancer And Singer',

			],
			[
				'url' => '/pandits',
				'img' => config('app.website') . 'popular/Pandits.jpg',
				'alt' => 'Pandits',
				'title' => 'Pandits',

			],
			[
				'url' => '/honeymoon-packages',
				'img' => config('app.website') . 'popular/honeymoon-packages.jpg',
				'alt' => 'honeymoon packages',
				'title' => 'honeymoon packages',

			],
			[
				'url' => '/stage-show-organisers',
				'img' => config('app.website') . 'popular/stage-show-organisers.jpg',
				'alt' => 'Stage Show Organisers',
				'title' => 'Stage Show Organisers',

			],



		];


		if ($data) {
			$data['status'] = true;
			$data['code'] = 200;
			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;
			$data['code'] = 200;
			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'data' => $data,
		], 200);

	}

	/**
	 * @OA\Get(
	 *     path="/api/site/entranceExams",
	 *     tags={"Frontend Home Page Entrance Exams"},
	 *     summary="Entrance Exams records",
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
				'url' => '/categories/entrance-exams-coaching',
				'img' => config('app.website') . 'popular/air-force-navy.jpg',
				'alt' => 'coaching',
				'title' => 'Air Force & Navy / SSR / MR',

			],
			[
				'url' => '/ssc-cgl',
				'img' => config('app.website') . 'popular/SSC-CGL-JEE.jpg',
				'alt' => 'SSC CGL JEE',
				'title' => 'SSC CGL JEE',

			],
			[
				'url' => '/rrb-ntpc-coaching',
				'img' => config('app.website') . 'popular/NTPC-RRB-Railway.jpg',
				'alt' => 'NTPC & RRB Railway ',
				'title' => 'NTPC & RRB Railway ',

			],
			[
				'url' => '/cat-coaching',
				'img' => config('app.website') . 'popular/CAT-exam.jpg',
				'alt' => 'CAT/NEET',
				'title' => 'CAT/NEET',

			],
			[
				'url' => '/ctet-coaching',
				'img' => config('app.website') . 'popular/CTET-Super-TET.jpg',
				'alt' => 'CTET Super TET',
				'title' => 'CTET Super TET',

			],
			[
				'url' => '/categories/entrance-exams-coaching',
				'img' => config('app.website') . 'popular/UPSC-IAS.jpg',
				'alt' => 'UPSC & IAS',
				'title' => 'UPSC & IAS',

			]


		];

		if ($data) {
			$data['status'] = true;
			$data['code'] = 200;
			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;
			$data['code'] = 200;
			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'data' => $data,
		], 200);

	}

	/**
	 * @OA\Get(
	 *     path="/api/site/studyAbroad",
	 *     tags={"Frontend Home Page Study Abroad"},
	 *     summary="Study Abroad records",
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
						'url' => '/child/' . $study->child_slug,
						'img' => $img,
						'alt' => $study->child_category,
						'title' => $study->child_category,
					);

					$data['studyAbroad'] = $studyPageList;


				}
			}
		}
		if ($data) {
			$data['status'] = true;
			$data['code'] = 200;
			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;
			$data['code'] = 200;
			$data['message'] = "failed";
		}

		return response()->json([
			'success' => true,
			'data' => $data,
		], 200);

	}

	/**
	 * @OA\Get(
	 *     path="/api/site/getBlog",
	 *     tags={"Frontend Home Page Blog"},
	 *     summary="Blog records",
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
				'url' => '/blog/' . $blog->slug,
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
	 *     path="/api/site/blog/{slug}",
	 *     tags={"Frontend blog details"},
	 *     summary="Search records",
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
	public function getBlogDetails(Request $request, $slug)
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
					'url' => '/blog/' . $blog->slug,
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
				'url' => '/blog/' . $blogdetails->slug,
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
	 *     path="/api/site/getKeyword",
	 *     tags={"Frontend get Keyword"},
	 *     summary="Search records",
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
		$search_kw = $request->input('keyword');
		$city = '';
		$keywordDetails = DB::table('keyword')
			->join('parent_category', 'keyword.parent_category_id', '=', 'parent_category.id')
			->join('child_category', 'keyword.child_category_id', '=', 'child_category.id')
			->where('keyword', 'LIKE', '%' . ucwords(str_replace('-', ' ', $search_kw)) . '%')
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

				DB::raw('MAX(assigned_kwds.sold_on_position) as sold_on_position'),
				DB::raw('MAX(c.rating) as rating'),
				DB::raw('MAX(c.comment_count) as comment_count')
			)

			->where('keyword.keyword', 'LIKE', "%{$keywordName}%")

			->groupBy('clients.id')

			->orderByRaw("
        CASE MAX(assigned_kwds.sold_on_position)
            WHEN 'platinum' THEN 1
            WHEN 'diamond' THEN 2
            WHEN 'FreeListing' THEN 3
            ELSE 4
        END
    ")

			->get();


		$data['clientsList'] = $clientsList->map(function ($client) {

			$logoImage = 'client/images/default_pp_small.jpg';
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
				'city' => $client->city,
				'state' => $client->state,
				'area' => $client->area,
				'zone' => $client->zone,
				'address' => $client->address,
				'pincode' => $client->pincode,
				'country' => $client->country,
				'year_of_estb' => $client->year_of_estb,
				'landmark' => $client->landmark,


				'rating' => $client->rating,
				'avgRating' => $avgRating,
				'comment_count' => $client->comment_count,
				'keywords' => $assignedKwds ?? null,
			];
		});


		$servicesRelated = Keyword::where('child_category_id', $keywordDetails->child_category_id)
			->where('parent_category_id', $keywordDetails->parent_category_id)
			->select('keyword', 'icon')
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
				'url' => '/' . generate_slug($keyword->keyword),
				'img' => $img,
				'alt' => $alt,
				'title' => $keyword->keyword,
			];
		})->values()->toArray();



		$data['servicesRelated'] = $servicesRelatedList;

		$cities = City::where('popular', '1')->get();
		$cityList = array();
		if ($cities) {
			foreach ($cities as $ckey => $cvalue) {

				$cityList[$ckey] = array(
					'url' => '/' . strtolower($cvalue->city) . '/' . generate_slug($keywordDetails->keyword),
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
	 *     path="/api/site/categories",
	 *     tags={"Frontend get categories"},
	 *     summary="Search records",
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
			// ->where('parent_category.parent_slug', $slug)
			->where('parent_category.parent_slug', 'LIKE', '%' . $slug . '%')
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
					'url' => "/child/{$child->child_slug}",
					'img' => $image,
					'alt' => $alt,
					'title' => $child->child_name,
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
	 *     path="/api/site/child",
	 *     tags={"Frontend get child"},
	 *     summary="Search records",
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
				DB::raw('MAX(keyword.icon) as icon') // Aggregate icon to avoid GROUP BY issue
			)
			->where('child_category.child_slug', $slug)
			->groupBy('keyword.keyword')
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
					'url' => '/' . generate_slug($keyword->keyword),
					'img' => $image ?: '',
					'alt' => $alt ?: $keyword->keyword,
					'title' => $keyword->keyword,
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
	 *     path="/api/site/home-slider",
	 *     tags={"Frontend Home slider"},
	 *     summary="Home slider records",
	 *     description="Home slider records dynamically based on a child",       
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
	 *     path="/api/site/getCityList",
	 *     tags={"Frontend get City List"},
	 *     summary="Search Zone records by City ",
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

		// -------- ZONE + CITY + PINCODE SEARCH --------
		$zoneResults = collect();

		if (!empty($cid)) {

			$zoneResults = DB::table('zones')
				->join('citylists', 'citylists.id', '=', 'zones.city_id')
				->where(function ($q) use ($cid) {
					$q->where('zones.zone', 'LIKE', "%{$cid}%")
						->orWhere('citylists.city', 'LIKE', "%{$cid}%")
						->orWhere('zones.city_id', $cid)
						->orWhere('zones.pincode', 'LIKE', "%{$cid}%");
				})
				->select(
					'zones.id as zone_id',
					'zones.zone',
					'citylists.id as city_id',
					'citylists.city as cityName',
					'zones.pincode'
				)
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
				'id' => $zone->city_id,
				'city' => $zone->cityName,
				'cityDetails' => $cityDetails
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
	 *     path="/api/site/get-keyword-list",
	 *     tags={"Frontend get Keyword List"},
	 *     summary="Search Keyword records",
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

		// Base keyword query
		$locations = DB::table('keyword')
			->when(empty($keyword), function ($q) {
				$q->whereIn('keyword.id', [
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
				$q->where('keyword.keyword', 'LIKE', "%{$keyword}%");
			})
			->select('id', 'keyword')
			->get();

		// Merge client data when keyword exists
		if (!empty($keyword)) {

			$clientData = DB::table('clients')
				->where('business_name', 'LIKE', "%{$keyword}%")
				->selectRaw('NULL as id, business_name as keyword')
				->distinct()
				->limit(20)
				->get();

			$locations = $locations->merge($clientData);
		}

		// Transform collection
		$html = $locations->map(function ($item) {
			return [
				'id' => $item->id,
				'keyword' => $item->keyword,
			];
		})->values();


		// $keyword = trim($request->input('keyword'));


		// // Initialize query
		// $query = DB::table('keyword');
		// // Apply filters
		// if (empty($keyword)) {
		// 	$query->whereIn('keyword.id', ['288', '601', '1517', '159', '602', '1624', '166', '536', '1937', '1481', '570', '1665']);
		// } else {
		// 	$query->where(function ($q) use ($keyword) {
		// 		$q->where('keyword.keyword', 'LIKE', '%' . $keyword . '%');

		// 	});
		// }


		// // Get results
		// $locations = $query->get();

		// if (!empty($keyword)) {

		// 		$clientData = DB::table('clients')
		// 			->where('business_name', 'LIKE', "%{$keyword}%")
		// 			->select('business_name as keyword')
		// 			->distinct()
		// 			->limit(20)
		// 			->get();

		// 	 $locations = $locations->merge($clientData);
		// 	}
		// // Transform results
		// $html = [];
		// foreach ($locations as $index => $data) {
		// 	$html[$index] =
		// 		[
		// 			'keyword' => $data->keyword,
		// 			'id' => $data->id
		// 		];
		// }

		// Handle empty results
		if (empty($html)) {
			return response()->json([
				'success' => false,
				'message' => 'No keyword found.',
			], 404);
		}

		// Return JSON response
		return response()->json([
			'success' => true,
			'data' => $html,
		], 200);


	}


	/**
	 * @OA\Get(
	 *     path="/api/site/business-details/{slug}",
	 *     tags={"Frontend Search business details"},
	 *     summary="Search records",
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
			->orderByRaw("
        CASE assigned_kwds.sold_on_position
            WHEN 'platinum' THEN 1
            WHEN 'diamond' THEN 2
            WHEN 'FreeListing' THEN 3
            ELSE 4
        END
    ")
			->first();

		if (!empty($clientscheck)) {

			$logoImage = 'client/images/default_pp_small.jpg';
			$altLogo = "Business Logo";
			if (!empty($clientscheck->logo)) {
				$cicons = unserialize($clientscheck->logo);

				if (!empty($cicons)) {
					$logoImage = config('app.website') . $cicons['large']['src'];
					$altLogo = $cicons['large']['name'];
				}
			}
			$profile_pic = 'client/images/default_profile_pic.jpg';
			$altbanner = "";
			if (!empty($clientscheck->profile_pic)) {
				$banner = unserialize($clientscheck->profile_pic);

				if (!empty($banner)) {
					$profile_pic = config('app.website') . $banner['large']['src'];
					$altLogo = $clientscheck->business_name;
				}
			}

			$gallery = 'client/images/default_profile_pic.jpg';
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

				$relKeywords = Keyword::select('keyword')->where('child_category_id', $findKeywords->child_category_id)->pluck('keyword.keyword')
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
	 *     path="/api/site/footer-links",
	 *     tags={"Frontend Footer links"},
	 *     summary="Footer Links records",
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
				'url' => '/about-us',
				'title' => 'About Us',

			],
			[
				'url' => '/business-owners',
				'title' => 'Featured Listings',

			],
			[
				'url' => '/pricing',
				'img' => config('app.website') . 'popular/CAT-exam.jpg',
				'alt' => 'CAT/NEET',
				'title' => 'CAT/NEET',

			],
			[
				'url' => '/ctet-coaching',
				'title' => 'Premium Plans',

			],
			[
				'url' => '/careers',
				'title' => 'Careers',

			],
			[
				'url' => '/blog',
				'title' => 'Success Stories',
			],
			[
				'url' => '/blog',
				'title' => 'Blog',
			],
			[
				'url' => '/business-owners',
				'title' => 'Advertise on quickdials',
			],
			[
				'url' => '/privacy-policy',
				'title' => 'Privacy Policy',
			],
			[
				'url' => '/terms-conditions',
				'title' => 'Terms & Conditions',
			],
			[
				'url' => '/copyright-policy',
				'title' => 'Copyright Policy',
			]


		];

		$data['popularCategories'] = [
			[
				'url' => '/categories/professional-courses',
				'title' => 'Coaching & Tuitions',

			],
			[
				'url' => '/child/wedding-planning',
				'title' => 'Wedding Planning',

			],
			[
				'url' => '/category/health-wellness',
				'title' => 'Healthcare',

			],
			[
				'url' => '/category/real-estate-agent',
				'title' => 'Real Estate',

			],
			[
				'url' => '/categories/electric-services',
				'title' => 'Electric Services',

			],
			[
				'url' => '/categories/security-system',
				'title' => 'Security System',

			],
			[
				'url' => '/categories/medical',
				'title' => 'Medical',
			],
			[
				'url' => '/categories/packers-movers',
				'title' => 'Packers Movers',
			],
			[
				'url' => '/restaurants',
				'title' => 'Restaurants',
			],
			[
				'url' => '/hotels',
				'title' => 'Hotels',
			],
			[
				'url' => '/interior-designer',
				'title' => 'interior Design',
			]
		];
		$data['businessServicesLink'] = [
			[
				'url' => '/patient-care-service',
				'title' => 'Patient Care Service',

			],
			[
				'url' => '/home-appliance-repair-training',
				'title' => 'Home Appliances Repair',

			],
			[
				'url' => '/wedding-organisers',
				'title' => 'Wedding Organisers',

			],
			[
				'url' => '/ac-service',
				'title' => 'AC Services',

			],
			[
				'url' => '/security-guards-services',
				'title' => 'Security Guards',

			],
			[
				'url' => '/cleaning-services',
				'title' => 'Cleaning Services',

			],
			[
				'url' => '/categories/repairs-services',
				'title' => 'Repairs Services',
			],
			[
				'url' => '/categories/spa-beauty',
				'title' => 'SPA Beauty',
			],
			[
				'url' => '/child/loan',
				'title' => 'Loan',
			],
			[
				'url' => '/income-tax-consultants',
				'title' => 'Tax Consultants',
			],
			[
				'url' => '/interviews',
				'title' => 'Interviews Question',
			]
		];

		if ($data) {
			$data['status'] = true;
			$data['code'] = 200;
			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;
			$data['code'] = 200;
			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'data' => $data,
		], 200);

	}
	/**
	 * @OA\Get(
	 *     path="/api/site/about-us",
	 *     tags={"Frontend about-us"},
	 *     summary="about-us records",
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
			$data['code'] = 200;
			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;
			$data['code'] = 200;
			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'data' => $data,
		], 200);

	}


	/**
	 * @OA\Get(
	 *     path="/api/site/faq",
	 *     tags={"Frontend FAQ's"},
	 *     summary="FAQ's records",
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
				'a8' => 'For More Info & any Queries, you can Contact Us on +91 70113 10265 or reach out to us via e-mail @ info@quickdials.com, or list your business as free listing, our marketing team Will Contact you Soon.',
			],
		];
		if ($data) {
			$data['status'] = true;
			$data['code'] = 200;
			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;
			$data['code'] = 200;
			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'data' => $data,
		], 200);

	}
	/**
	 * @OA\Get(
	 *     path="/api/site/business-owners",
	 *     tags={"Frontend business signup"},
	 *     summary="Business sign up",
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
		$data['businessOwners'] = [
			[
				'Grow Client' => '1453 +',
				'Suppliers' => '8.1 K+',
				'Products & Services' => '8.1 K+',
				'Keyword' => '1878 +',
				'Store' => '21 +',
				'Platform' => '11.3 K+',
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
				'a7' => 'For More Info & any Queries, you can Contact Us on +91 70113 10265 or reach out to us via e-mail @ info@quickdials.com, or list your business as free listing, our marketing team Will Contact you Soon.',
			],
		];
		if ($data) {
			$data['status'] = true;
			$data['code'] = 200;
			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;
			$data['code'] = 200;
			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'data' => $data,
		], 200);

	}


	/**
	 * @OA\Get(
	 *     path="/api/site/common-linked",
	 *     tags={"Frontend Common Linked"},
	 *     summary="Common Linked records",
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
				'url' => '/coaching-tuitions',
				'title' => 'Coaching & Tuitions',
			],
			[
				'url' => '/business-services',
				'title' => 'Business Services',
			],
			[
				'url' => '/home-construction',
				'title' => 'Home Construction & Renovation',

			],
			[
				'url' => '/categories/personal-finance-services',
				'title' => 'Personal Finance Services',

			],
			[
				'url' => '/categories/tours-travel-services',
				'title' => 'Tours & Travels',

			],
			[
				'url' => '/home-construction/property-dealer',
				'title' => 'Property Dealer',

			],
			[
				'url' => '/rentals',
				'title' => 'Rental Property',
			],
			[
				'url' => '/pg-hostels',
				'title' => 'PG & Hostel',
			],
			[
				'url' => '/categories/computer-courses',
				'title' => 'Computer Courses & Training',
			],
			[
				'url' => '/study-abroad',
				'title' => 'Study Abroad',
			],
			[
				'url' => '/home-services',
				'title' => 'Home Services',
			],

			[
				'url' => '/wedding-organizers',
				'title' => 'Parties, Special Occasions & Wedding',
			],
			[
				'url' => '/categories/electric-services',
				'title' => 'Electric Services',
			],
			[
				'url' => '/categories/entrance-exams-coaching',
				'title' => 'Government Exam',
			],
			[
				'url' => '/web-designers',
				'title' => 'Web Designers',
			],
			[
				'url' => '/medical',
				'title' => 'Medical',
			],
			[
				'url' => '/carpenters',
				'title' => 'Carpenters',
			],
			[
				'url' => '/child/yoga-classes',
				'title' => 'Yoga',
			],
			[
				'url' => '/tax-consultants',
				'title' => 'CA & TAX Consultants',
			],
		];
		$data['businessService'] = [
			[
				'url' => '/patient-care-services',
				'title' => 'Patient Care Service',
			],
			[
				'url' => '/home-appliances-repair-services',
				'title' => 'Home Appliances Repair & Services',
			],
			[
				'url' => '/packers-movers',
				'title' => 'Packers and Movers',

			],
			[
				'url' => '/ac-repair-services',
				'title' => 'AC Services',

			],
			[
				'url' => '/cleaning-services',
				'title' => 'Cleaning Services',

			],
			[
				'url' => '/security-guards-services',
				'title' => 'Security Guards',

			],
			[
				'url' => '/architects',
				'title' => 'Architects',
			],
			[
				'url' => '/building-consultants-contractors',
				'title' => 'Builders & Contractors',
			],
			[
				'url' => '/interior-designers-decorators',
				'title' => 'Interior Designers & Decorators',
			],
			[
				'url' => '/housekeeping-services',
				'title' => 'Housekeeping Services',
			],
			[
				'url' => '/painting-contractors',
				'title' => 'Painting Contractors',
			],

			[
				'url' => '/modular-kitchen-dealers',
				'title' => 'Modular Kitchen Dealers',
			],
			[
				'url' => '/waterproofing-contractors',
				'title' => 'Waterproofing Contractors',
			]

		];
		$data['educationTraining'] = [
			[
				'url' => '/schools-colleges',
				'title' => 'Schools & Colleges',
			],
			[
				'url' => '/categories/entrance-exams-coaching',
				'title' => 'Entrance Exam Coaching',
			],
			[
				'url' => '/competitive-exam-coaching',
				'title' => 'Competitive Exam Coaching',

			],
			[
				'url' => '/distance-education',
				'title' => 'Distance Education',

			],
			[
				'url' => '/language-training',
				'title' => 'Language Training',

			],
			[
				'url' => '/overseas-education-consultants',
				'title' => 'Overseas Education',

			],
			[
				'url' => '/college-tuition',
				'title' => 'College & University Tuitions',
			],
			[
				'url' => '/bank-insurance-exam-coaching',
				'title' => 'Bank & Insurance Exam Coaching',
			],
			[
				'url' => '/placement-consultants',
				'title' => 'Placement Consultants',
			]
		];
		$data['personalService'] = [
			[
				'url' => '/loan',
				'title' => 'Loans',
			],
			[
				'url' => '/visa-consultants',
				'title' => 'Visa Consultants',
			],
			[
				'url' => '/beauty-parlour-services',
				'title' => 'Beauty Parlour Services',

			],
			[
				'url' => '/event-organisers',
				'title' => 'Event Organisers',

			],
			[
				'url' => '/catering-services',
				'title' => 'Catering Services',

			],
			[
				'url' => '/photographers-videographers',
				'title' => 'Photographers & Videographers',

			],
			[
				'url' => '/astrologers',
				'title' => 'Astrologers',
			],
			[
				'url' => '/vehicle-rental',
				'title' => 'Vehicle Rentals',
			],
			[
				'url' => '/massage-centres',
				'title' => 'Massage Centres',
			],
			[
				'url' => '/advocates-lawyers',
				'title' => 'Advocates & Lawyers',
			],
		];

		$cities = City::where('popular', '1')->get();
		$cityList = array();
		if ($cities) {
			foreach ($cities as $ckey => $cvalue) {

				$cityList[$ckey] = array(
					'url' => '/' . strtolower($cvalue->city),
					'city' => $cvalue->city,

				);

			}
		}
		$data['citiesofIndia'] = $cityList;

		if ($data) {
			$data['status'] = true;
			$data['code'] = 200;
			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;
			$data['code'] = 200;
			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'data' => $data,
		], 200);

	}

	/**
	 * @OA\Get(
	 *     path="/api/site/business-services",
	 *     tags={"Frontend business services"},
	 *     summary="Business Services records",
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
			$data['code'] = 200;
			$data['message'] = "Successfully";
		} else {
			$data['status'] = false;
			$data['code'] = 200;
			$data['message'] = "failed";
		}
		return response()->json([
			'success' => true,
			'data' => $data,
		], 200);

	}



	/**
	 * @OA\Post(
	 *     path="/api/site/{client_id}/saveReview",
	 *     tags={"Frontend Reviews"},
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
