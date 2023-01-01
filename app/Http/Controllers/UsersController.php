<?php

namespace App\Http\Controllers;



use App\Models\User;
use App\Models\Sales;
use App\Models\Store;
use App\Traits\Pagination;
use App\Traits\SendResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class UsersController extends Controller
{
    use SendResponse,Pagination;
    // اضافة مستخدمين
    public function addUser(Request $request){
        $request= $request->json()->all();
        $validator= Validator::make($request,[
            'name'=>'required',
            'name_store'=>'required',
            'user_name'=>'required|unique:users,user_name',
            'phone_number'=>'required|unique:users,phone_number',
            'password'=>'required'
        ]);
        if($validator->fails()){
            return $this->send_response(400,'فشل عملية تسجيل الدخول',$validator->errors(),[]);
        }
        $user = User::create([
            'name'=> $request['name'],
            'activation'=> 0,
            'user_name'=>$request['user_name'],
            'phone_number'=>$request['phone_number'],
            'password'=>bcrypt($request['password'])
        ]);
        $store = Store::create([
            'name'=> $request['name_store'],
            'user_id'=> $user->id,
        ]);

        return $this->send_response(200,'تم اضافة الحساب بنجاح',[], User::find($user->id));
    }
    // تسجيل الدخول
     public function login(Request $request){
        $request = $request->json()->all();
        $validator = Validator::make($request,[
            'user_name'=>'required',
            'password'=>'required'
        ],[
            'user_name.required'=>'اسم المستخدم مطلوب',
            'password.required'=>'كلمة المرور مطلوبة'
        ]);
        if($validator->fails()){
            return $this->send_response(400,'فشل عملية تسجيل الدخول',$validator->errors(),[]);
        }
        if(auth()->attempt(array('user_name'=> $request['user_name'], 'password'=> $request['password']))){
            $user=auth()->user();
                $token= $user->createToken('accounting_system')->accessToken;
                return $this->send_response(200,'تم تسجيل الدخول بنجاح',[], $user, $token);
        }else{
            return $this->send_response(400, 'هناك مشكلة تحقق من تطابق المدخلات', null, null, null);
        }
    }
    // خطوه اضافية لتاكد من كلمة المرور
    public function chackPassword(Request $request){
        $request = $request->json()->all();
        $validator = Validator::make($request,[
            'user_name'=>'required',
            'password'=>'required'
        ],[
            'user_name.required'=>'اسم المستخدم مطلوب',
            'password.required'=>'كلمة المرور مطلوبة'
        ]);
         if($validator->fails()){
            return $this->send_response(400,'فشل عملية تسجيل الدخول',$validator->errors(),[]);
        }
        if(Hash::check($request['password'],auth()->user()->password ) && $request['user_name'] == auth()->user()->user_name){
            return $this->send_response(200,'تم تسجيل الدخول بنجاح',[],[]);
        }else{
            return $this->send_response(400, 'هناك مشكلة تحقق من تطابق المدخلات', null, null, null);
        }
    }
    // تغير اسم المستخدم
    public function userNameChange(Request $request){
        $request= $request->json()->all();
          $validator = Validator::make($request,[
            'user_name'=>'required:min:3|max:15|unique:users,user_name,'.auth()->user()->id,
            'password'=>'required',
        ]);
        if($validator->fails()){
            return $this->send_response(400,'فشلة العملية',$validator->errors(),[]);
        }
       if(Hash::check($request['password'],auth()->user()->password)){
            $user = User::find(auth()->user()->id);
            $user->update([
                'user_name'=>$request['user_name']
            ]);
             return $this->send_response(200,'تمت العملية بنجاح',[], User::find(auth()->user()->id));
        }else{
            return $this->send_response(400, 'هناك مشكلة تحقق من تطابق المدخلات', 'هناك مشكلة تحقق من تطابق المدخلات', null, null);
        }
    }
    // تغير كلمة المرور
     public function passwordChange(Request $request){
         $request= $request->json()->all();
          $validator = Validator::make($request,[
            'old_password'=>'required:min:3|max:15',
            'new_password'=>'required|min:3|max:15',
        ]);
        if($validator->fails()){
            return $this->send_response(400,'فشلة العملية',$validator->errors(),[]);
        }
       if(Hash::check($request['old_password'],auth()->user()->password)){
            $user = User::find(auth()->user()->id);
            $user->update([
                'password'=>bcrypt($request['new_password'])
            ]);
            return $this->send_response(200,'تمت العملية بنجاح',[],[]);
        }else{
            return $this->send_response(400, 'فشلة العملية', 'هناك مشكلة تحقق من تطابق المدخلات', null, null);
        }
    }

    // احضار المستخدمين الموجودين في المنصه
    public function getUsers(){
        $users = User::select("*");
        if (isset($_GET['filter'])) {
            $filter = json_decode($_GET['filter']);
            $users->where($filter->name, $filter->value);
        }
        if (isset($_GET['query'])) {
            $columns = Schema::getColumnListing('users');
            foreach ($columns as $column) {
                $users->orWhere($column, 'LIKE', '%' . $_GET['query'] . '%');
            }
        }
        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                if ($key == 'skip' || $key == 'limit' || $key == 'query' || $key == 'filter') {
                    continue;
                } else {
                    $sort = $value == 'true' ? 'desc' : 'asc';
                    $users->orderBy($key,  $sort);
                }
            }
        }
        if (!isset($_GET['skip']))
            $_GET['skip'] = 0;
        if (!isset($_GET['limit']))
            $_GET['limit'] = 10;
        $res = $this->paging($users,  $_GET['skip'],  $_GET['limit']);
        return $this->send_response(200, 'تم جلب المستخدمين بنجاح', [], $res["model"], null, $res["count"]);
    }
    // تغير حالة المستخدم
    public function UserStatusChange(Request $request){
         $request= $request->json()->all();
         $validator = Validator::make($request,[
            'id'=>'required|exists:users,id',
        ]);
        if($validator->fails()){
            return $this->send_response(400,'فشلة العملية',$validator->errors(),[]);
        }
        $user=User::find($request['id']);
        $user->update([
            'activation'=> !$user->activation
        ]);
        return $this->send_response(200,'تم تسجيل الدخول بنجاح',[],User::find($user->id));
    }
    public function chackUser(){
        $user=User::find(auth()->user()->id);
        return $this->send_response(200,'تم تسجيل الدخول بنجاح',[],$user);
    }

}

