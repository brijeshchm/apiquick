<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Client\Client;
use DB;

use App\Http\Controllers\Controller;

use App\Models\Client\Comment;
use Illuminate\Support\Facades\Validator;


class ReviewController extends Controller
{


    /**
     * @OA\Get(
     *     path="/api/business/get-review",
     *     tags={"Review"},
     *     summary="Get all reviews",
     *     description="Fetch paginated review list for authenticated business user.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Records per page",
     *         required=false,
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Review list fetched successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="lead_id", type="integer", example=101),
     *                     @OA\Property(property="rating", type="integer", example=4),
     *                     @OA\Property(property="comment", type="string", example="Great service"),
     *                     @OA\Property(property="created_at", type="string", example="2025-09-04 12:45:00")
     *                 )
     *             ),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="per_page", type="integer", example=10),
     *             @OA\Property(property="total", type="integer", example=35),
     *             @OA\Property(property="last_page", type="integer", example=4)
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */

    public function getReview(Request $request)
    {
        // ✅ Sanctum Auth
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // ✅ Pagination inputs
        $perPage = $request->get('per_page', 10);

        $reviews = DB::table('comments')
            ->where('comment_client_ID', $user->id)
            ->select('comment_ID', 'comment_author', 'comment_author_email', 'comment_author_phone', 'rating', 'comment_content', 'created_at')
            ->orderByDesc('comment_ID')
            ->paginate($perPage);

        return response()->json([
            'status' => true,
            'data' => $reviews->items(),
            'current_page' => $reviews->currentPage(),
            'per_page' => $reviews->perPage(),
            'total' => $reviews->total(),
            'last_page' => $reviews->lastPage(),
        ], 200);
    }





    /**
     * @OA\Get(
     *     path="/api/business/review",
     *     tags={"Review"},
     *     summary="Get authenticated user profile information",
     *     description="Returns profile details of the logged-in user. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User profile info",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john.com"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-04T12:34:56Z")
     *         )
     *     ),
     *      @OA\Response(
     *         response=401,
     *         description="Unauthenticated",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function profileReview(Request $request)
    {
        try {

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

            if (!$user->active_status) {
                $user->tokens()->delete();
                return response()->json(['status' => false, 'message' => 'User account is inactive',], 403);
            }


            $clientscheck = DB::table('clients')
                ->leftJoin(DB::raw('(
        SELECT 
            SUM(rating) AS total_rating, 
            comment_client_ID, 
            COUNT(comment_ID) AS comment_count
        FROM comments 
        GROUP BY comment_client_ID
    ) as c'), 'c.comment_client_ID', '=', 'clients.id')
                ->select(
                    'clients.id',
                    'clients.business_name',
                    'clients.business_slug',
                    DB::raw('COALESCE(c.total_rating, 0) as total_rating'),
                    DB::raw('COALESCE(c.comment_count, 0) as comment_count')
                )
                ->where('clients.id', $user->id)
                ->get()
                ->map(function ($client) {
                    // ✅ Fetch all comments for this specific client
                    $comments = DB::table('comments')
                        ->where('comment_client_ID', $client->id)
                        ->select('comment_ID', 'comment_content', 'rating', 'created_at', 'comment_author', 'comment_author_email', 'comment_author_phone')
                        ->orderByDesc('created_at')
                        ->get()
                        ->map(function ($comment) {
                        // ✅ Mask email
                        if (!empty($comment->comment_author_email)) {
                            [$name, $domain] = explode('@', $comment->comment_author_email);
                            $comment->comment_author_email =
                                substr($name, 0, 4) . str_repeat('*', max(0, strlen($name) - 4)) . '@' . $domain;
                        }

                        // ✅ Mask phone (keep first 3 and last 3 digits visible)
                        if (!empty($comment->comment_author_phone)) {
                            $phone = preg_replace('/\D/', '', $comment->comment_author_phone); // remove non-digits
                            $comment->comment_author_phone =
                                substr($phone, 0, 3) . str_repeat('*', max(0, strlen($phone) - 6)) . substr($phone, -3);
                        }

                        return $comment;
                    });

                    // ✅ Compute average rating
                    $avg_rating = $client->comment_count > 0
                        ? round($client->total_rating / $client->comment_count, 1)
                        : 0;

                    // ✅ Build clean output array
                    return [
                        'business_name' => $client->business_name,
                        'business_slug' => $client->business_slug,
                        'total_rating' => $client->total_rating,
                        'avg_rating' => $avg_rating,
                        'comment_count' => $client->comment_count,
                        'comments' => $comments,
                    ];
                })
                ->first();


            return response()->json([
                'status' => true,
                'data' => $clientscheck,
                'message' => 'get data record',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to : ' . $e->getMessage(),
            ], 500);
        }


    }
    /**
     * @OA\Get(
     *     path="/api/business/{id}/get-review-details",
     *     tags={"Review"},
     *     summary="Get review details by comment ID",
     *     description="Returns review details for a given comment ID",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Comment ID",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Review details fetched successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Record found"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=404,
     *         description="Record not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Record not found")
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function getReviewDetails(Request $request, $id)
    {
        // ✅ Validate path param
        if (!is_numeric($id)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid comment ID'
            ], 400);
        }


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

        
        // ✅ Fetch comment
        $comment = Comment::select('comment_ID', 'comment_author', 'comment_author_email', 'comment_author_phone', 'rating', 'comment_content', 'created_at')
            ->orderByDesc('comment_ID')->where('comment_client_ID',$user->id)->where('comment_ID', $id)->first();

        if (!$comment) {
            return response()->json([
                'status' => false,
                'message' => 'Record not found'
            ], 404);
        }



        return response()->json([
            'status' => true,
            'message' => 'Record found',
            'data' => $comment
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/business/update-review/{id}",
     *     tags={"Review"},
     *     summary="Update user review",
     *     description="Update review comment for authenticated business user. Requires Bearer token.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Comment ID",
     *         @OA\Schema(type="integer", example=308)
     *     ),
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"comment_content"},
     *             @OA\Property(
     *                 property="comment_content",
     *                 type="string",
     *                 example="Very good service, highly recommended"
     *             )
     *             
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Review updated successfully"
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Review not found")
     * )
     */


     public function updateReview(Request $request, $id)
    {
        $user = auth('sanctum')->user();
        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }

        $validator = Validator::make($request->all(), [
            'comment_content' => 'required|string|max:1000',
             
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $comment = Comment::where('comment_ID', $id)
            ->where('comment_client_ID', $user->id)
            ->first();
 
        if (!$comment) {
            return response()->json(['status' => false, 'message' => 'Review not found'], 404);
        }

        $comment->comment_content = $request->comment_content;
        $comment->save();
        return response()->json([
            'status' => true,
            'message' => 'Review updated successfully',
            'data' => $comment
        ]);
    }




}
