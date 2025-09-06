<?php

namespace App\Models\Client;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Client extends Authenticatable
{
    use SoftDeletes;
	protected $table = 'clients';
    protected $guarded = [];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */	
	protected $fillable = ['business_name','business_slug','username','password','first_name','last_name','mobile','city','email','address','landmark','state','country','sec_mobile','landline','fax','tollfree','website','display_hofo','time','payment_mode_accepted','certifications','year_of_estb','logo','profile_pic','pictures'];
	
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password','remember_token'
    ];
	
	protected $dates = ['deleted_at'];

    
}
