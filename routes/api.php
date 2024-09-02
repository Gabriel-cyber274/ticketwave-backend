<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user-details', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');



Route::post('/register', [UserController::class, 'Register']);
Route::post('/login', [UserController::class, 'Login']);
Route::post('/verify-email', [UserController::class, 'verifyEmail']);
Route::post('/resend-verify-email', [UserController::class, 'resendVerificationEmail']);
Route::post('/user/check-email', [UserController::class, 'checkEmail']);
Route::post('/user/change-password', [UserController::class, 'ChangePassword']);



Route::get('/events', [EventController::class, 'index']);

Route::get('/popular-events', [EventController::class, 'popularEvents']);

Route::get('/events/{id}', [EventController::class, 'show']);


Route::group(['middleware'=> ['auth:sanctum']], function () {
    Route::post('/user/change-userdetails', [UserController::class, 'changeDetails']);
    Route::get('/user-details', [UserController::class, 'userDetails']);

    
    Route::get('/my-events', [EventController::class, 'myEvents']);
    Route::post('/events', [EventController::class, 'store']);
    Route::post('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);
    
    
    Route::post('/events-cost/{id}', [EventController::class, 'updateCost']);
    Route::delete('/events-cost/{id}', [EventController::class, 'deleteCost']);
    Route::post('/events-cost/add/{eventId}', [EventController::class, 'addEventCost']);
    Route::get('/categories', [EventController::class, 'Categories']);





    Route::get('/my-registrations', [RegisterController::class, 'GetAllMyRegistrations']);
    Route::post('/Register', [RegisterController::class, 'Register']);


    



    Route::get('/carts', [CartController::class, 'index']);
    Route::post('/carts', [CartController::class, 'store']);
    Route::get('/carts/{id}', [CartController::class, 'show']);
    Route::post('/carts/{id}', [CartController::class, 'update']);
    Route::post('/update-carts', [CartController::class, 'updateMultiple']);
    Route::delete('/carts/{id}', [CartController::class, 'destroy']);






    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    


    
    


    

    

    

    
    
    
    

    

    
    
    
    Route::post('/logout', [UserController::class, 'Logout']);
});