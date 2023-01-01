<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use App\Models\Goods;
use App\Models\Sales;
use App\Models\GoodSales;
use App\Traits\SendResponse;
use Carbon\CarbonPeriod;
use App\Traits\Pagination;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    use SendResponse, Pagination;
    public function random_code()
    {
        $code = substr(str_shuffle("0123456789"), 0, 8);
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
        $Sales = Sales::where("stores_id", auth()->user()->store[0]->id);
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
            'sales_recovery' => 0,
            'code_invoices' => $this->random_code(),
            'stores_id' => auth()->user()->store[0]->id,
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
            $total_price += $good->sale_price * $good_id['quantity'];
        }
        $data['total_price'] = $total_price;

         if($request['debt_record'] == 0){
            $data['debt_record'] = 0;
            $data['total_debt'] = 0;
        }else{
            $data['debt_record'] = 1;
            $data['total_debt'] = $total_price;
        }
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
    // راجع بيع
    public function retriveGoods(Request $request)
    {
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'sales_id' => 'required|exists:sales,id',
            'goods_id' => 'required|exists:goods,id',
            'good_quantity' => 'required',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ في البيانات', $validator->errors()->all());
        }

        $sales = Sales::find($request['sales_id']);
        if ($sales->stores_id != auth()->user()->store[0]->id) {
            return $this->send_response(400, 'لا يمكنك تعديل هذه الفاتورة', [], []);
        }

        $good = Goods::find($request['goods_id']);
        $good_sale = GoodSales::where("sales_id", $request['sales_id'])->where("goods_id", $request['goods_id'])->first();
        if ($good_sale) {
            // استرجاع جزء من المواد الموجوده في الفاتوره
            if ($good_sale->quantity > $request['good_quantity']) {
                $good_sale->update([
                    'quantity' => $good_sale->quantity - $request['good_quantity']
                ]);
            // اذا كمية المنتج المتوفر في المخزن  يساوي عدد
            //  المراد ارجاعه اي استرجاع فاتوره كلها
            } else if ($good_sale->quantity == $request['good_quantity']) {
                $good_sale->delete();
                $sales->update([
                    'sales_recovery'=> 1,
                ]);
            } else {
                return $this->send_response(400, 'كمية البضاعة المراد استرجاعها اكبر من المتوفرة في الفاتورة', [], []);
            }
            $good->update([
                "quantity" => $good->quantity + $request['good_quantity']
            ]);
            if($sales->debt_record == 1){
                    $sales->update([
                        'total_debt' => $sales->total_debt - ($good->sale_price * $request["good_quantity"]),
                    ]);
                }
            $sales->update([
                'total_price' => $sales->total_price - ($good->sale_price * $request["good_quantity"]),
            ]);
        } else {
            return $this->send_response(400, 'يرجى ادخال بضاعة متوفرة في الفاتورة', [], []);
        }

        return $this->send_response(200, 'تم تعديل الفاتورة بنجاح', [], Sales::find($sales->id));
    }
    // احصائيات المبيعات
    public function getSalesStats(){
        $sales = Sales::where('stores_id',auth()->user()->store[0]->id)->
        where('debt_record',0)->select([
            DB::raw('DATE(created_at) AS date'),
            DB::raw('COUNT(stores_id) AS count')
        ])->whereBetween('created_at', [Carbon::now()->subDays(6), Carbon::now()])
            ->groupBy('date')
            ->orderBy('date','DESC')
            ->get()
            ->toArray();
            $salesChartByDay = array();
        $lastSevenDays = CarbonPeriod::create(Carbon::now()->subDays(6), Carbon::now());
        foreach ($sales as $data) {
              foreach ($lastSevenDays as $date) {
                $dateString = $date->format('M j');
                if (!isset($salesChartByDay[$dateString])) {
                    $salesChartByDay[$dateString] = 0;
                }
            }
            if (isset($salesChartByDay[$dateString])) {
                $date = date('M j', strtotime($data['date']));
                $salesChartByDay[$date] = $data['count'];
            }
        }
        $data=[];
        $chart=[];
        foreach($salesChartByDay as $key => $val) {
            array_push($chart,$key);
            array_push($data,$val);
        }
        $resulte=[$data,$chart];
        return $this->send_response(200,'تم احضار احصائيات',[],$resulte);
    }
    // احضار جدول بضائع لاختيار منها في خانة بيع
     public function getSalesGoods()
    {
        $goods = Goods::where("stores_id", auth()->user()->store[0]->id);
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
        $res = $this->paging($goods->orderBy("created_at", "desc"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب البضاعة بنجاح', [], $res["model"], null, $res["count"]);
    }
}
