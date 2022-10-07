<?php

use App\Http\Controllers\UsersController;
use App\Http\Controllers\GoodsController;
use App\Http\Controllers\DebtRecordsController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SalesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Broadcast::routes(['middleware' => ['auth:api']]);
route::post('add_user',[UsersController::class,'addUser']);
route::post('login',[UsersController::class,'login']);

Route::middleware(['auth:api'])->group(function () {
    route::post('chack_password',[UsersController::class,'chackPassword']);

    route::post('add_goods',[GoodsController::class,'addGoods']);
    route::get('get_goods',[GoodsController::class,'getGoods']);
    route::get('get_goods_barcode',[GoodsController::class,'getGoodsBarcode']);
    route::put('edit_goods',[GoodsController::class,'editGoods']);
    route::delete('delete_goods',[GoodsController::class,'deleteGoods']);

    route::get('get_sales',[SalesController::class,'getSales']);
    route::post('add_sales',[SalesController::class,'addSales']);
    route::put('edit_sales',[SalesController::class,'EditSales']);
});
