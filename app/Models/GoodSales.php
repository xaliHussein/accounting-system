<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodSales extends Model
{
    use HasFactory;
     protected $table = 'good_sales';
     protected $guarded = [];
     protected $with = ['goods'];

    public function goods(){
        return $this->belongsTo(Goods::class, 'goods_id');
    }
}
