<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Store;
use App\Models\Goods;
use App\Models\Sales;
use App\Models\GoodSales;
use App\Traits\SendResponse;
use App\Traits\Pagination;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DebtRecordsController extends Controller
{
    use SendResponse, Pagination;

    // احضار سجلات الديون
    public function DebtRecords()
    {
         if (isset($_GET["sales_id"])) {
            $goods = GoodSales::where('sales_id', $_GET["sales_id"]);
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
                    if ($key == 'skip' || $key == 'limit' || $key == 'query' || $key == 'invoice_id') {
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
            if ($goods) {
                return $this->send_response(200, 'تم جلب الفواتير بنجاح', [], $res["model"], null, $res["count"]);
            } else {
                return $this->send_response(404, 'لا يوجد فاتورة بهذا الرقم', [], []);
            }
        }

        $Sales = Sales::where("stores_id", auth()->user()->store[0]->id)->where("debt_record",1);
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
        $res = $this->paging($Sales->orderBy("created_at", "desc"),  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب البضاعة بنجاح', [], $res["model"], null, $res["count"]);
    }
    // دفع الديون على العميل
    public function PayDebtRecords(Request $request){
        $request = $request->json()->all();
        $validator = Validator::make($request, [
            'id'=>'required|exists:sales,id',
            'payment_amount'=>'required|numeric',
        ]);
        if ($validator->fails()) {
            return $this->send_response(400, 'خطأ في البيانات', $validator->errors()->all());
        }
        $PayDebt = Sales::find($request['id']);
        $PayDebt->update([
            'total_debt' =>$PayDebt->total_debt-$request['payment_amount']
        ]);
        return $this->send_response(200, 'تمت عملية التعديل بنجاح', [], Sales::find($request['id']));
    }
    // احصائيات الديون
     public function getDebtStats(){
        $sales = Sales::where('stores_id',auth()->user()->store[0]->id)->
        where('debt_record',1)->select([
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
        $resulte=[];
        foreach($salesChartByDay as $key => $val) {
            array_push($chart,$key);
            array_push($data,$val);
        }
        array_push($resulte,$data);
        array_push($resulte,$chart);
        return $this->send_response(200,'تم احضار احصائيات',[],$resulte);
    }
}
