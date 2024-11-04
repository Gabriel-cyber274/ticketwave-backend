<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Event;
use App\Models\EventCost;
use App\Models\Notification;
use App\Models\Register;
use App\Models\User;
use Illuminate\Support\Str;
use App\Models\withdraw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;



class RegisterController extends Controller
{
    //

    public function GetAllMyRegistrations()
    {
        $meId = Auth()->id();
        $registration = Register::with(['user', 'event'])->where('user_id', $meId)->get();
        $sortedReg = collect($registration)->sortByDesc('id');
        $finalL = $sortedReg->values()->all();



        return response([
            'registration' => $finalL,
            'message' => 'registration retrieved successfully',
            'success' => true,
        ], 200);
    }

    public function allRegistrations()
    {
        $registration = Register::with(['user', 'event'])->get();
        $sortedReg = collect($registration)->sortByDesc('id');
        $finalL = $sortedReg->values()->all();



        return response([
            'registration' => $finalL,
            'message' => 'registration retrieved successfully',
            'success' => true,
        ], 200);
    }

    public function registrationSorbByName()
    {
        $registration = Register::with(['user', 'event'])->get();

        $sortedReg = $registration->sortBy(function ($register) {
            return $register->user->fullname;
        });

        // Reset the collection keys after sorting
        $finalL = $sortedReg->values()->all();

        // Return the sorted data in the response
        return response([
            'registration' => $finalL,
            'message' => 'registration sorted by user fullname successfully',
            'success' => true,
        ], 200);
    }


    public function registrationSorbByOrderId()
    {
        $registration = Register::with(['user', 'event'])->get();

        $sortedReg = $registration->sortBy(function ($register) {
            return $register->reference;
        });

        // Reset the collection keys after sorting
        $finalL = $sortedReg->values()->all();

        // Return the sorted data in the response
        return response([
            'registration' => $finalL,
            'message' => 'registration sorted by user order_id successfully',
            'success' => true,
        ], 200);
    }



    public function registrationSorbByFee()
    {
        $registration = Register::with(['user', 'event'])->get();

        $sortedReg = $registration->sortBy(function ($register) {
            return $register->ticket_cost * $register->ticket_quantity;
        });

        $finalL = $sortedReg->values()->all();

        // Return the sorted data in the response
        return response([
            'registration' => $finalL,
            'message' => 'Registration sorted by total ticket cost successfully',
            'success' => true,
        ], 200);
    }


    public function filterOrders($param)
    {
        // Fetch the registrations with filters for user fullname, order ID (reference), or ticket fee
        $registration = Register::with(['user', 'event'])
            ->whereHas('user', function ($query) use ($param) {
                $query->where('fullname', 'like', '%' . $param . '%'); // Filter by user's full name
            })
            ->orWhere('reference', 'like', '%' . $param . '%') // Filter by order ID (reference)
            ->orWhereRaw('ticket_cost * ticket_quantity like ?', ['%' . $param . '%']) // Filter by total ticket cost
            ->get();

        // Return the filtered data in the response
        return response([
            'registration' => $registration,
            'message' => 'Registrations filtered successfully',
            'success' => true,
        ], 200);
    }






