<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use App\Models\Goods;
use App\Models\Sales;
use App\Models\GoodSales;
use App\Traits\SendResponse;
use App\Traits\Pagination;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;

class SalesController extends Controller
{
    use SendResponse, Pagination;
    public function random_code()
    {
        $code = substr(str_shuffle("0123456789"), 0, 6);
        $get = Sales::where('code_invoices', $code)->first();
        if ($get) {
            return $this->random_code();
        } else {
            return $code;
        }
    }

    // احضار جميع المبيعات
    public function getSales()
    {
        $Sales = Sales::where("stores_id", auth()->user()->store->id);
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            // return $filter;
            $Sales->where($filter->name, $filter->value);
        }
        if (isset($_GET['query'])) {
            $Sales->where(function ($q) {
                $columns = Schema::getColumnListing('Sales');
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
                    $Sales->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($Sales,  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب البضاعة بنجاح', [], $res["model"], null, $res["count"]);
    }
    // انشاء عمليه شراء
    public function addSales(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'goods_id.*.id' => 'required|exists:goods,id',
            'goods_id.*.quantity' => 'required|Numeric',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ في البيانات', $validator->errors()->all());
        }

        $total_price = 0;
        $data = [];
        $data = [
            'client_name' => $request['client_name'],
            'client_phone' => $request['client_phone'],
            'code_invoices' => $this->random_code(),
            'stores_id' => auth()->user()->store->id,
        ];
        $goods_id = [];
        foreach ($request['goods_id'] as $good_id) {
            $good = Goods::find($good_id['id']);
            array_push($goods_id, $good_id['id']);
            if ($good->quantity < $good_id['quantity']) {
                return $this->send_response(400, 'الكمية المطلوبة أكبر من المتاحة', [], []);
            }
            $good->update([
                'quantity' => $good->quantity - $good_id['quantity']
            ]);
            $total_price += $good->buy_price * $good_id['quantity'];
        }

        $data['total_price'] = $total_price;
        $sales = Sales::create($data);
        foreach ($goods_id as $key => $good_id) {
            GoodSales::create([
                'goods_id' => $good_id,
                'sales_id' => $sales->id,
                'quantity' => $request['goods_id'][$key]['quantity']
            ]);
        }
        return $this->send_response(200, 'تم اضافة الفاتورة بنجاح', [], Sales::find($sales->id));
    }
}
