<?php

namespace App\Http\Controllers;

use App\Models\Food;
use App\Services\ImageKitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FoodController extends Controller
{
    protected $imageKitService;

    public function __construct(ImageKitService $imageKitService)
    {
        $this->imageKitService = $imageKitService;
    }

    public function index(Request $request)
    {
        $query = Food::query();

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }


        if ($request->has('is_available')) {
            $query->where('is_available', $request->is_available);
        }


        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $foods = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $foods
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'category' => 'required|in:food,beverage,dessert',
            'is_available' => 'boolean',
            'description' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            $uploadResult = $this->imageKitService->upload($request->file('image'), 'foods');
            
            if ($uploadResult) {
                $data['image_url'] = $uploadResult['url'];
                $data['image_id'] = $uploadResult['fileId'];
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to upload image'
                ], 500);
            }
        }

        $food = Food::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Food created successfully',
            'data' => $food
        ], 201);
    }


    public function update(Request $request, $id)
    {
        $food = Food::find($id);

        if (!$food) {
            return response()->json([
                'status' => 'error',
                'message' => 'Food not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|required|numeric|min:0',
            'category' => 'sometimes|required|in:food,beverage,dessert',
            'is_available' => 'sometimes|boolean',
            'description' => 'nullable|string',
            'image' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();


        if ($request->hasFile('image')) {
            if ($food->image_id) {
                $this->imageKitService->delete($food->image_id);
            }


            $uploadResult = $this->imageKitService->upload($request->file('image'), 'foods');
            
            if ($uploadResult) {
                $data['image_url'] = $uploadResult['url'];
                $data['image_id'] = $uploadResult['fileId'];
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to upload image'
                ], 500);
            }
        }

        $food->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Food updated successfully',
            'data' => $food->fresh()
        ], 200);
    }

    public function destroy($id)
    {
        $food = Food::find($id);

        if (!$food) {
            return response()->json([
                'status' => 'error',
                'message' => 'Food not found'
            ], 404);
        }

        if ($food->image_id) {
            $this->imageKitService->delete($food->image_id);
        }

        $food->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Food deleted successfully'
        ], 200);
    }
}
