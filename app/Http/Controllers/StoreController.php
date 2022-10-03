<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use App\Models\Goods;
use App\Traits\SendResponse;
use App\Traits\Pagination;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    use SendResponse,Pagination;

}
