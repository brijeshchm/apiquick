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
use App\Keyword;
use App\Models\Client\AssignedKWDS;
use App\Models\Client;
use App\Models\Citieslists;
use App\Models\Blogdetails;
use App\Models\ChildCategory;
use Session;
use App\Models\ParentCategory;
use App\Models\Client\Comment;

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


	public function getSearch(Request $request)
	{
		$search_kw = $request->input('keyword');
		$city = $request->input('city');


		$keywordDetails = DB::table('keyword')
			->join('parent_category', 'keyword.parent_category_id', '=', 'parent_category.id')
			->join('child_category', 'keyword.child_category_id', '=', 'child_category.id')
			->where('keyword', 'LIKE', '%' . ucwords(str_replace('-', ' ', $search_kw)) . '%')
			->select('keyword.*', 'parent_category.*', 'child_category.*', 'keyword.id as key_id', 'keyword.faqq1', 'keyword.faqa1', 'keyword.faqq2', 'keyword.faqa2', 'keyword.faqq3', 'keyword.faqa3', 'keyword.faqq4', 'keyword.faqa4', 'keyword.faqq5', 'keyword.faqa5', 'keyword.meta_title', 'keyword.meta_description', 'keyword.meta_keywords', 'keyword.top_description', 'keyword.bottom_description', 'keyword.ratingvalue', 'keyword.ratingcount')
			->first();


		$category_banner = "";
		$alt = "";

		if (!empty($keywordDetails->category_banner)) {
			$cicons = unserialize($keywordDetails->category_banner);

			if (!empty($cicons)) {
				$category_banner = config('app.website') . $cicons['category_banner']['src'];
				$alt = $cicons['category_banner']['name'];
			}
		}

		$data['keyword'] = array(
			'keyword' => $keywordDetails->keyword,
			'keyword_slug' => generate_slug($keywordDetails->keyword),
			'category_banner' => $category_banner,
			'alt' => $alt,
			'meta_title' => $keywordDetails->meta_title,
			'meta_keywords' => $keywordDetails->meta_keywords,
			'meta_description' => $keywordDetails->meta_description,
			'top_description' => $keywordDetails->top_description,
			'bottom_description' => $keywordDetails->bottom_description,
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


		$cityName = ucwords(str_replace('-', ' ', $city));
		$keywordName = ucwords(str_replace('-', ' ', $search_kw));

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
		//  dd($clientsList);
		$data['clientsList'] = $clientsList->map(function ($client) {

			$logoImage = 'client/images/default_pp_small.jpg';
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
 
			return [
				'business_id' => $client->id,
				'business_name' => $client->business_name,
				'business_slug' => $client->business_slug,
				'logo' => $logoImage ?? '',
				'altLogo' => $altLogo ?? '',			 
				'gallery' => $galleryArray ?? '',
				'business_intro' => $client->business_intro,
				'certifications' => $client->certifications,
				'sirName' => $client->sirName,
				'first_name' => $client->first_name,
				'middle_name' => $client->middle_name,
				'last_name' => $client->last_name,
				'email' => $client->email,
				'certified_status' => $client->certified_status,
				'website' => $client->website,
				'city' => $client->city,
				'business_state' => $client->business_state,
				'area' => $client->area,
				'business_city' => $client->business_city,
				'address' => $client->address,
				'pincode' => $client->pincode,
				'country' => $client->country,
				'year_of_estb' => $client->year_of_estb,
				'time' => $client->time,
				'landmark' => $client->landmark,
				'profile_pic' => $client->profile_pic,
				'pictures' => $client->pictures,
				'rating' => $client->rating,
				'comment_count' => $client->comment_count,
			];
		});
		// Return JSON response
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
	 *     path="/api/site/popularSearches",
	 *     tags={"Frontend Home Page popularSearches"},
	 *     summary="Popular Searches records",
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
	public function popularSearches(Request $request)
	{
		$url = config('app.url');
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
				'url' => '/banquet-halls',
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
	 *     path="/api/site/getBlog-homepage",
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
	public function getBlogHomepage(Request $request)
	{
		$url = config('app.url');
		$blogPageList = [];
		$data = [];
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

				$data['blogList'] = $blogPageList;



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
		$city = $request->input('city');

		$keyword = DB::table('keyword')
			->join('parent_category', 'keyword.parent_category_id', '=', 'parent_category.id')
			->join('child_category', 'keyword.child_category_id', '=', 'child_category.id')
			->where('keyword', 'LIKE', '%' . ucwords(str_replace('-', ' ', $search_kw)) . '%')
			->select('keyword.*', 'parent_category.*', 'child_category.*', 'keyword.id as key_id', 'keyword.faqq1', 'keyword.faqa1', 'keyword.faqq2', 'keyword.faqa2', 'keyword.faqq3', 'keyword.faqa3', 'keyword.faqq4', 'keyword.faqa4', 'keyword.faqq5', 'keyword.faqa5', 'keyword.meta_title', 'keyword.meta_description', 'keyword.meta_keywords', 'keyword.top_description', 'keyword.bottom_description', 'keyword.ratingvalue', 'keyword.ratingcount')
			->first();

		$data['keyword'] = $keyword;

		$clientsList = DB::table('clients')
			->join('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id')
			->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
			->join('citylists', 'assigned_kwds.city_id', '=', 'citylists.id')
			->leftJoin(DB::raw('(SELECT SUM(rating) AS rating,comment_client_ID,COUNT(comment_ID) AS comment_count FROM comments GROUP BY comment_client_ID) c'), 'c.comment_client_ID', '=', 'clients.id')
			->select('clients.*', 'citylists.city', 'assigned_kwds.sold_on_position', 'c.rating', 'c.comment_count')

			//->where('clients.active_status','1')	 
			->where('keyword.keyword', 'LIKE', '%' . ucwords(str_replace('-', ' ', $search_kw)) . '%')

			->orderby(DB::raw('(CASE `assigned_kwds`.`sold_on_position` WHEN \'platinum\' THEN 1 WHEN \'diamond\' THEN 2 WHEN \'FreeListing\' THEN 3 END)'), 'asc')
			//	->orderby(DB::raw('(CASE `assigned_kwds`.`sold_on_position` WHEN \'platinum\' THEN 1 WHEN \'diamond\' THEN 2 END)'),'asc')
			//->orderby(DB::raw('(CASE `assigned_kwds`.`sold_on_position` WHEN \'premium\' THEN 1 WHEN \'platinum\' THEN 2 WHEN \'royal\' THEN 3 WHEN \'preferred\' THEN 4 END)'),'asc')
			// ->groupBy('client_id')
			//->orderby(DB::raw('(CASE `clients`.`certified_status` WHEN \'1\' THEN 1 END)'),'DESC')		
			->get();

		$data['clientsList'] = $clientsList;

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
						$alt = $cicons['pc_icon']['name'];
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


		$image = "";
		$alt = "";

		if (!empty($categoryDetails->category_banner)) {
			$cicons = unserialize($categoryDetails->category_banner);

			if (!empty($cicons)) {
				$image = config('app.website') . $cicons['category_banner']['src'];
				$alt = $cicons['category_banner']['name'];
			}
		}
		$data['keyword'] = array(
			'parent_category' => $categoryDetails->parent_category,
			'parent_slug' => $categoryDetails->parent_slug,
			'category_banner' => $image,
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

					$cicons = @unserialize($keyword->icon);
					if ($cicons !== false && !empty($cicons['pc_icon']['src']) && !empty($cicons['pc_icon']['name'])) {
						$image = config('app.website') . $cicons['pc_icon']['src'];
						$alt = $cicons['pc_icon']['name'];
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

		$image = "";
		$alt = "";

		if (!empty($childDetails->category_banner)) {
			$cicons = unserialize($childDetails->child_banner);

			if (!empty($cicons)) {
				$image = config('app.website') . $cicons['child_banner']['src'];
				$alt = $cicons['child_banner']['name'];
			}
		}
		$data['keyword'] = array(
			'child_category' => $childDetails->child_category,
			'child_slug' => $childDetails->child_slug,
			'category_banner' => $image,
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
	 *     path="/api/site/getCityList",
	 *     tags={"Frontend get get City List"},
	 *     summary="Search records",
	 *     description="Search records dynamically based on a city",
	 *            
	 *     @OA\Parameter(
	 *         name="city",
	 *         in="query",
	 *         required=true,
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
	public function getCityList(Request $request)
	{

		$city = $request->input('city');
		if (!is_null($city) && !is_string($city)) {
			return response()->json([
				'success' => false,
				'message' => 'Invalid city input.',
			], 400);
		}

		// Initialize query
		$query = DB::table('citylists')
			->select(
				'citylists.city',
				'zones.zone',
				'areas.area'
			)
			->leftJoin('zones', 'citylists.id', '=', 'zones.city_id')
			->leftJoin('areas', 'zones.id', '=', 'areas.zone_id');

		// Apply filters
		if (is_null($city)) {
			$query->whereIn('citylists.id', ['278', '596', '961', '428']);
		} else {
			$query->where(function ($q) use ($city) {
				$q->where('citylists.city', 'LIKE', '%' . $city . '%')
					->orWhere('zones.zone', 'LIKE', '%' . $city . '%')
					->orWhere('areas.area', 'LIKE', '%' . $city . '%');
			});
		}

		// Get results
		$locations = $query->get();

		// Transform results
		$html = [];
		foreach ($locations as $index => $data) {
			$entry = ['city' => strtolower($data->city)];
			if (!is_null($data->zone)) {
				$entry['zone'] = strtolower($data->zone);
			}
			if (!is_null($data->area)) {
				$entry['area'] = strtolower($data->area);
			}
			$html[$index] = $entry;
		}

		// Handle empty results
		if (empty($html)) {
			return response()->json([
				'success' => false,
				'message' => 'No locations found.',
			], 404);
		}

		// Return JSON response
		return response()->json([
			'success' => true,
			'data' => array_values($html),
		], 200);
	}


	/**
	 * @OA\Get(
	 *     path="/api/site/business-details",
	 *     tags={"Frontend Search city and keyword"},
	 *     summary="Search records",
	 *     description="Search records dynamically based on a keyword or filters",
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
		$business_slug = $request->input('business_slug');
	 


		$keywordDetails = DB::table('keyword')
			->join('parent_category', 'keyword.parent_category_id', '=', 'parent_category.id')
			->join('child_category', 'keyword.child_category_id', '=', 'child_category.id')
			->where('keyword', 'LIKE', '%' . ucwords(str_replace('-', ' ', $business_slug)) . '%')
			->select('keyword.*', 'parent_category.*', 'child_category.*', 'keyword.id as key_id', 'keyword.faqq1', 'keyword.faqa1', 'keyword.faqq2', 'keyword.faqa2', 'keyword.faqq3', 'keyword.faqa3', 'keyword.faqq4', 'keyword.faqa4', 'keyword.faqq5', 'keyword.faqa5', 'keyword.meta_title', 'keyword.meta_description', 'keyword.meta_keywords', 'keyword.top_description', 'keyword.bottom_description', 'keyword.ratingvalue', 'keyword.ratingcount')
			->first();


		$category_banner = "";
		$alt = "";

		if (!empty($keywordDetails->category_banner)) {
			$cicons = unserialize($keywordDetails->category_banner);

			if (!empty($cicons)) {
				$category_banner = config('app.website') . $cicons['category_banner']['src'];
				$alt = $cicons['category_banner']['name'];
			}
		}

		$data['keyword'] = array(
			'keyword' => $keywordDetails->keyword,
			'keyword_slug' => generate_slug($keywordDetails->keyword),
			'category_banner' => $category_banner,
			'alt' => $alt,
			'meta_title' => $keywordDetails->meta_title,
			'meta_keywords' => $keywordDetails->meta_keywords,
			'meta_description' => $keywordDetails->meta_description,
			'top_description' => $keywordDetails->top_description,
			'bottom_description' => $keywordDetails->bottom_description,
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


		$cityName = ucwords(str_replace('-', ' ', $city));
		$keywordName = ucwords(str_replace('-', ' ', $search_kw));

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
		//  dd($clientsList);
		$data['clientsList'] = $clientsList->map(function ($client) {

			$logoImage = 'client/images/default_pp_small.jpg';
			$altLogo = "Business Logo";
			if (!empty($client->logo)) {
				$cicons = unserialize($client->logo);

				if (!empty($cicons)) {
					$logoImage = config('app.website') . $cicons['large']['src'];
					$altLogo = $cicons['large']['name'];
				}
			}
			$profile_pic = 'client/images/default_profile_pic.jpg';
			$altbanner = "";
			if (!empty($client->profile_pic)) {
				$banner = unserialize($client->profile_pic);

				if (!empty($banner)) {
					$profile_pic = config('app.website') . $banner['large']['src'];
					$altLogo = $banner['large']['name'];
				}
			}

			$gallery = 'client/images/default_profile_pic.jpg';
			$altbanner = "";
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


			$assignedKeywords = DB::table('assigned_kwds')
				->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
				->where('assigned_kwds.client_id', '1748')
				->pluck('keyword.keyword')
				->toArray();
			$assignedCity = DB::table('assigned_kwds')
			->join('citylists', 'assigned_kwds.city_id', '=', 'citylists.id')
			->where('assigned_kwds.client_id', 1748)
			->pluck('citylists.city')
			->toArray();
 
			return [
				'business_id' => $client->id,
				'business_name' => $client->business_name,
				'business_slug' => $client->business_slug,
				'logo' => $logoImage ?? '',
				'altLogo' => $altLogo ?? '',
				'profile_banner' => $profile_pic ?? '',
				'altbanner' => $altbanner ?? '',
				'gallery' => $galleryArray ?? '',
				'business_intro' => $client->business_intro,
				'assign_keyword' => $assignedKeywords,
				'service_city' => $assignedCity,

				'certifications' => $client->certifications,
				'sirName' => $client->sirName,
				'first_name' => $client->first_name,
				'middle_name' => $client->middle_name,
				'last_name' => $client->last_name,
				'email' => $client->email,
				'certified_status' => $client->certified_status,
				'website' => $client->website,
				'city' => $client->city,
				'business_state' => $client->business_state,
				'area' => $client->area,
				'business_city' => $client->business_city,
				'address' => $client->address,
				'pincode' => $client->pincode,
				'country' => $client->country,
				'year_of_estb' => $client->year_of_estb,
				'time' => $client->time,
				'landmark' => $client->landmark,
				'profile_pic' => $client->profile_pic,
				'pictures' => $client->pictures,
				'rating' => $client->rating,
				'comment_count' => $client->comment_count,
			];
		});

		dd($data['clientsList']);
		return response()->json([
			'success' => true,
			'data' => $data,
		], 200);
	 
	}



}
