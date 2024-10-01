<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\User;
use App\Notifications\VerifyAccount;
use App\Notifications\VerifyAdminAccount;
use App\Notifications\verifyEmail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\PersonalAccessToken;


class UserController extends Controller
{
    //
    public function Login(Request $request)
    {
        $fields = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($fields->fails()) {
            $response = [
                'errors' => $fields->errors(),
                'success' => false
            ];

            return response($response);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
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
            'user' => $user,
            'token' => $token,
            'message' => 'logged in',
            'success' => true
        ];

        return response($response, 201);
    }

    public function Register(Request $request)
    {
        $fields = Validator::make($request->all(), [
            // 'fullname'=> 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'password_confirmation' => 'required|string|min:8|same:password',
            'profile_pic' => 'nullable',
            'bank' => 'nullable',
            'acc_no' => 'nullable'
        ]);

        if ($fields->fails()) {
            $response = [
                'errors' => $fields->errors(),
                'success' => false
            ];

            return response($response);
        }

        $imageUrl = null;
        if ($request->hasFile('profile_pic')) {
            $image = $request->file('profile_pic');
            $imagePath = $image->store('users', 'public');
            $filename = basename($imagePath);

            // Generate the API URL for the image using your custom route
            $imageUrl = route('userimg.get', ['filename' => $filename]);
        }


        $user = User::create([
            'first_name' => Str::title($request['first_name']),
            'last_name' => Str::title($request['last_name']),
            'fullname' => Str::title($request['last_name']) . ' ' . Str::title($request['first_name']),
            'email' => $request['email'],
            'password' => bcrypt($request['password']),
            'agree' => $request['agree'],
            'admin' => false,
            'profile_pic' => $imageUrl,
            'bank' => $request['bank'],
            'acc_no' => $request['acc_no']
        ]);

        // $token = $user->createToken('Personal Access Token', [])->plainTextToken;
        $token = $user->createToken('Email Verification Token', ['*'], Carbon::now()->addMinutes(30))->plainTextToken;

        // Mail::to($user->email)->send(new VerifyEmail($user, $token));

        $user->notify(new verifyEmail($user, $token));

        $adminUsers = User::where('admin', true)->get();

        foreach ($adminUsers as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title' => 'New Registration',
                'is_read' => false,
                'customer_id' => $user->id,
                'description' => $user->fullname . 'created an account',
            ]);
        }

        $response = [
            'user' => $user,
            'verify-token' => $token,
            'message' => 'Successful signup. Please verify your email.',
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


    public function verifyEmail(Request $request)
    {
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


    public function resendVerificationEmail(Request $request)
    {
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


        $user->notify(new verifyEmail($user, $token));

        return response([
            'message' => 'Verification email resent',
            'success' => true,
            'token' => $token
        ], 200);
    }

    public function checkEmail(Request $request)
    {
        // Validate the request
        $fields = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
        ]);

        if ($fields->fails()) {
            return response([
                'errors' => $fields->errors(),
                'success' => false,
            ]);
        }

        // Retrieve the user
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response([
                'message' => "User doesn't exist",
                'success' => false,
            ]);
        }

        // Create token and notify based on user role
        try {
            $token = $user->createToken('Personal Access Token')->plainTextToken;
            if ($user->admin) {
                // $user->notify(new VerifyAdminAccount($user, $token));
            } else {
                $user->notify(new VerifyAccount($user, $token));
            }

            return response([
                'email' => $user->email,
                'token' => $token,
                'message' => 'User retrieved successfully',
                'success' => true,
            ]);
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ]);
        }
    }


    public function ChangePassword(Request $request)
    {
        $fields = Validator::make($request->all(), [
            'email' => 'required',
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

        // $user = auth()->user();
        $user = User::where('email', $request->email)->get()->first();

        if ($user) {
            // if (is_null($user->email_verified_at)) {
            //     return response([
            //         'message' => 'Email address not verified',
            //         'success' => false
            //     ]);
            // }

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


    public function changeDetails(Request $request)
    {
        // Validation for user details, excluding password
        $fields = Validator::make($request->all(), [
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|string|email|max:255|unique:users,email,' . auth()->id(), // Ignore current user's email
            'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'bank' => 'nullable',
            'acc_no' => 'nullable'
        ]);

        // Handle validation errors
        if ($fields->fails()) {
            return response([
                'errors' => $fields->errors(),
                'success' => false
            ], 422);
        }

        try {
            // Get the authenticated user
            $user = auth()->user();

            if (!$user) {
                return response([
                    'message' => 'User not authenticated',
                    'success' => false
                ], 401);
            }

            $mainUser = User::findOrFail($user->id);

            $imageUrl = $mainUser->profile_pic;

            if ($request->hasFile('profile_pic')) {
                if ($mainUser->profile_pic) {
                    $oldImagePath = str_replace(route('userimg.get', ['filename' => '']), '', $mainUser->profile_pic);

                    Storage::disk('public')->delete('users/' . $oldImagePath);
                }

                $image = $request->file('profile_pic');
                $imagePath = $image->store('users', 'public');
                $filename = basename($imagePath);

                // Generate the API URL for the new image
                $imageUrl = route('userimg.get', ['filename' => $filename]);
            }

            // Update user details
            $mainUser->update([
                'first_name' => Str::title($request->first_name),
                'last_name' => Str::title($request->last_name),
                'fullname' => Str::title($request->first_name) . ' ' . Str::title($request->last_name),
                'email' => $request->email,
                'profile_pic' => $imageUrl,
                'bank' => Str::title($request->bank),
                'acc_no' => $request->acc_no
            ]);

            // Response
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


    public function userDetails()
    {
        $meId = Auth()->id();

        try {
            $user = User::with(['event', 'registrations'])->findOrFail($meId);

            return response([
                'user' => $user,
                'message' => 'user info retrieved successfully',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }

    public function allAdminRevenue()
    {
        $amount = User::sum('account_balance');

        $users = User::all();

        return response([
            'users' => $users,
            'total_balance' => $amount,
            'message' => 'volunteer deleted successfully',
            'success' => true,
        ], 200);
    }

    public function adminRevenueByDate($date)
    {
        $users = User::with(['event', 'registrations'])
            ->whereDate('created_at', $date)
            ->get();

        $totalBalance = $users->sum('account_balance');

        return response([
            'users' => $users,
            'total_balance' => $totalBalance,
            'message' => 'Revenue and users fetched successfully',
            'success' => true,
        ], 200);
    }


    public function allUsers()
    {
        $users = User::with(['event', 'registrations'])->where('admin', false)->get();

        return response([
            'users' => $users,
            'total' => count($users),
            'message' => 'volunteer deleted successfully',
            'success' => true,
        ], 200);
    }


    public function sortUsersByDate($date)
    {
        $users = User::with(['event', 'registrations'])->where('admin', false)
            ->whereDate('created_at', $date)
            ->get();

        return response([
            'users' => $users,
            'total' => count($users),
            'message' => 'Users created on ' . $date . ' fetched successfully',
            'success' => true,
        ], 200);
    }



    public function viewUser($id)
    {
        try {
            $user = User::findorfail($id);

            return response([
                'user' => $user,
                'message' => 'User retrieved successfully',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }




    public function Logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return [
            'message' => 'logged out',
            'success' => true
        ];
    }
}
