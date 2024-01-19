<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use App\Mail\OTPMail;
use App\Helper\JWTToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{

    // Page Return Methods
    function LoginPage()
    {
        return view('pages.auth.login-page');
    }

    function RegistrationPage()
    {
        return view('pages.auth.registration-page');
    }

    function SendOtpPage()
    {
        return view('pages.auth.send-otp-page');
    }

    function VerifyOTPPage()
    {
        return view('pages.auth.verify-otp-page');
    }

    function ResetPasswordPage()
    {
        return view('pages.auth.reset-pass-page');
    }
    function ProfilePage()
    {
        return view('pages.dashboard.profile-page');
    }






    function UserRegistration(Request $request)
    {
        // One and Oldest way to user create
        //    return  User::create([
        //     'firstName' => $request->input('firstName'),
        //     'lastName' => $request->input('lastName'),
        //     'email' => $request->input('email'),
        //     'mobile' => $request->input('mobile'),
        //     'password' => $request->input('password'),
        //    ]);


        //  That is the best way to create a new user

        try {                        // Try Catch for errors handling with out default code error

            User::create($request->input());

            return response()->json([
                'status' => 'success',
                'message' => 'User Registration Successfully'
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'Failed',
                'message' => 'User Registration Failed'
            ], 200);
        }

    }

    function UserLogin(Request $request)
    {

        $count = User::where('email', '=', $request->input('email'))
            ->where('password', '=', $request->input('password'))
            ->select('id')->first();

        if ($count !==null) {
            $token = JWTToken::CreateToken($request->input('email'), $count->id);
            return response()->json([
                'status' => 'success',
                'message' => 'User Login Successfully',
            ], 200)->cookie('token', $token, 60*24*30);

        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthorized',
            ], 200);
        }
    }

    function SendOtpCode(Request $request): object
    {
        $email = $request->input('email');
        $otp = rand(1000, 9999);
        $count = User::where('email', '=', $email)->count();

        if ($count == 1) {

            Mail::to($email)->send(new OTPMail($otp));

            User::where('email', '=', $email)->update(['otp' => $otp]);

            return response()->json([
                'status' => 'success',
                'message' => 'OTP Send Successfully'
            ]);

        } else {
            return response()->json([
                'status' => 'Failed',
                'message' => 'Unauthorized User'
            ]);
        }
    }

    // Otp Verification
    function VerifyOtp(Request $request)
    {
        $email = $request->input('email');
        $otp = $request->input('otp');
        $count = User::where('email', '=', $email)
            ->where('otp', '=', $otp)->count();

        if ($count == 1) {
            //Database Otp Update
            User::where('email', '=', $email)->update(['otp' => '0']);

            //Pass test Token Update
            $token = JWTToken::CreateTokenForResetPass($request->input('email'));
            return response()->json([
                'status' => 'success',
                'message' => 'Otp Veryfication Successfully',
            ], 200)->cookie('token', $token, 60*24*30);

        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthorized',
            ], 401);
        }
    }

    function ResetPassword(Request $request)
    {
        try {
            $email = $request->header('email');
            $password = $request->input('password');
            User::where('email', '=', $email)->update(['password' => $password]);
            return response()->json([
                'status' => 'success',
                'message' => 'Request Successful'
            ], 200);

        } catch (Exception $exception) {
            return response()->json([
                'status' => 'Failed',
                'message' => "Something went wrong"
            ], 200);
        }
    }


    function UserLogout(){
        return redirect('/userLogin')->cookie('token', '', -1);
    }


    function UserProfile(Request $request){
        $email = $request->header('email');
        $user = User::where('email','=', $email)->first();
        return response()->json([
            'status' => 'success',
            'message' =>'Request Successfull',
            'data' =>$user
        ],200);
    }

    function UpdateProfile(Request $request){
        try{
            $email = $request->header('email');
            $firstName = $request->input('firstName');
            $lastName = $request->input('lastName');
            $mobile = $request->input('mobile');
            $password = $request->input('password');

            User::where('email', '=', $email)->update([
                'firstName' => $firstName,
                'lastName' => $lastName,
                'mobile' => $mobile,
                'password' => $password
            ]);
            return response()->json([
                'status' => 'success',
                'message' =>'Request Successfully Updated',
            ], 200);
        }
        catch(Exception $e) {
            return response()->json([
                'status' => 'fail',
                'message' =>'Something went wrong',
            ],200);
        }
    }
}
