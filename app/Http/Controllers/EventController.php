<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use App\Models\EventCost;
use App\Models\EventTag;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use function PHPUnit\Framework\isNull;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $event = Event::with(['costs', 'tags', 'registrations'])->where('is_accepted', true)->get();
        $sortedEvent = collect($event)->sortByDesc('id');
        $finalL = $sortedEvent->values()->all();


        return response([
            'event' => $finalL,
            'count_accepted' => count($finalL),
            'message' => 'All accepted events retrieved successfully',
            'success' => true,
        ], 200);
    }

    public function allEvents()
    {
        $event = Event::with(['costs', 'tags', 'registrations'])->withTrashed()->get();
        $sortedEvent = collect($event)->sortByDesc('id');
        $finalL = $sortedEvent->values()->all();


        return response([
            'event' => $finalL,
            'count_all' => count($finalL),
            'message' => 'All events retrieved successfully',
            'success' => true,
        ], 200);
    }


    public function popularEvents()
    {
        $events = Event::with(['costs', 'tags', 'registrations'])->where('is_accepted', true)->get();
        $sortedEvent = $events->sortByDesc(function ($event) {
            return $event->registrations->count();
        });

        $finalL = $sortedEvent->values()->all();


        return response([
            'event' => $finalL,
            'count_popular' => count($finalL),
            'message' => 'popular events retrieved successfully',
            'success' => true,
        ], 200);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function myEvents()
    {
        $id = auth()->id();
        $event = Event::with(['costs', 'tags', 'registrations'])->where('user_id', $id)->get();

        return response([
            'event' => $event,
            'message' => 'my events retrieved successfully',
            'success' => true,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fields = Validator::make($request->all(), [
            'event_title' => 'required|string',
            'venue_details' => 'required|string',
            'event_website' => 'nullable|string',
            'event_cost.*.level' => 'required|string',
            'event_cost.*.cost' => 'required|numeric',
            'event_cost.*.available' => 'required|numeric',
            'event_tag.*.name' => 'required|string',
            'event_description' => 'required',
            'organizer_details' => 'required',
            'event_start' => 'required',
            'event_category' => 'required|string',
            'event_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($fields->fails()) {
            $response = [
                'errors' => $fields->errors(),
                'success' => false
            ];

            return response($response);
        }

        try {
            $user = Auth::user(); // Use Auth::user() instead of Auth()->user()

            $imageUrl = null;
            if ($request->hasFile('event_image')) {
                $image = $request->file('event_image');
                $imagePath = $image->store('events', 'public');
                $filename = basename($imagePath);

                // Generate the API URL for the image using your custom route
                $imageUrl = route('img.get', ['filename' => $filename]);
            }

            // Check if required fields are present
            if (is_null($request->event_cost) || is_null($request->event_tag)) {
                return response([
                    'message' => 'Some event information is missing',
                    'success' => false,
                ], 200);
            }

            $randomString = Str::random(4);

            $event = Event::create([
                'user_id' => $user->id,
                'event_title' => $request->event_title,
                'venue_details' => $request->venue_details,
                'event_website' => $request->event_website,
                'event_category' => $request->event_category,
                'event_description' => $request->event_description,
                'organizer_details' => $request->organizer_details,
                'event_start' => $request->event_start,
                'event_end' => $request->event_end,
                'event_image' => $imageUrl,
                'is_accepted' => false,
            ]);

            $event->update([
                'event_code' => 'event_' . $randomString . $event->id
            ]);

            $costData = json_decode($request->event_cost, true);
            foreach ($costData as $data) {
                EventCost::create([
                    'event_id' => $event->id,
                    'level' => $data['level'],
                    'cost' => $data['cost'],
                    'available' => $data['available']
                ]);
            }

            $tagData = json_decode($request->event_tag, true);
            foreach ($tagData as $data) {
                EventTag::create([
                    'event_id' => $event->id,
                    'name' => $data['name']
                ]);
            }


            $adminUsers = User::where('admin', true)->get();

            foreach ($adminUsers as $admin) {
                Notification::create([
                    'user_id' => $admin->id,
                    'title' => 'Event Pending',
                    'is_read' => false,
                    'event_id' => $event->id,
                    'description' => $user->fullname . 'created an event and its pending',
                ]);
            }



            return response([
                'message' => 'Event created successfully',
                'success' => true,
                'event' => $event
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $event = Event::with(['costs', 'tags', 'registrations'])->withTrashed()->findorfail($id);

            return response([
                'event' => $event,
                'message' => 'single event retrieved successfully',
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
            'event_title' => 'required|string',
            'venue_details' => 'required|string',
            'event_website' => 'nullable|string',
            'event_cost' => 'required',
            'event_description' => 'required',
            'organizer_details' => 'required',
            'event_start' => 'required',
            // 'event_end' => 'required',
            'event_image' => 'nullable',
        ]);

        if ($fields->fails()) {
            $response = [
                'errors' => $fields->errors(),
                'success' => false
            ];

            return response($response);
        }

        try {
            $event = Event::with(['costs', 'tags'])->findorfail($id);


            if ($request->hasFile('event_image')) {
                // Delete the previous image if it exists
                if ($event->event_image) {
                    $oldImagePath = str_replace('/api/imgs/', 'events/', parse_url($event->event_image, PHP_URL_PATH));
                    Storage::disk('public')->delete($oldImagePath);
                }

                // Store the new image
                $image = $request->file('event_image');
                $imagePath = $image->store('events', 'public');
                $filename = basename($imagePath);

                // $imageUrl = asset('storage/' . $imagePath);
                // Generate the API URL for the image using your custom route
                $imageUrl = route('img.get', ['filename' => $filename]);

                $event->update([
                    'event_image' => $imageUrl,
                ]);
            }



            $event->update([
                'event_title' => $request->event_title,
                'venue_details' => $request->venue_details,
                'event_website' => $request->event_website,
                'event_cost' => $request->event_cost,
                'event_description' => $request->event_description,
                'organizer_details' => $request->organizer_details,
                'event_start' => $request->event_start,
                'event_end' => $request->event_end,
            ]);




            return response([
                'event' => $event,
                'message' => 'event updated successfully',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }



    public function updateCost(Request $request, $id)
    {
        $fields = Validator::make($request->all(), [
            'level' => 'required|string',
            'cost' => 'required',
            'available' => 'required'
        ]);

        if ($fields->fails()) {
            $response = [
                'errors' => $fields->errors(),
                'success' => false
            ];

            return response($response);
        }
        try {
            $cost = EventCost::findorfail($id);
            $userId = auth()->id();

            $checkEvent = Event::findorfail($cost->event_id);
            if ($checkEvent->user_id == $userId) {
                $cost->update([
                    'level' => $request->level,
                    'cost' => $request->cost,
                    'available' => $request->available
                ]);


                return response([
                    'cost' => $cost,
                    'message' => 'event cost updated successfully',
                    'success' => true,
                ], 200);
            } else {
                return response([
                    'message' => "You can't edit this",
                    'success' => false,
                ], 200);
            }
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }

    public function deleteCost($id)
    {
        try {
            $cost = EventCost::findorfail($id);

            $userId = auth()->id();

            $checkEvent = Event::findorfail($cost->event_id);

            if ($checkEvent->user_id == $userId) {
                $cost->delete();
                return response([
                    'message' => 'event cost deleted successfully',
                    'success' => true,
                ], 200);
            } else {
                return response([
                    'message' => "You can't delete this",
                    'success' => false,
                ], 200);
            }
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }

    public function addEventCost(Request $request, $eventId)
    {
        $fields = Validator::make($request->all(), [
            'level' => 'required|string',
            'cost' => 'required',
        ]);

        if ($fields->fails()) {
            $response = [
                'errors' => $fields->errors(),
                'success' => false
            ];

            return response($response);
        }

        try {
            $event = Event::findorfail($eventId);
            $userId = auth()->id();

            if ($event->user_id == $userId) {
                $cost = EventCost::create([
                    'event_id' => $event->id,
                    'level' => $request->level,
                    'cost' => $request->cost
                ]);


                return response([
                    'cost' => $cost,
                    'message' => 'event cost added successfully',
                    'success' => true,
                ], 200);
            } else {
                return response([
                    'message' => "You can't add this",
                    'success' => false,
                ], 200);
            }
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }


    public function Categories()
    {
        $category = Category::get();

        return response([
            'category' => $category,
            'message' => 'category retrieved successfully',
            'success' => true,
        ], 200);
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $event = Event::findorfail($id);

            if ($event->event_image) {
                $imagePath = str_replace('/api/imgs/', 'events/', parse_url($event->event_image, PHP_URL_PATH));

                Storage::disk('public')->delete($imagePath);
            }

            $event->delete();

            return response([
                'message' => 'event deleted successfully',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }


    public function deletedEvents()
    {
        // Ensure the user is authenticated
        $user = auth()->user();

        $events = Event::where('user_id', $user->id)
            ->onlyTrashed()
            ->orderBy('id', 'desc')
            ->get();

        if ($events->isEmpty()) {
            return response()->json([
                'message' => 'No deleted events found',
                'success' => false,
                'events' => []
            ], 404);
        }

        return response()->json([
            'events' => $events,
            'message' => 'events retrieved successfully',
            'success' => true
        ], 200);
    }


    public function getAllpendingEvent()
    {
        $event = Event::with(['costs', 'tags', 'registrations'])->where('is_accepted', false)->get();
        $sortedEvent = collect($event)->sortByDesc('id');
        $finalL = $sortedEvent->values()->all();

        return response([
            'event' => $finalL,
            'count_pending' => count($finalL),
            'message' => 'popular events retrieved successfully',
            'success' => true,
        ], 200);
    }


    public function acceptEvent($id)
    {

        try {
            $event = Event::with(['costs', 'tags', 'registrations'])->findorfail($id);

            $event->update([
                'is_accepted' => true,
            ]);

            return response([
                'event' => $event,
                'message' => 'event updated successfully',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }


    public function rejectEvent($id)
    {

        try {
            $event = Event::with(['costs', 'tags', 'registrations'])->findorfail($id);

            $event->update([
                'is_accepted' => false,
            ]);

            return response([
                'event' => $event,
                'message' => 'event updated successfully',
                'success' => true,
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'message' => $th->getMessage(),
                'success' => false,
            ], 200);
        }
    }




    public function getPendingEventByDate($date)
    {
        $events = Event::with(['costs', 'tags', 'registrations'])
            ->where('is_accepted', false)
            ->whereDate('created_at', $date)
            ->get();

        $sortedEvents = collect($events)->sortByDesc('id')->values()->all();

        return response([
            'events' => $sortedEvents,
            'count_pending' => count($sortedEvents),
            'message' => 'Pending events for ' . $date . ' retrieved successfully',
            'success' => true,
        ], 200);
    }


    public function getAcceptedEventByDate($date)
    {
        $events = Event::with(['costs', 'tags', 'registrations'])
            ->where('is_accepted', true)
            ->whereDate('created_at', $date)
            ->get();

        $sortedEvents = collect($events)->sortByDesc('id')->values()->all();

        return response([
            'events' => $sortedEvents,
            'count_accepted' => count($sortedEvents),
            'message' => 'Pending events for ' . $date . ' retrieved successfully',
            'success' => true,
        ], 200);
    }




    public function getSetEventCategory()
    {
        $events = Event::with(['costs', 'tags', 'registrations'])
            ->where('is_accepted', true)
            ->get();

        $eventCategories = $events->pluck('event_category')->unique();

        $categories = Category::whereIn('name', $eventCategories)->get();

        return response()->json([
            'categories' => $categories,
            'message' => 'event category retrieved successfully',
            'success' => true,
        ], 200);
    }


    public function getEventByCategory($category)
    {
        $events = Event::with(['costs', 'tags', 'registrations'])
            ->where('is_accepted', true)
            ->where('event_category', $category)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'events' => $events,
            'message' => 'events retrieved successfully',
            'success' => true,
        ], 200);
    }

    public function searchEventByName($name)
    {
        $events = Event::with(['costs', 'tags', 'registrations'])
            ->where('is_accepted', true)
            ->where('event_title', 'like', '%' . $name . '%')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'events' => $events,
            'message' => 'events retrieved successfully',
            'success' => true,
        ], 200);
    }


    

}
