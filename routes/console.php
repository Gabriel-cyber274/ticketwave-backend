<?php

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();





Schedule::call(function () {
    // Get all events
    $events = Event::all();

    foreach ($events as $event) {
        if (Carbon::now()->greaterThan($event->event_end)) {

            // Find the event by its ID
            $eventToDelete = Event::findOrFail($event->id);

            if ($eventToDelete->event_image) {
                $filename = basename($eventToDelete->event_image);

                // Build the storage path based on the assumed directory 'events'
                $imagePath = 'events/' . $filename;

                // Delete the image from the public disk
                Storage::disk('public')->delete($imagePath);
            }

            // Delete the event
            $eventToDelete->delete();
        }
    }
})->daily();
