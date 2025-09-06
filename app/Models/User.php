<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_name', 'first_name', 'mobile', 'email', 'password', 'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

	public function capability(){
		return $this->hasOne('App\Models\Capability');
	}
	public function current_user_can($role_cap=NULL){
	/* 	if(is_null($role_cap)){
			return false;
		}
		$role_caps = explode("|",$role_cap);
		foreach($role_caps as $role_cap){
			if($this->role == $role_cap)
				return true;
		} */
		
			if(is_null($role_cap))
			return false;
		if($this->role == $role_cap)
			return true;
		 $capabilities = $this->capability()->first();
	 if(!empty($capabilities)){
			 if(isset($capabilities->capabilities) && !is_null($capabilities->capabilities)){
				 $capabilities = unserialize($capabilities->capabilities);
				 foreach($capabilities as $capability){
					 if($capability == $role_cap)
						 return true;
			 }
			}
	 }
		return false;
	}

}
