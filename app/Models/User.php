<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     * @var list<string>
     */
    protected $fillable = [
        'row_id',
        'first_name',
        'last_name',
        'email',
        'password',
        'mobile',
        'password',
        'role',
        'status'
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

    public function getTableIndex(){
        return 'row_id';
    }

    public function fetch_row_by_field($field, $value){
        return self::where($field, $value)->first();
    }

    public function fetch_row_by_id($value){
        return self::where($this->getTableIndex(), $value)->first();
    }

    public function format($row){

        if($row){
            $row->first_name = clean_display($row->first_name);
            $row->last_name = clean_display($row->last_name);
            $row->email = clean_display($row->email);
            unset($row->password);
        }

        return $row;
    }

    public function branches(){

        return [
            ['key' => 'delhi', 'value' => 'Delhi'],
            ['key' => 'mumbai', 'value' => 'Mumbai']
        ];
    }
}