    public function Register(Request $request)
    {
        $fields = Validator::make($request->all(), [
            'register.*.event_id' => 'required|numeric',
            'register.*.ticket_type' => 'required|string',
            'register.*.ticket_quantity' => 'required|numeric',
            'register.*.ticket_cost' => 'required|numeric',
            'register.*.reference' => 'required|string',
            'register.*.transaction' => 'required|string',
        ]);

        if ($fields->fails()) {
            return response([
                'errors' => $fields->errors(),
                'success' => false
            ], 400);
        }

        $meId = Auth()->id();
        $registerData = $request->register;

        // Start database transaction
        DB::beginTransaction();

        try {
            foreach ($registerData as $data) {
                $randomString = Str::random(4);

                $register = Register::create([
                    'user_id' => $meId,
                    'event_id' => $data['event_id'],
                    'ticket_type' => $data['ticket_type'],
                    'ticket_quantity' => $data['ticket_quantity'],
                    'ticket_cost' => $data['ticket_cost'],
                    'reference' => $data['reference'],
                    'transaction' => $data['transaction'],
                ]);

                $register->update([
                    'ticket_code' => 'ticket_' . $randomString . $register->id
                ]);

                $event = Event::findOrFail($data['event_id']);
                $host = User::findOrFail($event->user_id);
                $me = User::findOrFail($meId);
                $myInfo = User::findOrFail($meId);

                $cost = EventCost::where('event_id', $data['event_id'])->where('level', $data['ticket_type'])->get()->first();

                $cost->decrement('available', $data['ticket_quantity']);

                Deposit::create([
                    'user_id' => $event->user_id,
                    'amount' => $data['ticket_cost'] * $data['ticket_quantity']
                ]);


                $host->increment('account_balance', $data['ticket_cost'] * $data['ticket_quantity']);


                // Generate QR code URL
                $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($register->ticket_code);


                // Send email to the purchaser
                Mail::send([], [], function ($message) use ($me, $register, $event, $qrCodeUrl) {
                    $message->to($me->email)
                        ->subject('Your Ticket Purchase Confirmation')
                        ->html('
                <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; color: #333; }
                            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px; background-color: #f9f9f9; }
                            .header { text-align: center; margin-bottom: 20px; }
                            .header h2 { color: #4CAF50; }
                            .details { margin-bottom: 20px; }
                            .details p { margin: 5px 0; }
                            .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                            .qr-code { text-align: center; margin-top: 20px; }
                        </style>
                    </head>
                    <body>
                        <div class="container">
                            <div class="header">
                                <h2>Ticket Purchase Confirmation</h2>
                            </div>
                            <div class="details">
                                <p>Dear ' . htmlspecialchars($me->last_name) . ',</p>
                                <p>Thank you for your purchase! Here are your ticket details:</p>
                                <p><strong>Event:</strong> ' . htmlspecialchars($event->event_title) . '</p>
                                <p><strong>Ticket Type:</strong> ' . htmlspecialchars($register->ticket_type) . '</p>
                                <p><strong>Ticket Quantity:</strong> ' . htmlspecialchars($register->ticket_quantity) . '</p>
                                <p><strong>Ticket Cost:</strong> #' . htmlspecialchars($register->ticket_cost * $register->ticket_quantity) . '</p>
                                <p><strong>Ticket Code:</strong> ' . htmlspecialchars($register->ticket_code) . '</p>
                                <div class="qr-code">
                                    <img src="' . $qrCodeUrl . '" alt="QR Code" />
                                </div>
                                <p>We hope you enjoy the event!</p>
                            </div>
                            <div class="footer">
                                <p>Best regards,</p>
                                <p>The TicketWave Team</p>
                            </div>
                        </div>
                    </body>
                </html>
            ');
                });


                // Send email notification to the event host
                Mail::send([], [], function ($message) use ($host, $me, $register, $event) {
                    $message->to($host->email)
                        ->subject('New Ticket Purchase Notification')
                        ->html('
                            <html>
                                <head>
                                    <style>
                                        body { font-family: Arial, sans-serif; color: #333; }
                                        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e0e0e0; border-radius: 5px; background-color: #f9f9f9; }
                                        .header { text-align: center; margin-bottom: 20px; }
                                        .header h2 { color: #4CAF50; }
                                        .details { margin-bottom: 20px; }
                                        .details p { margin: 5px 0; }
                                        .footer { text-align: center; margin-top: 20px; font-size: 0.9em; color: #666; }
                                    </style>
                                </head>
                                <body>
                                    <div class="container">
                                        <div class="header">
                                            <h2>New Ticket Purchase Notification</h2>
                                        </div>
                                        <div class="details">
                                            <p>Dear ' . $host->last_name . ',</p>
                                            <p>A ticket has been purchased for your event:</p>
                                            <p><strong>Event:</strong> ' . $event->event_title . '</p>
                                            <p><strong>Purchased By:</strong> ' . $me->first_name . ' ' . $me->last_name . '</p>
                                            <p><strong>Ticket Type:</strong> ' . $register->ticket_type . '</p>
                                            <p><strong>Ticket Quantity:</strong> ' . $register->ticket_quantity . '</p>
                                            <p><strong>Total Cost:</strong> #' . ($register->ticket_cost * $register->ticket_quantity) . '</p>
                                            <p>Please check your dashboard for more details.</p>
                                        </div>
                                        <div class="footer">
                                            <p>Best regards,</p>
                                            <p>The TicketWave Team</p>
                                        </div>
                                    </div>
                                </body>
                            </html>
                        ');
                });



                Notification::create([
                    'user_id' => $meId,
                    'title' => 'Ticket Purchased',
                    'is_read' => false,
                    'description' => 'You have successfully purchased “' . $event->event_title . ' Ticket ' . $data['ticket_quantity'] . ' for a sum of #' . $data['ticket_cost'] * $data['ticket_quantity'],
                ]);



                Notification::create([
                    'is_read' => false,
                    'user_id' => $event->user_id,
                    'title' => 'Ticket Purchased',
                    'description' => $myInfo->fullname . ' has successfully purchased “' . $event->event_title . ' Ticket ' . $data['ticket_quantity'] . ' for a sum of #' . $data['ticket_cost'] * $data['ticket_quantity'],
                ]);
            }

            DB::commit();

            return response([
                'message' => 'Registered successfully',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 500);
        }
    }




    public function totalTicketSold()
    {
        $registrations = Register::with(['user', 'event'])->get();



        return response([
            'registration' => $registrations,
            'total' => count($registrations),
            'message' => 'all ticket sold',
            'success' => true,
        ], 200);
    }

    public function totalTicketSoldByDate($date)
    {
        $registrations = Register::with(['user', 'event'])->whereDate('created_at', $date)->get();

        return response([
            'registrations' => $registrations,
            'total' => $registrations->count(),
            'message' => 'Total tickets sold on ' . $date . ' retrieved successfully',
            'success' => true,
        ], 200);
    }





    function getMonthlyRegistrations($year)
    {
        $monthlyRegistrations = Register::selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Initialize an array for all months
        $registrationsPerMonth = array_fill(1, 12, 0); // From Jan (1) to Dec (12)

        // Populate the array with actual data
        foreach ($monthlyRegistrations as $registration) {
            $registrationsPerMonth[$registration->month] = $registration->total;
        }

        return response([
            'year' => $year,
            'registrations_per_month' => $registrationsPerMonth,
            'message' => 'Registrations fetched successfully',
            'success' => true,
        ], 200);
    }



    public function usersWithTicketSold()
    {
        // Fetch all users who have created events, including trashed events and their registrations
        $users = User::with(['event' => function ($query) {
            $query->withTrashed()->with('registrations'); // Include trashed events and their registrations
        }])->where('admin', false)->orderBy('id', 'desc')->get();

        $userData = [];

        foreach ($users as $user) {
            $eventsData = [];
            $totalSold = 0; // Initialize total registrations for the user

            if ($user->events && is_iterable($user->events)) {
                foreach ($user->events as $event) {
                    // Count the total registrations for this event
                    $registrationCount = $event->registrations->count();

                    // Append event data
                    $eventsData[] = [
                        'event_id' => $event->id,
                        'event_name' => $event->event_title,
                        'total_registrations' => $registrationCount,
                        'trashed' => $event->trashed(), // Add a field to indicate if the event is trashed
                    ];

                    // Accumulate total registrations across all events
                    $totalSold += $registrationCount;
                }
            }

            // Fetch and sum accepted withdrawals for the user
            $withdrawals = Withdraw::where('user_id', $user->id)->where('is_accepted', true)->sum('amount');

            // Append user data
            $userData[] = [
                'user' => $user,
                'events' => $eventsData,
                'totalEvents' => count($eventsData),
                'totalSold' => $totalSold,
                'withdraw_total' => $withdrawals ?? 0,  // Default to 0 if no withdrawals exist
            ];
        }

        return response([
            'users' => $userData,
            'message' => 'Users with their events and registration counts retrieved successfully',
            'success' => true,
        ], 200);
    }



    public function usersWithTicketSoldSortByName()
    {
        $users = User::with(['event' => function ($query) {
            $query->withTrashed()->with('registrations'); // Include trashed events and their registrations
        }])->where('admin', false)->get();

        $sortedUsers = $users->sortBy('fullname');

        $userData = [];

        foreach ($sortedUsers as $user) {
            $eventsData = [];
            $totalSold = 0;

            if ($user->events && is_iterable($user->events)) {
                foreach ($user->events as $event) {
                    $registrationCount = $event->registrations->count();

                    // Append event data
                    $eventsData[] = [
                        'event_id' => $event->id,
                        'event_name' => $event->event_title,
                        'total_registrations' => $registrationCount,
                        'trashed' => $event->trashed(), // Indicate if the event is trashed
                    ];

                    $totalSold += $registrationCount;
                }
            }
            // Fetch and sum accepted withdrawals for the user
            $withdrawals = Withdraw::where('user_id', $user->id)->where('is_accepted', true)->sum('amount');

            // Append user data
            $userData[] = [
                'user' => $user,
                'events' => $eventsData,
                'totalEvents' => count($eventsData),
                'totalSold' => $totalSold,
                'withdraw_total' => $withdrawals ?? 0,  // Default to 0 if no withdrawals exist
            ];
        }

        return response([
            'users' => $userData,
            'message' => 'Users with their events and registration counts retrieved successfully, sorted by name',
            'success' => true,
        ], 200);
    }



    public function usersWithTicketSoldSortByTicketSold()
    {
        $users = User::with(['event' => function ($query) {
            $query->withTrashed()->with('registrations'); // Include trashed events and their registrations
        }])->where('admin', false)->get();

        $userData = [];

        foreach ($users as $user) {
            $eventsData = [];
            $totalSold = 0;

            if ($user->events && is_iterable($user->events)) {
                foreach ($user->events as $event) {
                    $registrationCount = $event->registrations->count();

                    // Append event data
                    $eventsData[] = [
                        'event_id' => $event->id,
                        'event_name' => $event->event_title,
                        'total_registrations' => $registrationCount,
                    ];

                    $totalSold += $registrationCount;
                }
            }
            $withdrawals = Withdraw::where('user_id', $user->id)->where('is_accepted', true)->sum('amount');

            // Append user data
            $userData[] = [
                'user' => $user,
                'events' => $eventsData,
                'totalEvents' => count($eventsData),
                'totalSold' => $totalSold,
                'withdraw_total' => $withdrawals ?? 0,  // Default to 0 if no withdrawals exist
            ];
        }

        // Sort the users by 'totalSold' in descending order
        $sortedUserData = collect($userData)->sortByDesc('totalSold')->values()->all();

        return response([
            'users' => $sortedUserData,
            'message' => 'Users with their events and ticket sales sorted by tickets sold',
            'success' => true,
        ], 200);
    }


    public function usersWithTicketSoldSortByHighestAmount()
    {
        $users = User::with(['event' => function ($query) {
            $query->withTrashed()->with('registrations'); // Include trashed events and their registrations
        }])->where('admin', false)->get();

        // Sort users by 'account_balance' in descending order (highest first)
        $sortedUsers = $users->sortByDesc('account_balance');

        $userData = [];

        foreach ($sortedUsers as $user) {
            $eventsData = [];
            $totalSold = 0;

            if ($user->events && is_iterable($user->events)) {
                foreach ($user->events as $event) {
                    $registrationCount = $event->registrations->count();

                    // Append event data
                    $eventsData[] = [
                        'event_id' => $event->id,
                        'event_name' => $event->event_title,
                        'total_registrations' => $registrationCount,
                    ];

                    $totalSold += $registrationCount;
                }
            }
            $withdrawals = Withdraw::where('user_id', $user->id)->where('is_accepted', true)->sum('amount');

            // Append user data
            $userData[] = [
                'user' => $user,
                'events' => $eventsData,
                'totalEvents' => count($eventsData),
                'totalSold' => $totalSold,
                'account_balance' => $user->account_balance,
                'withdraw_total' => $withdrawals ?? 0,  // Default to 0 if no withdrawals exist
            ];
        }

        return response([
            'users' => $userData,
            'message' => 'Users with their events and registration counts retrieved successfully, sorted by highest account balance',
            'success' => true,
        ], 200);
    }
}
