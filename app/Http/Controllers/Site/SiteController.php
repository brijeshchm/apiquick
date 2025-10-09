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
use Session;
use App\Models\ParentCategory;
use App\Models\Client\Comment;

class SiteController extends Controller
{

	/**
	 * @OA\Get(
	 *     path="/api/site/city/keyword",
	 *     tags={"Frontend API"},
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


		$keyword = DB::table('keyword')
			->join('parent_category', 'keyword.parent_category_id', '=', 'parent_category.id')
			->join('child_category', 'keyword.child_category_id', '=', 'child_category.id')
			->where('keyword', 'LIKE', ucwords(str_replace("-", " ", $search_kw)))
			->select('keyword.*', 'parent_category.*', 'child_category.*', 'keyword.id as key_id', 'keyword.faqq1', 'keyword.faqa1', 'keyword.faqq2', 'keyword.faqa2', 'keyword.faqq3', 'keyword.faqa3', 'keyword.faqq4', 'keyword.faqa4', 'keyword.faqq5', 'keyword.faqa5', 'keyword.meta_title', 'keyword.meta_description', 'keyword.meta_keywords', 'keyword.top_description', 'keyword.bottom_description', 'keyword.ratingvalue', 'keyword.ratingcount')
			->first();
		// dd($keyword);
		$data['keyword'] = $keyword;

		$clientscheck = DB::table('clients')
			->join('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id')
			->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
			->join('citylists', 'assigned_kwds.city_id', '=', 'citylists.id')
			->leftJoin(DB::raw('(SELECT SUM(rating) AS rating, comment_client_ID, COUNT(comment_ID) AS comment_count FROM comments GROUP BY comment_client_ID) c'), 'c.comment_client_ID', '=', 'clients.id')
			->select('clients.*', 'citylists.city', 'assigned_kwds.sold_on_position', 'c.rating', 'c.comment_count')
			->where('citylists.city', 'LIKE', ucwords($city))
			->where('clients.active_status', '1')
			->where('keyword.keyword', 'LIKE', ucwords(str_replace('-', ' ', $search_kw)))
			->orderByRaw("
        CASE assigned_kwds.sold_on_position
            WHEN 'platinum' THEN 1
            WHEN 'diamond' THEN 2
            WHEN 'FreeListing' THEN 3
            ELSE 4
        END
    ")
			// ->groupBy(
			//     'clients.id'

			// )
			->get();




		if (!empty($clientscheck->count())) {

			$clientsList = $clientscheck;

		} else {

			$clientsList = DB::table('clients')
				->join('assigned_kwds', 'clients.id', '=', 'assigned_kwds.client_id')
				->join('keyword', 'assigned_kwds.kw_id', '=', 'keyword.id')
				->join('citylists', 'assigned_kwds.city_id', '=', 'citylists.id')
				->leftJoin(DB::raw('(SELECT SUM(rating) AS rating,comment_client_ID,COUNT(comment_ID) AS comment_count FROM comments GROUP BY comment_client_ID) c'), 'c.comment_client_ID', '=', 'clients.id')
				->select('clients.*', 'citylists.city', 'assigned_kwds.sold_on_position', 'c.rating', 'c.comment_count')

				//->where('clients.active_status','1')
				->where('keyword.keyword', 'LIKE', ucwords(str_replace("-", " ", $search_kw)))

				->orderby(DB::raw('(CASE `assigned_kwds`.`sold_on_position` WHEN \'platinum\' THEN 1 WHEN \'diamond\' THEN 2 WHEN \'FreeListing\' THEN 3 END)'), 'asc')
				//	->orderby(DB::raw('(CASE `assigned_kwds`.`sold_on_position` WHEN \'platinum\' THEN 1 WHEN \'diamond\' THEN 2 END)'),'asc')
				//->orderby(DB::raw('(CASE `assigned_kwds`.`sold_on_position` WHEN \'premium\' THEN 1 WHEN \'platinum\' THEN 2 WHEN \'royal\' THEN 3 WHEN \'preferred\' THEN 4 END)'),'asc')
				// ->groupBy('client_id')
				//->orderby(DB::raw('(CASE `clients`.`certified_status` WHEN \'1\' THEN 1 END)'),'DESC')		
				->get();
		}


		$data['clientsList'] = $clientsList;
		echo json_encode($data);


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
				'url' => $url . 'categories/professional-courses',
				'img' => $url . 'img/IT-Training.png',
				'alt' => 'computer courses',
				'title' => 'computer courses',

			],
			[
				'url' => $url . 'child/wedding-planning',
				'img' => $url . 'img/wedding.png',
				'alt' => 'Wedding Planning',
				'title' => 'Wedding Planning',

			],
			[
				'url' => $url . 'categories/electric-services',
				'img' => $url . 'img/electric-services.png',
				'alt' => 'Electric Services',
				'title' => 'Electric Services',

			],
			[
				'url' => $url . 'categories/entrance-exams-coaching',
				'img' => $url . 'img/government-exam.png',
				'alt' => 'Government exam',
				'title' => 'Government exam',

			],
			[
				'url' => $url . 'categories/study-abroad',
				'img' => $url . 'img/study-abroad.png',
				'alt' => 'Study Abroad',
				'title' => 'Study Abroad',

			],
			[
				'url' => $url . 'categories/spa-beauty',
				'img' => $url . 'img/Spa & Beauty.png',
				'alt' => 'Spa & Beauty',
				'title' => 'Spa & Beauty',

			],
			[
				'url' => $url . 'categories/repairs-services',
				'img' => $url . 'img/Repairs-Services.png',
				'alt' => 'Repair Services',
				'title' => 'Repair Services',

			],
			[
				'url' => $url . 'categories/packers-movers',
				'img' => $url . 'img/Packers-movers.png',
				'alt' => 'Packers & Movers',
				'title' => 'Packers & Movers',

			],
			[
				'url' => $url . 'categories/professional',
				'img' => $url . 'img/Professional.png',
				'alt' => 'Professional',
				'title' => 'Professional',

			],
			[
				'url' => $url . 'categories/contractors',
				'img' => $url . 'img/contractors.png',
				'alt' => 'Contractors',
				'title' => 'Contractors',

			],
			[
				'url' => $url . 'categories/distance-education',
				'img' => $url . 'img/Education.png',
				'alt' => 'Education',
				'title' => 'Education',

			],
			[
				'url' => $url . 'categories/rent-buy',
				'img' => $url . 'img/Rent-buy.png',
				'alt' => 'Rent & Buy',
				'title' => 'Rent & Buy',
			],
			[
				'url' => $url . 'child/sports-academy',
				'img' => $url . 'img/sports.png',
				'alt' => 'Sport Academy',
				'title' => 'Sport Academy',
			],
			[
				'url' => $url . 'categories/medical',
				'img' => $url . 'img/Medical.png',
				'alt' => 'Medical',
				'title' => 'Medical',
			],
			[
				'url' => $url . 'categories/loan',
				'img' => $url . 'img/Loan.png',
				'alt' => 'Loan',
				'title' => 'Loan',
			],
			[
				'url' => $url . 'categories/dancing',
				'img' => $url . 'img/Dancing.png',
				'alt' => 'Dancing',
				'title' => 'Dancing',
			],
			[
				'url' => $url . 'categories/yoga',
				'img' => $url . 'img/Yoga.png',
				'alt' => 'Yoga',
				'title' => 'Yoga',
			],
			[
				'url' => $url . 'categories/security-system',
				'img' => $url . 'img/CCTV-security.png',
				'alt' => 'CCTV Security',
				'title' => 'CCTV Security',
			],
			[
				'url' => $url . 'categories/web-technologies',
				'img' => $url . 'images/Web-Designers.png',
				'alt' => 'Web Designers',
				'title' => 'Web Designers',
			],
			[
				'url' => $url . 'tours-and-travels',
				'img' => $url . 'images/tour-travels.png',
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
		echo json_encode($data);



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
				'url' => $url . 'categories/computer-courses',
				'img' => $url . 'popular/IT-Training.jpg',
				'alt' => 'computer courses',
				'title' => 'computer courses',

			],
			[
				'url' => $url . 'categories/entrance-exams-coaching',
				'img' => $url . 'popular/Entrance-Exam.jpg',
				'alt' => 'Entrance exam',
				'title' => 'Entrance exam',

			],
			[
				'url' => $url . 'categories/packers-movers',
				'img' => $url . 'popular/Packers-Movers.jpg',
				'alt' => 'Packers & Movers',
				'title' => 'Packers & Movers',

			],
			[
				'url' => $url . 'categories/interior-designer',
				'img' => $url . 'popular/Interior-design.jpg',
				'alt' => 'Interior Design',
				'title' => 'GInterior Design',

			],
			[
				'url' => $url . 'real-estate-agent',
				'img' => $url . 'popular/real-estate-agent.jpg',
				'alt' => 'Real Estate Agents',
				'title' => 'Real Estate Agents',

			],
			[
				'url' => $url . 'carpenters',
				'img' => $url . 'popular/carpenter.jpg',
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
		echo json_encode($data);


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
				'url' => $url . 'ac-service',
				'img' => $url . 'popular/AC-Service.jpg',
				'alt' => 'AC Service',
				'title' => 'AC Service',

			],
			[
				'url' => $url . 'car-service',
				'img' => $url . 'popular/car-services.jpg',
				'alt' => 'Car Services',
				'title' => 'Car Services',

			],
			[
				'url' => $url . 'laundry-service',
				'img' => $url . 'popular/washing-machines.jpg',
				'alt' => 'Laundry Services',
				'title' => 'Laundry Services',

			],
			[
				'url' => $url . 'electricity-service',
				'img' => $url . 'popular/Electricity-Services.jpg',
				'alt' => 'Electrician Services',
				'title' => 'Electrician Services',

			],
			[
				'url' => $url . 'hotels',
				'img' => $url . 'popular/Hotel-Services.jpg',
				'alt' => 'Hotels',
				'title' => 'Hotels',

			],
			[
				'url' => $url . 'categories/clinical-research-training',
				'img' => $url . 'popular/Fitness-Services.jpg',
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
		echo json_encode($data);
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
				'url' => $url . 'catering-services',
				'img' => $url . 'popular/Catering-Services.jpg',
				'alt' => 'Catering Services',
				'title' => 'Catering Services',

			],
			[
				'url' => $url . 'banquet-halls',
				'img' => $url . 'popular/Banquet-Halls.jpg',
				'alt' => 'Banquet Halls',
				'title' => 'Banquet Halls',

			],
			[
				'url' => $url . 'stage-decorators',
				'img' => $url . 'popular/Stage-Decorators.jpg',
				'alt' => 'Stage Decorators',
				'title' => 'Stage Decorators',

			],
			[
				'url' => $url . 'makeup-artists',
				'img' => $url . 'popular/makeup-artists.jpg',
				'alt' => 'Makeup Artists',
				'title' => 'Makeup Artists',

			],
			[
				'url' => $url . 'mehendi-artists',
				'img' => $url . 'popular/Mehendi-Artists.jpg',
				'alt' => 'Mehendi Artists',
				'title' => 'Mehendi Artists',

			],
			[
				'url' => $url . 'bridal-wear',
				'img' => $url . 'popular/Bridal-Wear.jpg',
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
		echo json_encode($data);
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
				'url' => $url . 'categories/entrance-exams-coaching',
				'img' => $url . 'popular/air-force-navy.jpg',
				'alt' => 'coaching',
				'title' => 'Air Force & Navy / SSR / MR',

			],
			[
				'url' => $url . 'ssc-cgl',
				'img' => $url . 'popular/SSC-CGL-JEE.jpg',
				'alt' => 'SSC CGL JEE',
				'title' => 'SSC CGL JEE',

			],
			[
				'url' => $url . 'rrb-ntpc-coaching',
				'img' => $url . 'popular/NTPC-RRB-Railway.jpg',
				'alt' => 'NTPC & RRB Railway ',
				'title' => 'NTPC & RRB Railway ',

			],
			[
				'url' => $url . 'cat-coaching',
				'img' => $url . 'popular/CAT-exam.jpg',
				'alt' => 'CAT/NEET',
				'title' => 'CAT/NEET',

			],
			[
				'url' => $url . 'ctet-coaching',
				'img' => $url . 'popular/CTET-Super-TET.jpg',
				'alt' => 'CTET Super TET',
				'title' => 'CTET Super TET',

			],
			[
				'url' => $url . 'categories/entrance-exams-coaching',
				'img' => $url . 'popular/UPSC-IAS.jpg',
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
		echo json_encode($data);
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
						$img = $abicons['pc_icon']['src'];
					} else {
						$img = $url . 'images/it-training.png';
					}



					$studyPageList[$key] = array(
						'url' => $url . '/child/' . $study->child_slug,
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

		echo json_encode($data);
	}


	/**
	 * @OA\Get(
	 *     path="/api/site/getKeyword",
	 *     tags={"Frontend API"},
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
			->where('keyword', 'LIKE', ucwords(str_replace("-", " ", $search_kw)))
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
			->where('keyword.keyword', 'LIKE', ucwords(str_replace("-", " ", $search_kw)))

			->orderby(DB::raw('(CASE `assigned_kwds`.`sold_on_position` WHEN \'platinum\' THEN 1 WHEN \'diamond\' THEN 2 WHEN \'FreeListing\' THEN 3 END)'), 'asc')
			//	->orderby(DB::raw('(CASE `assigned_kwds`.`sold_on_position` WHEN \'platinum\' THEN 1 WHEN \'diamond\' THEN 2 END)'),'asc')
			//->orderby(DB::raw('(CASE `assigned_kwds`.`sold_on_position` WHEN \'premium\' THEN 1 WHEN \'platinum\' THEN 2 WHEN \'royal\' THEN 3 WHEN \'preferred\' THEN 4 END)'),'asc')
			// ->groupBy('client_id')
			//->orderby(DB::raw('(CASE `clients`.`certified_status` WHEN \'1\' THEN 1 END)'),'DESC')		
			->get();

		$data['clientsList'] = $clientsList;
		echo json_encode($data);


	}


}
