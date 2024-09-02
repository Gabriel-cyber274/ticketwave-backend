<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\EventCost;
use App\Models\Notification;
use App\Models\Register;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    //

    public function GetAllMyRegistrations() {
        $meId= Auth()->id();
        $registration = Register::with(['user', 'event'])->where('user_id', $meId)->get();
        $sortedReg = collect($registration)->sortByDesc('id');
        $finalL = $sortedReg->values()->all();
        
        

        return response([
            'registration' => $finalL,
            'message' => 'registration retrieved successfully',
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
    
                $host->increment('account_balance', $data['ticket_cost'] * $data['ticket_quantity']);


                Notification::create([
                    'user_id'=> $meId,
                    'title'=> 'Ticket Purchased',
                    'description' => 'You have successfully purchased “'.$event->event_title.' Ticket '.$data['ticket_quantity'].' for a sum of #'.$data['ticket_cost']*$data['ticket_quantity'],
                ]);

                Notification::create([
                    'user_id'=> $event->user_id,
                    'title'=> 'Ticket Purchased',
                    'description' => $myInfo->fullname.' has successfully purchased “'.$event->event_title.' Ticket '.$data['ticket_quantity'].' for a sum of #'.$data['ticket_cost']*$data['ticket_quantity'],
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
    

    


}
