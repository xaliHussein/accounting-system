<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $with = ['user'];

    public function user(){
        return $this->belongsTo(User::class,'user_id');
    }
    public function goods(){
        return $this->hasMany(Goods::class,'stores_id');
    }
    public function sales(){
        return $this->hasMany(Goods::class,'stores_id');
    }
}
