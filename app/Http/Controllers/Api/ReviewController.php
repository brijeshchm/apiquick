<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
 

use App\Http\Requests;
use App\Http\Controllers\Controller;
 
use App\Models\Client\Comment;
use Illuminate\Support\Facades\Validator;
use DB;

class ReviewController extends Controller
{

/**
 * @OA\Post(
 *     path="/api/business/{client_id}/saveReview",
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
            'comment_author'        => 'required|string|regex:/^[A-Za-z ]+$/',
            'comment_author_phone'  => 'required|digits:10',
            'comment_author_email'  => 'required|email',
            'comment_content'       => 'required|string',
            's_rating'              => 'required|integer|min:1|max:5',
        ]);

        // 🔒 Check last review date (30 days rule)
        $lastReviewDate = DB::table('comments')
            ->where('comment_author_email', $request->comment_author_email)
            ->where('comment_client_ID', $client_id)
            ->max(DB::raw('DATE(created_at)'));

        if ($lastReviewDate && now()->diffInDays($lastReviewDate) <= 30) {
            return response()->json([
                'status'  => false,
                'message' => 'Thanks for your feedback! You are already submitted a review for this business'
            ], 429);
        }

        // 💾 Save review
        Comment::create([
            'comment_client_ID'      => $client_id,
            'comment_author'         => $request->comment_author,
            'comment_author_phone'   => $request->comment_author_phone,
            'comment_author_email'   => $request->comment_author_email,
            'comment_content'        => $request->comment_content,
            'rating'                 => $request->s_rating,
            'admin_id'                 => '0',
            'OTP'                 => '0',
            'comment_author_IP'      => $request->ip(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Review successfully submitted.'
        ], 200);
    }

}
