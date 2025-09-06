<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use DB;
class BusinessDiscussionController extends Controller
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
 *     path="/api/business/get-discussion",
 *     tags={"Discussion"},
 *     summary="Get all discussions",
 *     description="Fetch all discussions. Requires Bearer token.",
 *     security={{"bearerAuth":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="List of discussions",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="data", type="array",
 *                 @OA\Items(
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="title", type="string", example="How to improve SEO for my business?"),
 *                     @OA\Property(property="author", type="string", example="Amit Sharma"),
 *                     @OA\Property(property="author_id", type="integer", example=42),
 *                     @OA\Property(property="replies_count", type="integer", example=5),
 *                     @OA\Property(property="last_reply_at", type="string", format="date-time", example="2025-09-04T14:30:00Z"),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-03T09:15:00Z")
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

	public function getDiscussion(Request $request)
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
			$data['discussion'] = DB::table('client_discussion')
				->orderBy('id', 'desc')
				->where('client_id', $user->id)
				->get();
			echo json_encode($data);	
	}
}
