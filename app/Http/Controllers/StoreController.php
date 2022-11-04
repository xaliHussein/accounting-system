<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Goods;
use App\Models\Sales;
use App\Models\Store;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
    use SendResponse,Pagination;

    // تغير اسم المتجر
    public function storeNameChange(Request $request){
         $request= $request->json()->all();
          $validator = Validator::make($request,[
            'store'=>'required:min:3|max:16|unique:stores,name,'.auth()->user()->store[0]->id,
            'password'=>'required',
        ]);
        if($validator->fails()){
            return $this->send_response(400,'فشلة العملية',$validator->errors(),[]);
        }
       if(Hash::check($request['password'],auth()->user()->password)){
            $store = Store::find(auth()->user()->store[0]->id);
            $store->update([
                'name'=>$request['store']
            ]);
             return $this->send_response(200,'تمت العملية بنجاح',[], Store::find(auth()->user()->store[0]->id));
        }else{
            return $this->send_response(400, 'هناك مشكلة تحقق من تطابق المدخلات', 'هناك مشكلة تحقق من تطابق المدخلات', null, null);
        }
    }
    // احصائيات المتجر
    public function getStoreStats(){
        // قيمة مبيعات لاسبوع
        $sales= Sales::where('stores_id',auth()->user()->store[0]->id)
        ->where('debt_record',0)->select(DB::raw('SUM(total_price) as total'))
        ->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()])->get();

        // قيمة الديون لاسبوع
        $debt= Sales::where('stores_id',auth()->user()->store[0]->id)
        ->where('debt_record',1)->select(DB::raw('SUM(total_debt) as total'))
        ->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()])->get();

        // قيمة راس المال الحالي
        $capital= Goods::where('stores_id',auth()->user()->store[0]->id)
        ->select(DB::raw('SUM(buy_price * quantity) as total'))->get();

       $resulte=[$sales[0],$debt[0],$capital[0]];
        return $this->send_response(200,'تمت العملية بنجاح',[], $resulte);
    }
}
