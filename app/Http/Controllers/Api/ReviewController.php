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
 *     description="Fetch review list for the authenticated business user. Requires Bearer token.",
 *     security={{"bearerAuth":{}}},
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
 *                     type="object",
 *                     @OA\Property(property="id", type="integer", example=1),
 *                     @OA\Property(property="lead_id", type="integer", example=101),
 *                     @OA\Property(property="rating", type="integer", example=4),
 *                     @OA\Property(property="comment", type="string", example="Great service"),
 *                     @OA\Property(property="created_at", type="string", format="date-time", example="2025-09-04T12:45:00Z")
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
 *         description="Unauthenticated",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Unauthenticated")
 *         )
 *     )
 * )
 */

	public function getReview(Request $request)
    {
        if (!Auth::guard('sanctum')->check()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $user = auth('sanctum')->user();

        $reviews = DB::table('comments')
            ->where('comment_client_ID', $user->id)
            ->select(
                'id',
                'lead_id',
                'rating',
                'comment',
                'created_at'
            )
            ->orderBy('id', 'desc')
            ->paginate(10);

        return response()->json([
            'status' => true,
            'data' => $reviews->items(),
            'current_page' => $reviews->currentPage(),
            'per_page' => $reviews->perPage(),
            'total' => $reviews->total(),
            'last_page' => $reviews->lastPage(),
        ], 200);
    }




}
