<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Jobs\SendOtpJob;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AccountCreationController extends Controller
{
    /*************  ✨ Codeium Command ⭐  *************/
    /**
     * Register a new user.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    /******  3535583e-bf57-4f81-8657-d8df6c2ab1aa  *******/
    public function register(Request $request)
    {
        $email = $request->email;

        $user = User::where('email', $email)->first();

        if ($user) {
            $otp = rand(1000, 9999);
            dispatch(new SendOtpJob($email, $otp));

            $user->otp = $otp;
            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'User Already Exist',
                'otp' => 'OTP sent successfully! Plesase Verify'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required|unique:users,email,',
            'password' => [
                'required',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ]);
        }
        $otp = rand(1000, 9999);
        dispatch(new SendOtpJob($email, $otp));



        $user = new User();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->role = 'user';
        $user->status = 'pending';
        $user->otp = $otp;
        $user->save();

        $user_id = $user->id;

        return response()->json([
            'status' => true,
            'message' => 'User Registred Successfully',
            'otp' => 'OTP sent successfully! Plesase Verify',
            'user_id' => $user_id
        ]);
    }

    public function verifyOtp(Request $request, $id)
    {
        $user = User::where('id', $id)->first();
        $validator = Validator::make($request->all(), [
            'otp' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ]);
        }



        if ($user->otp == $request->otp) {
            $user->status = 'active';
            $user->save();
            return response()->json([
                'status' => true,
                'message' => 'User Verified Successfully',
                'user_id' => $user->id
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP'
            ]);
        }
    }




    public function accountRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'paynest_id' => 'required|unique:accounts,paynest_id',
            'phone' => 'required|unique:accounts,phone|digits:11',
            'gender' => 'required',
            'gender' => 'required',
            'address' => 'required',
        ]);

        $user_id = $request->user_id;
        $user = User::where('id', $user_id)->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User Not Found'
            ]);
        }
        if ($user->status != 'active') {
            return response()->json([
                'status' => false,
                'message' => 'User Not Verified'
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ]);
        }

        $account = new Account();
        $account->user_id = $request->user_id;
        $account->paynest_id = 'paynest' . $request->paynest_id;
        $account->phone = $request->phone;
        $account->gender = $request->gender;
        $account->address = $request->address;
        $account->balance = 10000.00;
        $account->status = 'pending';
        $account->save();

        return response()->json([
            'status' => true,
            'message' => 'Account Registred Successfully',
            'account_id' => $account->id,
            'user_id' => $account->user_id
        ]);
    }


    public function Userlogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => [
                'required',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()
            ],
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ]);
        }
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User Not Found'
            ]);
        }


        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {

            if ($user->status != 'active') {
                return response()->json([
                    'status' => false,
                    'message' => 'User Not Verified'
                ]);
            }
            $token = $user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'status' => true,
                'message' => 'Login Success',
                'token' => $token,
                'user_id' => $user->id,
            ]);
        }

       
    }


    public function accountInfo($id)
    {
        $user = Account::with('user')->where('id', $id)->first();
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User Not Found'
            ]);
        }
        return response()->json([
            'status' => true,
            'message' => 'Account Info',
            'user' => $user,
        ]);
    }
}
