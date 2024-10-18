<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\Event;
use App\Models\EventCost;
use App\Models\Notification;
use App\Models\Register;
use App\Models\User;
use App\Models\withdraw;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;


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
                Register::create([
                    'user_id' => $meId,
                    'event_id' => $data['event_id'],
                    'ticket_type' => $data['ticket_type'],
                    'ticket_quantity' => $data['ticket_quantity'],
                    'ticket_cost' => $data['ticket_cost'],
                    'reference' => $data['reference'],
                    'transaction' => $data['transaction'],
                ]);

                $event = Event::findOrFail($data['event_id']);
                $host = User::findOrFail($event->user_id);
                $myInfo = User::findOrFail($meId);

                $cost = EventCost::where('event_id', $data['event_id'])->where('level', $data['ticket_type'])->get()->first();

                $cost->decrement('available', $data['ticket_quantity']);

                Deposit::create([
                    'user_id' => $event->user_id,
                    'amount' => $data['ticket_cost'] * $data['ticket_quantity']
                ]);

                
                $host->increment('account_balance', $data['ticket_cost'] * $data['ticket_quantity']);


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
