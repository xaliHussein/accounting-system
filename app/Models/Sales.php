<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sales extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $with = ['good_sales'];

    public function good_sales(){
        return $this->hasMany(GoodSales::class,'sales_id');
    }
}
