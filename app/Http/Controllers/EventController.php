<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Event;
use App\Models\EventCost;
use App\Models\EventTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $event = Event::with(['costs', 'tags', 'registrations'])->get();
        $sortedEvent = collect($event)->sortByDesc('id');
        $finalL = $sortedEvent->values()->all();
        

        return response([
            'event' => $finalL,
            'message' => 'All events retrieved successfully',
            'success' => true,
        ], 200);
    }


    public function popularEvents () {
        $events = Event::with(['costs', 'tags', 'registrations'])->get();
        $sortedEvent = $events->sortByDesc(function ($event) {
            return $event->registrations->count();
        });

        $finalL = $sortedEvent->values()->all();
        
        
        return response([
            'event' => $finalL,
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
        $event = Event::with(['costs', 'tags'])->where('user_id', $id)->get();

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
        //
        $fields = Validator::make($request->all(),[
            'event_title'=> 'required|string',
            'venue_details'=> 'required|string',
            'event_website'=> 'nullable|string',
            'event_cost' => 'required|array',
            'event_cost.*.level' => 'required|string',
            'event_cost.*.cost' => 'required|numeric',
            'event_tag' => 'required|array',
            'event_tag.*.name' => 'required|string',
            'event_description' => 'required',
            'organizer_details' => 'required',
            'event_start' => 'required',
            'event_category'=>'required|string',
            'event_end' => 'required',
            // 'event_image' => 'nullable',
        ]);
        
        if($fields->fails()) {
            $response = [
                'errors'=> $fields->errors(),
                'success' => false
            ];

            return response($response);
        }

        try {
            $user = auth()->user();

            if ($request->hasFile('event_image')) {
                $image = $request->file('event_image');
                $imagePath = $image->store('events', 'public'); 
                $imageUrl = asset('storage/' . $imagePath);
            }
    
            $event = Event::create([
                'user_id' => $user->id,
                'event_title'=> $request->event_title,
                'venue_details'=> $request->venue_details,
                'event_website'=> $request->event_website,
                'event_category'=> $request->event_category,
                'event_description' => $request->event_description,
                'organizer_details' => $request->organizer_details,
                'event_start' => $request->event_start,
                'event_end' => $request->event_end,
                'event_image' => $request->hasFile('event_image')?$imageUrl:null,
            ]);

            
            // if($event) {
                $costData = $request->event_cost;
                foreach ($costData as $data) {
                    EventCost::create([
                        'event_id' => $event->id,
                        'level' => $data['level'],
                        'cost' => $data['cost']
                    ]);
                }




                $tagData = $request->event_tag;
    
                
                foreach ($tagData as $data) {
                    EventTag::create([
                        'event_id' => $event->id,
                        'name' => $data['name']
                    ]);
                }
            // }







    
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
            $event = Event::with(['costs', 'tags'])->findorfail($id);
            
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
        $fields = Validator::make($request->all(),[
            'event_title'=> 'required|string',
            'venue_details'=> 'required|string',
            'event_website'=> 'nullable|string',
            'event_cost' => 'required',
            'event_description' => 'required',
            'organizer_details' => 'required',
            'event_start' => 'required',
            'event_end' => 'required',
            'event_image' => 'nullable',
        ]);
        
        if($fields->fails()) {
            $response = [
                'errors'=> $fields->errors(),
                'success' => false
            ];

            return response($response);
        }

        try {
            $event = Event::with(['costs', 'tags'])->findorfail($id);


            if ($request->hasFile('event_image')) {
                // Delete the previous image if it exists
                if ($event->event_image) {
                    $oldImagePath = str_replace(asset('storage/'), '', $event->event_image);
                    Storage::disk('public')->delete($oldImagePath);
                }
    
                // Store the new image
                $image = $request->file('event_image');
                $imagePath = $image->store('events', 'public');
                $imageUrl = asset('storage/' . $imagePath);
    
                $event->update([
                    'event_image' => $imageUrl,
                ]);
            }



            $event->update([
                'event_title'=> $request->event_title,
                'venue_details'=> $request->venue_details,
                'event_website'=> $request->event_website,
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



    public function updateCost (Request $request, $id) {
        $fields = Validator::make($request->all(),[
            'level'=> 'required|string',
            'cost'=> 'required',
        ]);
        
        if($fields->fails()) {
            $response = [
                'errors'=> $fields->errors(),
                'success' => false
            ];

            return response($response);
        }
        try {
            $cost = EventCost::findorfail($id);
            $userId = auth()->id();

            $checkEvent = Event::findorfail($cost->event_id);
            if($checkEvent->user_id == $userId) {
                $cost->update([
                    'level' => $request->level,
                    'cost' => $request->cost
                ]);

                
                return response([
                    'cost' => $cost,
                    'message' => 'event cost updated successfully',
                    'success' => true,
                ], 200);
            }else {
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

            if($checkEvent->user_id == $userId) {
                $cost->delete();
                return response([
                    'message' => 'event cost deleted successfully',
                    'success' => true,
                ], 200);
            }
            else {
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

    public function addEventCost (Request $request, $eventId) {
        $fields = Validator::make($request->all(),[
            'level'=> 'required|string',
            'cost'=> 'required',
        ]);
        
        if($fields->fails()) {
            $response = [
                'errors'=> $fields->errors(),
                'success' => false
            ];

            return response($response);
        }

        try {
            $event = Event::findorfail($eventId);
            $userId = auth()->id();

            if($event->user_id == $userId) {
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
            }else {
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


    public function Categories () {
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
                $imagePath = str_replace(asset('storage/'), '', $event->event_image);
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
}
