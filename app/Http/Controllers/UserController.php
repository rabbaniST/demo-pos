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
            ->count();

        if ($count == 1) {
            $token = JWTToken::CreateToken($request->input('email'));
            return response()->json([
                'status' => 'success',
                'message' => 'User Login Successfully',
                'token' => $token
            ], 200);

        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthorized',
            ], 200);
        }
    }

    function SendOtpCode(Request $request)
    {
        $email = $request->input('email');
        $otp = rand(1000, 9999);
        $count = User::where('email', '=', $email)->count();

        if ($count == 1) {
            Mail::to($email)->send(new OTPMail($otp));
            User::where('email', '=', $email)->update(['otp' => $otp]);

            return response()->json([
                'status' => 'Success',
                'message' => '4 digit otp code send successfully',
            ]);
        } else {
            return response()->json([
                'status' => 'Failed',
                'message' => 'Unauthorized',
            ], 401);
        }
    }

    // Otp Verification
    function VeryfyOtp(Request $request)
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
                'token' => $token
            ], 200);

        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'Unauthorized',
            ], 401);
        }
    }

    function ResetPassword(Request $request){
        try{
            $email=$request->header('email');
            $password=$request->input('password');
            User::where('email','=',$email)->update(['password'=>$password]);
            return response()->json([
                'status' => 'success',
                'message' => 'Request Successful'
            ],200);

        }catch (Exception $exception){
            return response()->json([
                'status' => 'Failed',
                'message' => "Something went wrong"
            ],200);
        }
    }
}
