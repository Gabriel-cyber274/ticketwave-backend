<?php

namespace App\Http\Controllers;

use App\Models\EventCost;
use App\Models\Register;
use App\Models\ValidatedTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ValidatedTicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function allTypes($event_id)
    {
        $ticket_types = EventCost::with(['event', 'validated_tickets'])->where('event_id', $event_id)->get();

        return response()->json([
            'ticket_types' => $ticket_types,
            'message' => 'all event types retrieved successfully',
            'success' => true,
        ]);
    }

    public function allUsersForType($type_id)
    {
        $validateTicket = ValidatedTicket::with(['register', 'ticket_type', 'user'])->where('type_id', $type_id)->get();

        return response()->json([
            'validateTicket' => $validateTicket,
            'message' => 'all signed in retrieved successfully',
            'success' => true,
        ]);
    }

    public function store(Request $request)
    {
        $fields = Validator::make($request->all(), [
            'event_name' => 'required',
            'ticket_code' => 'required',
        ]);

        if ($fields->fails()) {
            return response()->json([
                'errors' => $fields->errors(),
                'success' => false
            ], 422);
        }

        $register = Register::where('ticket_code', $request->ticket_code)->get()->first();
        $ticket_type = EventCost::where('event_id', $register->event_id)->where('level', $register->ticket_type)->get()->first();
        $validateTicket = ValidatedTicket::create([
            'register_id' => $register->id,
            'message' => 'ticket signed in',
            'type_id' => $ticket_type->id,
            'user_id' => $register->user_id,
        ]);

        return response()->json([
            'validateTicket' => $validateTicket,
            'message' => 'ticket validated successfully',
            'success' => true,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $validateTicket = ValidatedTicket::with(['register', 'ticket_type', 'user'])->findOrFail($id);

            return response([
                'validateTicket' => $validateTicket,
                'message' => 'validateTicket retrieved successfully',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validateTicket = ValidatedTicket::with(['register', 'ticket_type', 'user'])->findOrFail($id);

            $validateTicket->update($request->all());

            return response([
                'validateTicket' => $validateTicket,
                'message' => 'validateTicket updated successfully',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $validateTicket = ValidatedTicket::with(['register', 'ticket_type', 'user'])->findOrFail($id);

            $validateTicket->delete();

            return response([
                'message' => 'validateTicket deleted successfully',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }
}
