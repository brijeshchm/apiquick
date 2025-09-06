<?php

namespace App\Models\Client;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    //
	protected $table = 'comments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */	
	protected $fillable = ['comment_ID','comment_client_ID','comment_author','comment_author_email','comment_author_url','comment_author_IP','comment_content','comment_approved','admin_id','rating','comment_author_phone','OTP','created_at','updated_at'];
}
