<?php

namespace App\Http\Controllers;

use App\Models\Volunteer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class volunteerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $volunteer = Volunteer::with('user')->get();

        return response([
            'message' => 'volunteers retrieved successfuly',
            'success' => true,
            'volunteer' => $volunteer
        ], 200);
    }

    public function store(Request $request)
    {

        $fields = Validator::make($request->all(), [
            'full_name' => 'required',
            'email' => 'required|string|email|max:255|unique:volunteers,email',
            'phone_number' => 'required',
            'height' => 'required',
            'complexion' => 'required',
            'gender' => 'required',
        ]);

        if ($fields->fails()) {
            return response([
                'errors' => $fields->errors(),
                'success' => false
            ], 400);
        }

        $userId = auth()->id();


        $volunteer = Volunteer::create([
            'user_id' => $userId,
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'height' => $request->height,
            'complexion' => $request->complexion,
            'gender' => $request->gender
        ]);



        return response([
            'message' => 'you have successfully signup up as a volunteer',
            'success' => true,
            'volunteer' => $volunteer
        ], 200);
    }

    public function show($id)
    {
        try {
            $volunteer = Volunteer::with('user')->findorfail($id);
            return response([
                'message' => 'you have successfully signup up as a volunteer',
                'success' => true,
                'volunteer' => $volunteer
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
            $volunteer = Volunteer::with('user')->findorfail($id);

            $volunteer->update($request->all());
            return response([
                'message' => 'you have successfully signup up as a volunteer',
                'success' => true,
                'volunteer' => $volunteer
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
            $volunteer = Volunteer::with('user')->findorfail($id);
            $volunteer->delete();
            return response([
                'message' => 'volunteer deleted successfully',
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
