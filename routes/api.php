<?php

use App\Http\Controllers\UsersController;
use App\Http\Controllers\GoodsController;
use App\Http\Controllers\DebtRecordsController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\SalesController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
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
    route::put('user_name_change',[UsersController::class,'userNameChange']);
    route::put('password_change',[UsersController::class,'passwordChange']);
    route::get('get_users',[UsersController::class,'getUsers']);
    route::post('add_users',[UsersController::class,'addUser']);
    route::put('User_status_change',[UsersController::class,'UserStatusChange']);
    route::get('chack_user',[UsersController::class,'chackUser']);

    route::post('add_goods',[GoodsController::class,'addGoods']);
    route::get('get_goods',[GoodsController::class,'getGoods']);
    route::get('get_goods_barcode',[GoodsController::class,'getGoodsBarcode']);
    route::put('edit_goods',[GoodsController::class,'editGoods']);
    route::delete('delete_goods',[GoodsController::class,'deleteGoods']);

    route::get('get_sales_stats',[SalesController::class,'getSalesStats']);
    route::get('get_sales',[SalesController::class,'getSales']);
    route::post('add_sales',[SalesController::class,'addSales']);
    route::put('edit_sales',[SalesController::class,'EditSales']);
    route::put('retrive_goods',[SalesController::class,'retriveGoods']);

    route::get('get_debt_stats',[DebtRecordsController::class,'getDebtStats']);
    route::get('get_debt_records',[DebtRecordsController::class,'DebtRecords']);
    route::put('pay_debt_records',[DebtRecordsController::class,'PayDebtRecords']);

    route::get('get_store_stats',[StoreController::class,'getStoreStats']);
    route::put('store_name_change',[StoreController::class,'storeNameChange']);
});
