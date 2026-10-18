<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AdditionalAccessories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AccessoryController extends Controller
{
    /**
     * Tampilkan daftar aksesoris tambahan (GET)
     */
    public function index()
    {
        $accessories = AdditionalAccessories::all()->map(function ($accessory) {
            $accessory->image_url = $accessory->image_path ? asset('storage/' . $accessory->image_path) : null;
            return $accessory;
        });

        return response()->json([
            'status' => 'success',
            'data' => $accessories
        ]);
    }

    /**
     * Tampilkan detail aksesoris (GET {id})
     */
    public function show($id)
    {
        $accessory = AdditionalAccessories::find($id);

        if (!$accessory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Accessory not found'
            ], 404);
        }

        $accessory->image_url = $accessory->image_path ? asset('storage/' . $accessory->image_path) : null;

        return response()->json([
            'status' => 'success',
            'data' => $accessory
        ]);
    }

    /**
     * Tambah aksesoris baru (POST)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'accessory_name' => 'required|string|max:100',
            'daily_price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = [
            'accessory_name' => $request->accessory_name,
            'daily_price' => $request->daily_price,
            'stock' => $request->stock,
        ];

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('accessories', 'public');
            $data['image_path'] = $path;
        }

        $accessory = AdditionalAccessories::create($data);
        $accessory->image_url = $accessory->image_path ? asset('storage/' . $accessory->image_path) : null;

        return response()->json([
            'status' => 'success',
            'message' => 'Accessory created successfully',
            'data' => $accessory
        ], 201);
    }

    /**
     * Update data aksesoris (PUT/PATCH)
     */
    public function update(Request $request, $id)
    {
        $accessory = AdditionalAccessories::find($id);

        if (!$accessory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Accessory not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'accessory_name' => 'sometimes|string|max:100',
            'daily_price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'image' => 'sometimes|image|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('image')) {
            if ($accessory->image_path) {
                Storage::disk('public')->delete($accessory->image_path);
            }
            $path = $request->file('image')->store('accessories', 'public');
            $accessory->image_path = $path;
        }

        foreach (['accessory_name', 'daily_price', 'stock'] as $field) {
            if ($request->has($field)) {
                $accessory->$field = $request->$field;
            }
        }

        $accessory->save();
        $accessory->image_url = $accessory->image_path ? asset('storage/' . $accessory->image_path) : null;

        return response()->json([
            'status' => 'success',
            'message' => 'Accessory updated successfully',
            'data' => $accessory
        ]);
    }

    /**
     * Hapus aksesoris (DELETE)
     */
    public function destroy($id)
    {
        $accessory = AdditionalAccessories::find($id);

        if (!$accessory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Accessory not found'
            ], 404);
        }

        if ($accessory->image_path) {
            Storage::disk('public')->delete($accessory->image_path);
        }

        $accessory->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Accessory deleted successfully'
        ]);
    }
}
