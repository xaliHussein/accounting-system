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

class GoodsController extends Controller
{
    use SendResponse,Pagination;

    public function addGoods(Request $request){
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric',
            'buy_price' => 'required|numeric',
            'sale_price' => 'required|numeric',
            'product_code' => 'unique:goods,product_code',
            'company' => 'max:255',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ في المدخلات', $validator->errors(), []);
        }
        $good = Goods::create([
            'name' => $request['name'],
            'quantity' => $request['quantity'],
            'buy_price' => $request['buy_price'],
            'sale_price' => $request['sale_price'],
            'company' => $request['company'],
            'product_code' => $request['product_code'],
            'stores_id' => auth()->user()->store->id,
        ]);

        return $this->send_response(200, 'تمت عملية الاضافة بنجاح', [], $good);
    }

    public function getGoods()
    {
        $goods = Goods::where("stores_id", auth()->user()->store->id);
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            // return $filter;
            $goods->where($filter->name, $filter->value);
        }
        if (isset($_GET['query'])) {
            $goods->where(function ($q) {
                $columns = Schema::getColumnListing('goods');
                foreach ($columns as $column) {
                    $q->orWhere($column, 'LIKE', '%' . $_GET['query'] . '%');
                }
            });
        }
        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                if ($key == 'skip' || $key == 'limit' || $key == 'query' || $key == 'filter') {
                    continue;
                } else {
                    $sort = $value == 'true' ? 'desc' : 'asc';
                    $goods->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($goods,  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب البضاعة بنجاح', [], $res["model"], null, $res["count"]);
    }
    public function getGoodsBarcode()
    {
        $goods = Goods::where("stores_id", auth()->user()->store->id);
            if (isset($_GET['query'])) {
                $goods->where('product_code','=',$_GET['query']);
            if (!isset($_GET['skip']))
                $_GET['skip'] = 0;
            if (!isset($_GET['limit']))
                $_GET['limit'] = 10;
            $res = $this->paging($goods,  $_GET['skip'],  $_GET['limit']);
            return $this->send_response(200, 'تم جلب البضاعة بنجاح', [], $res["model"], null, $res["count"]);
        }

    }

    public function editGoods(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:goods,id",
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric',
            'buy_price' => 'required|numeric',
            'sale_price' => 'required|numeric',
            'product_code' => 'unique:goods,product_code,'.$request['id'],
            'company' => 'max:255',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ في المدخلات', $validator->errors(), []);
        }
        $good = Goods::find($request['id']);
        if (auth()->user()->store->id != $good->stores_id)
            return $this->send_response(400, 'لا يمكنك تعديل بيانات هذا المنتج', [], []);
        $good->update([
            'name' => $request['name'],
            'quantity' => $request['quantity'],
            'buy_price' => $request['buy_price'],
            'sale_price' => $request['sale_price'],
            'company' => $request['company'],
            'product_code' => $request['product_code'],
        ]);
        return $this->send_response(200, 'تمت عملية التعديل بنجاح', [], Goods::find($request['id']));
    }
     public function deleteGoods(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            "id" => "required|exists:goods,id",
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ في المدخلات', $validator->errors(), []);
        }
        $good = Goods::find($request['id']);
        if (auth()->user()->store->id != $good->stores_id)
            return $this->send_response(400, 'لا يمكنك حذف هذا المنتج', [], []);
        $good->delete();
        return $this->send_response(200, 'تمت عملية الحذف بنجاح', [], []);
    }
}
