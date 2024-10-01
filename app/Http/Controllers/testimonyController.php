<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class testimonyController extends Controller
{
    public function index()
    {
        $testimonials = Testimonial::get();

        return response([
            'message' => 'testimonials retrieved successfuly',
            'success' => true,
            'testimonials' => $testimonials
        ], 200);
    }


    public function store(Request $request)
    {
        // Validate the request
        $fields = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5', // Added rating validation (assuming it's a 1-5 scale)
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'title' => 'required|string|max:255',
            'pic' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate file type and size
        ]);

        // If validation fails, return errors
        if ($fields->fails()) {
            return response([
                'errors' => $fields->errors(),
                'success' => false
            ], 400);
        }

        $imageUrl = null;

        // Handle file upload
        if ($request->hasFile('pic')) {
            $image = $request->file('pic');
            $imagePath = $image->store('testimonials', 'public');

            $filename = basename($imagePath);

            // Generate the API URL for the image using a custom route
            $imageUrl = route('testimg.get', ['filename' => $filename]);
        }

        // Create the testimonial record
        $testimonial = Testimonial::create([
            'rating' => $request->rating,
            'description' => $request->description,
            'name' => $request->name,
            'title' => $request->title,
            'pic' => $imageUrl,
        ]);

        // Return success response
        return response([
            'message' => 'Testimonial created successfully',
            'success' => true,
            'testimonial' => $testimonial // Return created testimonial
        ], 201); // 201 for resource creation
    }


    public function show($id)
    {
        try {
            $testimonials = Testimonial::findorfail($id);

            return response([
                'message' => 'testimonial retrieved successfully',
                'success' => true,
                'testimonials' => $testimonials
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
            // Find the testimonial or fail
            $testimonial = Testimonial::findOrFail($id);

            // Validate the incoming request
            $fields = Validator::make($request->all(), [
                'rating' => 'nullable|numeric|min:1|max:5', // Validate rating if present
                'name' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'title' => 'nullable|string|max:255',
                'pic' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Validate new image
            ]);

            if ($fields->fails()) {
                return response([
                    'errors' => $fields->errors(),
                    'success' => false
                ], 400);
            }

            // Handle file upload if present
            if ($request->hasFile('pic')) {
                // Delete the old image if it exists
                if ($testimonial->pic) {
                    $oldImagePath = str_replace('/api/testimgs/', 'testimonials/', parse_url($testimonial->pic, PHP_URL_PATH));

                    Storage::disk('public')->delete($oldImagePath);
                }

                // Store the new image
                $image = $request->file('pic');
                $imagePath = $image->store('testimonials', 'public');
                $imageUrl = route('testimg.get', ['filename' => basename($imagePath)]);

                // Update the image path in the database
                $testimonial->pic = $imageUrl;
            }

            // Update other fields in the testimonial
            $testimonial->update($request->except(['pic']));

            return response([
                'message' => 'Testimonial updated successfully',
                'success' => true,
                'testimonial' => $testimonial
            ], 200);
        } catch (\Throwable $th) {
            return response([
                'message' => 'An error occurred: ' . $th->getMessage(),
                'success' => false,
            ], 500); // Use 500 for internal server errors
        }
    }


    public function destroy($id)
    {
        try {
            $testimonials = Testimonial::findorfail($id);
            if ($testimonials->pic) {
                // $imagePath = str_replace(asset('storage/'), '', $testimonials->pic);
                $imagePath = str_replace('/api/testimgs/', 'testimonials/', parse_url($testimonials->pic, PHP_URL_PATH));

                Storage::disk('public')->delete($imagePath);
            }
            $testimonials->delete();
            return response([
                'message' => 'testimonials deleted successfully',
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
