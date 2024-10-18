<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use Illuminate\Http\Request;

class DepositController extends Controller
{

    public function index()
    {
        $meId = Auth()->id();
        $deposits = Deposit::with('user')->where('user_id', $meId)->orderBy('id', 'desc')->get();

        return response()->json([
            'deposits' => $deposits,
            'message' => 'events retrieved successfully',
            'success' => true,
        ], 200);
    }

    public function show($id)
    {
        try {
            $deposits = Deposit::with('user')->findorfail($id);
            return response([
                'deposits' => $deposits,
                'message' => 'single deposit retrieved successfully',
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
            $deposits = Deposit::with('user')->findorfail($id);

            $deposits->update($request->all());

            return response([
                'deposits' => $deposits,
                'message' => 'single deposit retrieved successfully',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }

    public function destroy($id)
    {
        try {
            $deposits = Deposit::with('user')->findorfail($id);

            $deposits->delete();

            return response([
                'deposits' => $deposits,
                'message' => 'single deposit retrieved successfully',
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
