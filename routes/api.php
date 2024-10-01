<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\testimonyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\volunteerController;
use App\Http\Controllers\WithdrawController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

// Route::get('/user-details', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');


Route::get('/imgs/{filename}', function ($filename) {
    $path = storage_path('app/public/events/' . $filename);


    // if (!file_exists($path)) {
    //     abort(404);
    // }
    $file = file_get_contents($path);
    $type = mime_content_type($path);

    return Response::make($file, 200, [
        'Content-Type' => $type,
        // 'Content-Disposition'=> 'inline, filename="'. $filename . '"',
    ]);
})->name('img.get');


Route::get('/userimgs/{filename}', function ($filename) {
    $path = storage_path('app/public/users/' . $filename);


    // if (!file_exists($path)) {
    //     abort(404);
    // }
    $file = file_get_contents($path);
    $type = mime_content_type($path);

    return Response::make($file, 200, [
        'Content-Type' => $type,
        // 'Content-Disposition'=> 'inline, filename="'. $filename . '"',
    ]);
})->name('userimg.get');




Route::get('/testimgs/{filename}', function ($filename) {
    $path = storage_path('app/public/testimonials/' . $filename);


    // if (!file_exists($path)) {
    //     abort(404);
    // }
    $file = file_get_contents($path);
    $type = mime_content_type($path);

    return Response::make($file, 200, [
        'Content-Type' => $type,
        // 'Content-Disposition'=> 'inline, filename="'. $filename . '"',
    ]);
})->name('testimg.get');


Route::post('/register', [UserController::class, 'Register']);
Route::post('/login', [UserController::class, 'Login']);
Route::post('/verify-email', [UserController::class, 'verifyEmail']);
Route::post('/resend-verify-email', [UserController::class, 'resendVerificationEmail']);
Route::post('/user/check-email', [UserController::class, 'checkEmail']);
Route::post('/user/change-password', [UserController::class, 'ChangePassword']);



Route::get('/events', [EventController::class, 'index']);

Route::get('/popular-events', [EventController::class, 'popularEvents']);

Route::get('/events/{id}', [EventController::class, 'show']);


Route::group(['middleware' => ['auth:sanctum']], function () {
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

    Route::get('/registrations', [RegisterController::class, 'allRegistrations']);
    Route::get('/registrations-by-name', [RegisterController::class, 'registrationSorbByName']);

    Route::get('/registrations-by-orderid', [RegisterController::class, 'registrationSorbByOrderId']);
    Route::get('/registrations-by-fee', [RegisterController::class, 'registrationSorbByFee']);


    Route::get('/registrations-by-param/{param}', [RegisterController::class, 'filterOrders']);














    Route::get('/carts', [CartController::class, 'index']);
    Route::post('/carts', [CartController::class, 'store']);
    Route::get('/carts/{id}', [CartController::class, 'show']);
    Route::post('/carts/{id}', [CartController::class, 'update']);
    Route::post('/update-carts', [CartController::class, 'updateMultiple']);
    Route::delete('/carts/{id}', [CartController::class, 'destroy']);






    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications-read', [NotificationController::class, 'readNotificationAll']);


    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);





    Route::get('/volunteers', [volunteerController::class, 'index']);
    Route::post('/volunteers', [volunteerController::class, 'store']);
    Route::get('/volunteers/{id}', [volunteerController::class, 'show']);
    Route::post('/volunteers/{id}', [volunteerController::class, 'update']);
    Route::delete('/volunteers/{id}', [volunteerController::class, 'destroy']);




    Route::get('/testimonials', [testimonyController::class, 'index']);
    Route::post('/testimonials', [testimonyController::class, 'store']);
    Route::get('/testimonials/{id}', [testimonyController::class, 'show']);
    Route::post('/testimonials/{id}', [testimonyController::class, 'update']);
    Route::delete('/testimonials/{id}', [testimonyController::class, 'destroy']);
























    Route::post('/logout', [UserController::class, 'Logout']);




    Route::get('/all-revenue', [UserController::class, 'allAdminRevenue']);
    Route::get('/users/{id}', [UserController::class, 'viewUser']);

    Route::get('/all-users', [UserController::class, 'allUsers']);
    Route::get('/usersbydate/{date}', [UserController::class, 'sortUsersByDate']);
    Route::get('/revenuebydate/{date}', [UserController::class, 'adminRevenueByDate']);



    Route::get('/sold-tickets', [RegisterController::class, 'totalTicketSold']);
    Route::get('/soldTicketsByDate/{date}', [RegisterController::class, 'totalTicketSoldByDate']);

    Route::get('/usersWithTicketSold', [RegisterController::class, 'usersWithTicketSold']);
    Route::get('/usersWithTicketSoldSortByName', [RegisterController::class, 'usersWithTicketSoldSortByName']);
    Route::get('/usersWithTicketSoldSortByTicketSold', [RegisterController::class, 'usersWithTicketSoldSortByTicketSold']);
    Route::get('/usersWithTicketSoldSortByHighestAmount', [RegisterController::class, 'usersWithTicketSoldSortByHighestAmount']);




    Route::get('/monthly_registration/{year}', [RegisterController::class, 'getMonthlyRegistrations']);


    Route::get('/pending_events', [EventController::class, 'getAllpendingEvent']);
    Route::get('/all_events', [EventController::class, 'allEvents']);

    Route::post('/accept-event/{id}', [EventController::class, 'acceptEvent']);
    Route::post('/reject-event/{id}', [EventController::class, 'rejectEvent']);


    Route::get('/deleted-events', [EventController::class, 'deletedEvents']);



    Route::get('/pendingEventsByDate/{date}', [EventController::class, 'getPendingEventByDate']);
    Route::get('/acceptedEventsByDate/{date}', [EventController::class, 'getAcceptedEventByDate']);








    Route::get('/withdrawals', [WithdrawController::class, 'index']);
    Route::post('/withdrawals', [WithdrawController::class, 'store']);
    Route::get('/withdrawals/{id}', [WithdrawController::class, 'show']);
    Route::post('/withdrawals/{id}', [WithdrawController::class, 'updateAmount']);
    Route::post('/withdrawals/{id}/approve', [WithdrawController::class, 'approveWithdrawal']);
    Route::post('/withdrawals/{id}/disapprove', [WithdrawController::class, 'disapproveWithdrawl']);
    Route::delete('/withdrawals/{id}', [WithdrawController::class, 'destroy']);
});
