<?php

namespace App\Http\Controllers;

use App\Models\Register;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    //

    public function GetAllMyRegistrations() {
        $meId= Auth()->id();
        $registration = Register::with(['user', 'event'])->where('user_id', $meId)->get();

        return response([
            'registration' => $registration,
            'message' => 'registration retrieved successfully',
            'success' => true,
        ], 200);
    }

    


}
