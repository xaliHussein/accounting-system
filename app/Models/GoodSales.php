<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodSales extends Model
{
    use HasFactory;
     protected $table = 'good_sales';
     protected $guarded = [];

    public function good(){
        return $this->belongsTo(Goods::class, 'good_id');
    }
}
