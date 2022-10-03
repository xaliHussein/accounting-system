<?php

namespace App\Http\Controllers;


use App\Models\User;
use App\Traits\SendResponse;
use App\Traits\Pagination;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    use SendResponse,Pagination;

    public function addUser(Request $request){
        $request= $request->json()->all();
        $validator= Validator::make($request,[
            'name'=>'required',
            'user_name'=>'required|unique:users,user_name',
            'phone_number'=>'required|unique:users,phone_number',
            'password'=>'required'
        ],[
            'name.required'=>'حقل الاسم مطلوب',
            'user_name.required'=>' اسم المستخدم مطلوب',
            'user_name.unique'=>'اسم المستحدم موجود مسبقا',
            'phone_number.required'=>'رقم الهاتف مطلوب',
            'phone_number.unique'=>'رقم الهاتف موجود مسبقا',
            'password.required'=>'كلمة المرور مطلوبة'
        ]);
        if($validator->fails()){
            return $this->send_response(400,'فشل عملية تسجيل الدخول',$validator->errors(),[]);
        }
        $user = User::create([
            'name'=> $request['name'],
            'user_name'=>$request['user_name'],
            'phone_number'=>$request['phone_number'],
            'password'=>bcrypt($request['password'])
        ]);
        $token= $user->createToken('accounting_system')->accessToken;
        return $this->send_response(200,'تم اضافة الحساب بنجاح',[], User::find($user->id));
    }
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
}
