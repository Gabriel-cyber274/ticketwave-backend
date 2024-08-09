<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\verifyEmail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Laravel\Sanctum\PersonalAccessToken;


class UserController extends Controller
{
    //
    public function Login (Request $request) {
        $fields = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password'=> 'required|string|min:8',
        ]);
        
        if($fields->fails()) {
            $response = [
                'errors'=> $fields->errors(),
                'success' => false
            ];

            return response($response);
        }

        $user = User::where('email', $request->email)->first();

        if(!$user || !Hash::check($request->password, $user->password)) {
            return response([
                'message' => 'incorrect credentials',
                'success' => false
            ]);
        }
        // else if(is_null($user->email_verified_at)) {
        //     return response([
        //         'message' => 'email not verified',
        //         'success' => false
        //     ], 401);
        // }
        $token = $user->createToken('Personal Access Token', [])->plainTextToken;
        
            
        $response = [
            'user'=> $user,
            'token'=> $token,
            'message'=> 'logged in',
            'success' => true
        ];

        return response($response, 201);


    }

    public function Register (Request $request) {
        $fields = Validator::make($request->all(),[
            'fullname'=> 'required|string',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password'=> 'required|string|min:8',
            'password_confirmation'=> 'required|string|min:8|same:password'
        ]);

        if($fields->fails()) {
            $response = [
                'errors'=> $fields->errors(),
                'success' => false
            ];

            return response($response);
        }


        $user = User::create([
            'fullname'=> Str::title($request['fullname']),
            'email'=> $request['email'],
            'password' => bcrypt($request['password']),
            'agree' => $request['agree']
        ]);

        // $token = $user->createToken('Personal Access Token', [])->plainTextToken;
        $token = $user->createToken('Email Verification Token', ['*'], Carbon::now()->addMinutes(30))->plainTextToken;

        // Mail::to($user->email)->send(new VerifyEmail($user, $token));

        $user->notify(new verifyEmail($user, $token));

        $response = [
            'user'=> $user,
            'verify-token' => $token,
            'message'=> 'Successful signup. Please verify your email.',
            'success' => true
        ];

        return response($response);
    }


    // public function verifyEmail(Request $request) {
    //     $fields = Validator::make($request->all(), [
    //         // 'token' => 'required|string',
    //         'email' => 'required|string|email|max:255',
    //     ]);
    
    //     if ($fields->fails()) {
    //         return response([
    //             'errors' => $fields->errors(),
    //             'success' => false
    //         ], 422);
    //     }
    
    //     $user = User::where('email', $request->email)->first();
    
    //     if (!$user) {
    //         return response([
    //             'message' => "User doesn't exist",
    //             'success' => false
    //         ], 404);
    //     }
    
    //     if (!is_null($user->email_verified_at)) {
    //         return response([
    //             'message' => 'Email already verified',
    //             'success' => true
    //         ], 200);
    //     }
    
    //     // if ($this->isTokenValid($user, $request->token)) {
    //     if ($user) {
    //         $user->email_verified_at = now();
    //         $user->save();

    
    //         return response([
    //             'message' => 'Email successfully verified',
    //             'success' => true
    //         ], 200);
    //     }
        
        
    //     // else {
    //     //     return response([
    //     //         'message' => 'Invalid or expired token',
    //     //         'success' => false
    //     //     ], 400);
    //     // }
    // }


    public function verifyEmail(Request $request) {
        $fields = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|string|email|max:255',
        ]);
    
        if ($fields->fails()) {
            return response([
                'errors' => $fields->errors(),
                'success' => false
            ], 422);
        }
    
        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            return response([
                'message' => "User doesn't exist",
                'success' => false
            ], 404);
        }
    
        if ($user->email_verified_at) {
            return response([
                'message' => 'Email already verified',
                'success' => true
            ], 200);
        }
    
        $parts = explode('|', $request->token);
        if (count($parts) !== 2) {
            return response([
                'message' => 'Invalid token format',
                'success' => false
            ], 400);
        }
        $tokenId = $parts[0];
        $tokenPlainText = $parts[1];
    
        // Hash the plain text token
        $tokenHashed = hash('sha256', $tokenPlainText);
    
        // Find the token by ID and hashed value
        $token = PersonalAccessToken::where('id', $tokenId)->where('token', $tokenHashed)->first();
    
        if (!$token || $token->created_at->addMinutes(30)->isPast()) {
            return response([
                'message' => 'Invalid or expired token',
                'success' => false
            ], 400);
        }
    
        $user->email_verified_at = now();
        $user->save();
    
        $token->delete(); // Optional: Delete the token after verification
    
        return response([
            'message' => 'Email successfully verified',
            'success' => true
        ], 200);
    }
    

    public function resendVerificationEmail(Request $request) {
        $fields = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);
    
        if ($fields->fails()) {
            return response([
                'errors' => $fields->errors(),
                'success' => false
            ], 422);
        }
    
        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            return response([
                'message' => "User doesn't exist",
                'success' => false
            ], 404);
        }
    
        if ($user->email_verified_at) {
            return response([
                'message' => 'Email already verified',
                'success' => true
            ], 200);
        }
    
        $token = $user->createToken('Email Verification Token', ['*'], Carbon::now()->addMinutes(30))->plainTextToken;
    
        // Mail::to($user->email)->send(new VerifyEmail($user, $token));
        
        $user->notify(new verifyEmail($user, $token));
    
        return response([
            'message' => 'Verification email resent',
            'success' => true,
            'token' => $token
        ], 200);
    }
    
    
    public function checkEmail (Request $request) {
        $fields = Validator::make($request->all(),[
            'email' => 'required|string|email|max:255',
        ]);


        if($fields->fails()) {
            $response = [
                'errors'=> $fields->errors(),
                'success' => false
            ];

            return response($response);
        }

        $user = User::where('email', $request->email)->get()->first();
        
        $token = $user->createToken('Personal Access Token', [])->plainTextToken;

        if($user) {
            $response = [
                'email'=> $user->email,
                'token'=> $token,
                'message'=> 'user retrieved successfully',
                'success' => true
            ];
    
            return response($response);
        }else {
            $response = [
                'message'=> "user doesn't exist",
                'success' => false
            ];
    
            return response($response);
        }

    }

    public function ChangePassword(Request $request) {
        $fields = Validator::make($request->all(), [
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|string|min:8|same:password'
        ]);
    
        if ($fields->fails()) {
            $response = [
                'errors' => $fields->errors(),
                'success' => false
            ];
    
            return response($response);
        }
    
        $user = auth()->user();
    
        if ($user) {
            if (is_null($user->email_verified_at)) {
                return response([
                    'message' => 'Email address not verified',
                    'success' => false
                ], 403);
            }
    
            $mainUser = User::find($user->id);
            $mainUser->update([
                'password' => bcrypt($request['password']),
            ]);
    
            $response = [
                'user' => $mainUser,
                'message' => 'Password changed successfully',
                'success' => true
            ];
    
            return response($response);
        } else {
            $response = [
                'message' => "User doesn't exist",
                'success' => false
            ];
    
            return response($response);
        }
    }

    public function changeDetails(Request $request) {
        $fields = Validator::make($request->all(), [
            'fullname' => 'required|string',
        ]);
    
        if ($fields->fails()) {
            $response = [
                'errors' => $fields->errors(),
                'success' => false
            ];
    
            return response($response, 422);
        }
    
        try {
            $user = auth()->user();
    
            if (!$user) {
                return response([
                    'message' => 'User not authenticated',
                    'success' => false
                ], 401);
            }
    
            if (is_null($user->email_verified_at)) {
                return response([
                    'message' => 'Email address not verified',
                    'success' => false
                ], 403);
            }
    
            $mainUser = User::findOrFail($user->id);
    
            $mainUser->update([
                'fullname' => $request->fullname,
            ]);
    
            $response = [
                'user' => $mainUser,
                'message' => 'User details changed successfully',
                'success' => true
            ];
    
            return response($response, 200);
        } catch (\Exception $e) {
            return response([
                'message' => 'An error occurred while updating user details',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
    
    
    public function Logout (Request $request) {
        $request->user()->tokens()->delete();

        return [
            'message'=> 'logged out',
            'success' => true
        ];
    }

}
