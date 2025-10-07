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
 *     tags={"Frontend API"},
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
		$homePageList = [
			[
			'url'=>$url.'categories/professional-courses',
			'img'=>$url.'img/IT-Training.png',
			'alt'=>'computer courses',
			'title'=>'computer courses',

			],
			[
			'url'=>$url.'child/wedding-planning',
			'img'=>$url.'img/wedding.png',
			'alt'=>'Wedding Planning',
			'title'=>'Wedding Planning',

			],
			[
			'url'=>$url.'categories/electric-services',
			'img'=>$url.'img/electric-services.png',
			'alt'=>'Electric Services',
			'title'=>'Electric Services',

			],
			[
			'url'=>$url.'categories/entrance-exams-coaching',
			'img'=>$url.'img/government-exam.png',
			'alt'=>'Government exam',
			'title'=>'Government exam',

			],
			[
			'url'=>$url.'categories/study-abroad',
			'img'=>$url.'img/study-abroad.png',
			'alt'=>'Study Abroad',
			'title'=>'Study Abroad',

			],
			[
			'url'=>$url.'categories/spa-beauty',
			'img'=>$url.'img/Spa & Beauty.png',
			'alt'=>'Spa & Beauty',
			'title'=>'Spa & Beauty',

			],
			[
			'url'=>$url.'categories/repairs-services',
			'img'=>$url.'img/Repairs-Services.png',
			'alt'=>'Repair Services',
			'title'=>'Repair Services',

			],
			[
			'url'=>$url.'categories/packers-movers',
			'img'=>$url.'img/Packers-movers.png',
			'alt'=>'Packers & Movers',
			'title'=>'Packers & Movers',

			],
			[
			'url'=>$url.'categories/professional',
			'img'=>$url.'img/Professional.png',
			'alt'=>'Professional',
			'title'=>'Professional',

			],
			[
			'url'=>$url.'categories/contractors',
			'img'=>$url.'img/contractors.png',
			'alt'=>'Contractors',
			'title'=>'Contractors',

			],
			[
			'url'=>$url.'categories/distance-education',
			'img'=>$url.'img/Education.png',
			'alt'=>'Education',
			'title'=>'Education',

			],
			[
			'url'=>$url.'categories/rent-buy',
			'img'=>$url.'img/Rent-buy.png',
			'alt'=>'Rent & Buy',
			'title'=>'Rent & Buy',
			],
			[
			'url'=>$url.'child/sports-academy',
			'img'=>$url.'img/sports.png',
			'alt'=>'Sport Academy',
			'title'=>'Sport Academy',
			],
			[
			'url'=>$url.'categories/medical',
			'img'=>$url.'img/Medical.png',
			'alt'=>'Medical',
			'title'=>'Medical',
			],
			[
			'url'=>$url.'categories/loan',
			'img'=>$url.'img/Loan.png',
			'alt'=>'Loan',
			'title'=>'Loan',
			],
			[
			'url'=>$url.'categories/dancing',
			'img'=>$url.'img/Dancing.png',
			'alt'=>'Dancing',
			'title'=>'Dancing',
			],
			[
			'url'=>$url.'categories/yoga',
			'img'=>$url.'img/Yoga.png',
			'alt'=>'Yoga',
			'title'=>'Yoga',
			],
			[
			'url'=>$url.'categories/security-system',
			'img'=>$url.'img/CCTV-security.png',
			'alt'=>'CCTV Security',
			'title'=>'CCTV Security',
			],
			[
			'url'=>$url.'categories/web-technologies',
			'img'=>$url.'images/Web-Designers.png',
			'alt'=>'Web Designers',
			'title'=>'Web Designers',
			],
			[
			'url'=>$url.'tours-and-travels',
			'img'=>$url.'images/tour-travels.png',
			'alt'=>'Tours & Travels',
			'title'=>'Tours & Travels',
			],

		];
echo json_encode($homePageList);
	 
 

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
