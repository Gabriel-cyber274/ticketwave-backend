<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Event;
use App\Models\Register;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $medId = Auth()->id();
        $cartInit = Cart::with(['user', 'event'])->where('user_id', $medId)->where('paid', 0)->get();
        $sortedEvent = collect($cartInit)->sortByDesc('id');
        $cart = $sortedEvent->values()->all();

        return response([
            'cart' => $cart,
            'message' => 'cart retrieved successfully',
            'success' => true,
        ], 200);
    }


    // public function store(Request $request)
    // {
    //     $fields = Validator::make($request->all(),[
    //         'event_id'=> 'required',
    //         'quantity' => 'required',
    //         'ticket_cost' => 'required',
    //         'ticket_type'=> 'required'
    //     ]);

    //     if($fields->fails()) {
    //         $response = [
    //             'errors'=> $fields->errors(),
    //             'success' => false
    //         ];

    //         return response($response);
    //     }

    //     $medId = Auth()->id();

    //     $cart = Cart::create([
    //         'user_id'=>$medId,
    //         'event_id' => $request->event_id,
    //         'quantity' => $request->quantity,
    //         'paid'=> false,
    //         'ticket_cost' => $request->ticket_cost,
    //         'ticket_type'=> $request->ticket_type
    //     ]);


    //     return response([
    //         'cart' => $cart,
    //         'message' => 'cart added successfully',
    //         'success' => true,
    //     ], 200);


    // }


    public function store(Request $request)
    {
        $fields = Validator::make($request->all(), [
            'tickets.*.event_id' => 'required|numeric|exists:events,id',
            'tickets.*.quantity' => 'required|numeric|min:1', // Ensure quantity is at least 1
            'tickets.*.ticket_cost' => 'required|numeric|min:0', // Ensure the cost is non-negative
            'tickets.*.ticket_type' => 'required|string',
            'tickets.*.available' => 'required|numeric|min:0', // Ensure available is non-negative
        ]);

        if ($fields->fails()) {
            return response([
                'errors' => $fields->errors(),
                'success' => false
            ], 422);
        }

        $medId = Auth()->id();
        $tickets = $request->tickets;

        try {
            DB::beginTransaction(); // Start a transaction

            foreach ($tickets as $ticket) {
                $checkEvent = Event::find($ticket['event_id']);

                if (!$checkEvent || !$checkEvent->is_accepted) {
                    return response([
                        'message' => "Event with ID {$ticket['event_id']} does not exist or is not accepted.",
                        'success' => false,
                    ], 400);
                }

                Cart::create([
                    'user_id' => $medId,
                    'event_id' => $ticket['event_id'],
                    'quantity' => $ticket['quantity'],
                    'paid' => false,
                    'ticket_cost' => $ticket['ticket_cost'],
                    'ticket_type' => $ticket['ticket_type'],
                    'available' => $ticket['available']
                ]);
            }

            DB::commit(); // Commit the transaction
            return response([
                'message' => 'Carts added successfully',
                'success' => true,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback in case of error
            return response([
                'message' => 'An error occurred while adding carts.',
                'success' => false,
            ], 500);
        }
    }



    public function show($id)
    {
        try {
            $cart = Cart::with(['user', 'event'])->findOrFail($id);

            return response([
                'cart' => $cart,
                'message' => 'cart retrieved successfully',
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

        $fields = Validator::make($request->all(), [
            'quantity' => 'required',
            'paid' => 'nullable'
        ]);

        if ($fields->fails()) {
            $response = [
                'errors' => $fields->errors(),
                'success' => false
            ];

            return response($response);
        }

        try {
            $cart = Cart::with(['user', 'event'])->findOrFail($id);

            $cart->update([
                'quantity' => $request->quantity,
                'paid' => $request->paid ? $request->paid : false,
            ]);

            Log::info($request->paid);



            return response([
                'cart' => $cart,
                'message' => 'cart updated successfully',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }

    public function updateMultiple(Request $request)
    {
        $fields = Validator::make($request->all(), [
            'carts' => 'required|array',
            'carts.*.id' => 'required|numeric|exists:carts,id',
            'carts.*.quantity' => 'required|integer|min:1',
            'carts.*.paid' => 'nullable|boolean'
        ]);

        if ($fields->fails()) {
            return response([
                'errors' => $fields->errors(),
                'success' => false,
            ], 400);
        }

        $updatedCarts = [];

        try {
            foreach ($request->carts as $cartData) {
                $cart = Cart::with(['user', 'event'])->findOrFail($cartData['id']);
                $cart->update([
                    'quantity' => $cartData['quantity'],
                    'paid' => $cartData['paid'] ?? false,
                ]);
                $updatedCarts[] = $cart;
            }

            return response([
                'carts' => $updatedCarts,
                'message' => 'Carts updated successfully',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $cart = Cart::with(['user', 'event'])->findOrFail($id);

            $cart->delete();

            return response([
                'message' => 'cart deleted successfully',
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
