<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Jobs\SendOtpJob;
use App\Models\Account;
use App\Models\User;
use App\Services\TwilioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AccountCreationController extends Controller
{
    protected $twilioService;

    public function __construct(TwilioService $twilioService)
    {
        $this->twilioService = $twilioService;
    }
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
                'otp' => 'OTP sent successfully! Plesase Verify',
                'user_id' => $user->id
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

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'otp' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ]);
        }


        $user = User::find($request->user_id);



        if ($user->otp == $request->otp) {
            $user->status = 'active';
            $user->otp = null;
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
            'phone' => 'required|unique:accounts,phone|digits:10',
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

        $otp = rand(1000, 9999);
        $account = new Account();
        $account->user_id = $request->user_id;
        $account->paynest_id = 'paynest@' . $request->paynest_id;
        $account->phone = $request->phone;
        $account->gender = $request->gender;
        $account->address = $request->address;
        $account->otp = $otp;
        $account->balance = 10000.00;
        $account->status = 'active';
        $account->save();

        if ($account) {
            $to = '+92' . $request->phone;
            $message = "Your OTP verification code is {$otp}. Do not share it with others.";
            $messageSid = $this->twilioService->sendMessage($to, $message);
        }


        return response()->json([
            'status' => true,
            'message' => 'Account Registred Successfully',
            'account_id' => $account->id,
            'user_id' => $account->user_id
        ]);
    }

    public function verifyPhoneOtp(Request $request, $id)
    {

        $account = Account::where('id', $id)->first();
        if (!$account) {
            return response()->json([
                'status' => false,
                'message' => 'Account Not Found'
            ]);
        }

        $validator = Validator::make($request->all(), [
            'otp' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(
                [
                    'status' => false,
                    'message' => $validator->errors()
                ]
            );
        }

        $otp = $request->otp;

        if ($otp == $account->otp) {
            $account->status = 'active';
            $account->save();
            return response()->json([
                'status' => true,
                'message' => 'Phone Number Verified Successfully',
                'account_id' => $account->id,
                'user_id' => $account->user_id
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP'
            ]);
        }
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
                'message' => 'User Not Found ! Enter Correct Email'
            ]);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $account = Account::with('user')->where('user_id', $user->id)->get();

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
                'account' => $account,
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Invalid credentials',
        ]);
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
        if ($user->status == 'pending') {
            return response()->json([
                'status' => false,
                'message' => 'Please verify account first'
            ]);
        }
        if ($user->status == 'blocked') {
            return response()->json([
                'status' => false,
                'message' => 'Your account has been blocked.Conact admin'
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Account Info',
            'user' => $user,
        ]);
    }

    public function edit($id)
    {
        $user = Account::with('user')->where('id', $id)->first(); // Assuming one-to-one relationship

        $user = Account::with('user')->where('id', $id)->first();

        return response()->json([
            'status' => true,
            'message' => 'Account found',
            'first_name' => $user->user->first_name,
            'last_name' => $user->user->last_name,
            'gender' => $user->gender,
            'address' => $user->address,
        ]);
    }

    public function updateProfile(Request $request, $id)
    {
        $user = User::with('account')->findOrFail($id);

        $user->update([
            'first_name' => $request->filled('first_name') ? $request->first_name : $user->first_name,
            'last_name'  => $request->filled('last_name')  ? $request->last_name  : $user->last_name,
        ]);


        $user->account->update([
            'gender'  => $request->filled('gender')  ? $request->gender  : $user->account->gender,
            'address' => $request->filled('address') ? $request->address : $user->account->address,
        ]);


        return response()->json([
            'status' => true,
            'message' => 'Record updated successfully',
        ]);
    }

    public function updatePassword(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::findOrFail($id);

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Password updated successfully',
        ]);
    }
}
