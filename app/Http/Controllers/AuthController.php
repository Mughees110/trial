<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Mail;
class AuthController extends Controller
{

    public function __construct()
    {
    	//Applying middleware auth (verify JWT) to this whole class except three methods ; login, check and register
        $this->middleware('auth:api', ['except' => ['login','register','check']]);
    }

    public function login(Request $request)
    {
    	//Applying validation for email and password
    	$validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:6',
        ]);
    	//Returning with error messages if vaildation fails
        if($validator->fails()){
                //return response()->json($validator->errors()->toJson(), 400);
                return response()->json(['messages'=>$validator->errors(),'status'=>'validation-error']);
        }
        $credentials = $request->only('email', 'password');
        //Generating JWT token by providing email and password 
        $token = Auth::attempt($credentials);
        //Sending error message if token doesnot exists
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 401);
        }
        //Getting user with the help of token
        $user = JWTAuth::user();
        //Making user logged In
        Auth::login($user);
        
        //Returning user with json web token
        return response()->json([
                'status' => 'success',
                'user' => $user,
                'authorisation' => [
                    'token' => $token,
                    'type' => 'bearer',
                ]
            ]);
    }

    public function register(Request $request){
    	//Applying validation for email , password and confirmed password
        $validator = Validator::make($request->all(), [
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6|confirmed',
            ]);
        //Returning with error messages if vaildation fails
        if($validator->fails()){
                //return response()->json($validator->errors()->toJson(), 400);
                return response()->json(['messages'=>$validator->errors(),'status'=>'validation-error']);
        }
        //Creating user with telling that It is not verified yet
        $user = User::create([
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'isVerified'=>'no'
        ]);
        //Generating random number for sending OTP on user email
        $code=rand(0000,9999);
        $email=$user->email;
        //Sending OTP 
        Mail::send('mail',['code'=>$code], function($message) use($email){
         $message->to($email)->subject('Laravel Task');
         $message->from('zaidimughees@gmail.com');
        });

        //$token = Auth::login($user);

        //Returning user with message and otp so that api may receive this otp with user input from app side 
        return response()->json([
            'status' => 'success',
            'message' => 'User created successfully',
            'user' => $user,
            'OTP'=>$code
        ]);
    }

    public function logout()
    {
    	//Logout User and send message
        Auth::logout();
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully logged out',
        ]);
    }

    public function check(Request $request){
    	//Applying validation for otp , user input and userId
    	$validator = Validator::make($request->all(), [
                'otp' => 'required|string',
                'input-otp' => 'required|string',
                'userId' => 'required'
            ]);
    	//Returning with error messages if vaildation fails
        if($validator->fails()){
                //return response()->json($validator->errors()->toJson(), 400);
                return response()->json(['messages'=>$validator->errors(),'status'=>'validation-error']);
        }
        //Checking user id belongs to any user or not
        $userExists=User::where('id',$request->get('userId'))->exists();
        //Returning with error message if user does not exists
        if($userExists==false){
        	return response()->json(['status'=>'error','message'=>'User does not belongs to given Id']);
        }
        //Getting user
        $user=User::find($request->get('userId'));
        //Checking OTP (that register api sent) with user input on app side and returning with error message if both does not match
        if($request->get('otp')!=$request->get('input-otp')){
        	return response()->json(['status'=>'error','message'=>'OTP does not match. Try again']);
        }
        //Making user verified
        $user->isVerified="yes";
        $user->save();
        //Returning user with success message
        return response()->json(['status'=>'success','message'=>'Email verified successfully','user'=>$user]);
    }

}
