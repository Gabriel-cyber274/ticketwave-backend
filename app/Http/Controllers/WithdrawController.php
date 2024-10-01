<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use App\Models\Withdraw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;


class WithdrawController extends Controller
{
    // Display a listing of the resource.
    public function index()
    {
        $withdrawals = Withdraw::where('user_id', Auth::id())->get();

        return response()->json([
            'withdrawals' => $withdrawals,
            'message' => 'Withdrawals retrieved successfully.',
            'success' => true,
        ], 200);
    }


    public function store(Request $request)
    {
        // Validate the request
        $fields = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
        ]);

        // Handle validation errors
        if ($fields->fails()) {
            return response()->json([
                'errors' => $fields->errors(),
                'success' => false,
            ], 422);
        }

        // Retrieve the authenticated user
        $user = Auth::user();

        // Check if the user has sufficient balance
        if ($user->account_balance >= $request->amount) {
            // Create a new withdrawal
            $withdraw = Withdraw::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'is_accepted' => false,
            ]);

            $user->decrement('account_balance', $request->amount);

            // Create a notification for the user
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Withdrawal Request',
                'is_read' => false,
                'description' => 'Your withdrawal request of #' . $request->amount . ' has been sent to admin for reviewing and will be processed as soon as possible.',
            ]);

            // Notify all admin users about the withdrawal request
            $adminUsers = User::where('admin', true)->get();
            foreach ($adminUsers as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'title' => 'Withdrawal Pending',
                    'is_read' => false,
                    'withdraw_id' => $withdraw->id,
                    'description' => $user->fullname . ' has requested a withdrawal of #' . $request->amount . ' from their earnings.',
                ]);
            }

            // Send email notification to the user
            Mail::send([], [], function ($message) use ($user, $withdraw) {
                $message->to($user->email)
                    ->subject('Withdrawal Request Notification')
                    ->setBody('
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                            .container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
                            h2 { color: #333; }
                            p { color: #555; line-height: 1.6; }
                            .footer { margin-top: 20px; font-size: 12px; color: #aaa; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <h2>Withdrawal Request Submitted</h2>
                            <p>Dear ' . $user->fullname . ',</p>
                            <p>Your withdrawal request of <strong>#' . $withdraw->amount . '</strong> has been successfully submitted and is currently pending admin review. We will notify you once your request has been processed.</p>
                            <p>Thank you for your patience!</p>
                            <p>Regards,<br>Your Company Team</p>
                            <div class="footer">
                                <p>This is an automated message. Please do not reply.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ', 'text/html');
            });

            // Send email notification to each admin
            foreach ($adminUsers as $admin) {
                Mail::send([], [], function ($message) use ($admin, $user, $withdraw) {
                    $message->to($admin->email)
                        ->subject('New Withdrawal Request')
                        ->setBody('
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                                .container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
                                h2 { color: #333; }
                                p { color: #555; line-height: 1.6; }
                                .footer { margin-top: 20px; font-size: 12px; color: #aaa; }
                            </style>
                        </head>
                        <body>
                            <div class="container">
                                <h2>New Withdrawal Request</h2>
                                <p>Dear Admin,</p>
                                <p>User <strong>' . $user->fullname . '</strong> has requested a withdrawal of <strong>#' . $withdraw->amount . '</strong>. Please review the request at your earliest convenience.</p>
                                <p>Thank you!</p>
                                <p>Regards,<br>Your Company Team</p>
                                <div class="footer">
                                    <p>This is an automated message. Please do not reply.</p>
                                </div>
                            </div>
                        </body>
                        </html>
                        ', 'text/html');
                });
            }

            return response()->json([
                'withdraw' => $withdraw,
                'message' => 'Withdrawal request sent successfully.',
                'success' => true,
            ], 200);
        } else {
            // Insufficient balance
            return response()->json([
                'message' => 'Insufficient balance to withdraw this amount.',
                'success' => false,
            ], 200);
        }
    }


    public function show($id)
    {
        try {
            $withdraw = Withdraw::with('user')->findOrFail($id);

            return response()->json([
                'withdraw' => $withdraw,
                'message' => 'Single withdrawal retrieved successfully.',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }

    public function updateAmount(Request $request, $id)
    {
        $fields = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
        ]);

        // Handle validation errors
        if ($fields->fails()) {
            return response()->json([
                'errors' => $fields->errors(),
                'success' => false,
            ], 422);
        }

        try {
            $withdraw = Withdraw::findOrFail($id);

            $withdraw->update([
                'amount' => $request->amount,
            ]);

            return response()->json([
                'withdraw' => $withdraw,
                'message' => 'Withdrawal updated successfully.',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }

    public function approveWithdrawal($id)
    {
        try {
            $withdraw = Withdraw::findOrFail($id);

            $withdraw->update([
                'is_accepted' => true,
            ]);

            $user = User::find($withdraw->user_id);

            // Create a notification for the user
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Withdrawal Success',
                'is_read' => false,
                'description' => 'Your withdrawal request of #' . $withdraw->amount . ' has been paid to you.',
            ]);

            // Send email notification to the user
            Mail::send([], [], function ($message) use ($user, $withdraw) {
                $message->to($user->email)
                    ->subject('Withdrawal Approved')
                    ->setBody('
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                            .container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
                            h2 { color: #333; }
                            p { color: #555; line-height: 1.6; }
                            .footer { margin-top: 20px; font-size: 12px; color: #aaa; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <h2>Withdrawal Approved</h2>
                            <p>Dear ' . $user->fullname . ',</p>
                            <p>Your withdrawal request of <strong>#' . $withdraw->amount . '</strong> has been successfully processed and paid to you.</p>
                            <p>Thank you for your patience!</p>
                            <p>Regards,<br>Your Company Team</p>
                            <div class="footer">
                                <p>This is an automated message. Please do not reply.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ', 'text/html');
            });

            // Notify all admin users about the withdrawal request
            $adminUsers = User::where('admin', true)->get();
            foreach ($adminUsers as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'title' => 'Withdrawal Processed',
                    'is_read' => false,
                    'withdraw_id' => $withdraw->id,
                    'description' => $user->fullname . ' has successfully been paid the sum of #' . $withdraw->amount . ' to ' . $user->bank . ' after approval.',
                ]);

                // Send email notification to each admin
                Mail::send([], [], function ($message) use ($admin, $user, $withdraw) {
                    $message->to($admin->email)
                        ->subject('Withdrawal Processed')
                        ->setBody('
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                                .container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
                                h2 { color: #333; }
                                p { color: #555; line-height: 1.6; }
                                .footer { margin-top: 20px; font-size: 12px; color: #aaa; }
                            </style>
                        </head>
                        <body>
                            <div class="container">
                                <h2>Withdrawal Processed</h2>
                                <p>Dear Admin,</p>
                                <p>User <strong>' . $user->fullname . '</strong> has been paid a withdrawal of <strong>#' . $withdraw->amount . '</strong>. Please update your records accordingly.</p>
                                <p>Thank you!</p>
                                <p>Regards,<br>Your Company Team</p>
                                <div class="footer">
                                    <p>This is an automated message. Please do not reply.</p>
                                </div>
                            </div>
                        </body>
                        </html>
                        ', 'text/html');
                });
            }

            return response()->json([
                'withdraw' => $withdraw,
                'message' => 'Withdrawal updated successfully.',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }

    public function disapproveWithdrawal($id)
    {
        try {
            $withdraw = Withdraw::findOrFail($id);

            $withdraw->update([
                'is_accepted' => false,
            ]);

            $user = User::find($withdraw->user_id);

            // Create a notification for the user
            Notification::create([
                'user_id' => $user->id,
                'title' => 'Withdrawal Failed',
                'is_read' => false,
                'description' => 'Your withdrawal request of #' . $withdraw->amount . ' had some issues; contact support to fix it.',
            ]);

            // Send email notification to the user
            Mail::send([], [], function ($message) use ($user, $withdraw) {
                $message->to($user->email)
                    ->subject('Withdrawal Request Disapproved')
                    ->setBody('
                    <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                            .container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
                            h2 { color: #333; }
                            p { color: #555; line-height: 1.6; }
                            .footer { margin-top: 20px; font-size: 12px; color: #aaa; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <h2>Withdrawal Request Disapproved</h2>
                            <p>Dear ' . $user->fullname . ',</p>
                            <p>We regret to inform you that your withdrawal request of <strong>#' . $withdraw->amount . '</strong> could not be processed due to some issues. Please contact support for assistance.</p>
                            <p>Thank you for your understanding!</p>
                            <p>Regards,<br>Your Company Team</p>
                            <div class="footer">
                                <p>This is an automated message. Please do not reply.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ', 'text/html');
            });

            // Notify all admin users about the withdrawal request
            $adminUsers = User::where('admin', true)->get();
            foreach ($adminUsers as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'title' => 'Withdrawal Not Processed',
                    'is_read' => false,
                    'withdraw_id' => $withdraw->id,
                    'description' => $user->fullname . ' has not been paid the sum of #' . $withdraw->amount . ' to ' . $user->bank . ' after disapproval.',
                ]);

                // Send email notification to each admin
                Mail::send([], [], function ($message) use ($admin, $user, $withdraw) {
                    $message->to($admin->email)
                        ->subject('Withdrawal Request Disapproved')
                        ->setBody('
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                                .container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
                                h2 { color: #333; }
                                p { color: #555; line-height: 1.6; }
                                .footer { margin-top: 20px; font-size: 12px; color: #aaa; }
                            </style>
                        </head>
                        <body>
                            <div class="container">
                                <h2>Withdrawal Request Disapproved</h2>
                                <p>Dear Admin,</p>
                                <p>User <strong>' . $user->fullname . '</strong> has had their withdrawal request of <strong>#' . $withdraw->amount . '</strong> disapproved. Please check the reasons for disapproval and follow up as necessary.</p>
                                <p>Thank you!</p>
                                <p>Regards,<br>Your Company Team</p>
                                <div class="footer">
                                    <p>This is an automated message. Please do not reply.</p>
                                </div>
                            </div>
                        </body>
                        </html>
                        ', 'text/html');
                });
            }

            return response()->json([
                'withdraw' => $withdraw,
                'message' => 'Withdrawal updated successfully.',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }


    // Remove the specified resource from storage.
    public function destroy($id)
    {
        try {
            $withdraw = Withdraw::with(['user'])->findOrFail($id);

            $withdraw->delete();

            return response()->json([
                'message' => 'Withdrawal deleted successfully.',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }
}
