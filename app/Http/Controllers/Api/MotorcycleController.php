<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Motorcycle;
use App\Models\MotorcycleImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use App\Support\ActivityLogger;

class MotorcycleController extends Controller
{
    /** Generate embeddings for every motorcycle search text through Supabase Edge Functions. */
    public function generateEmbedding()
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('SUPABASE_KEY'),
            'apikey' => env('SUPABASE_KEY'),
        ])->post(env('SUPABASE_URL') . '/functions/v1/generate-motorcycle-embedding');

        return response()->json($response->json(), $response->status());
    }

    /** Search motorcycles by semantic similarity through Supabase vector search. */
    public function vectorSearch(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string',
            'match_threshold' => 'nullable|numeric|min:0|max:1',
            'match_count' => 'nullable|integer|min:1|max:20',
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('SUPABASE_KEY'),
            'apikey' => env('SUPABASE_KEY'),
        ])->post(env('SUPABASE_URL') . '/functions/v1/vector-search-motorcycles', $validated);

        return response()->json($response->json(), $response->status());
    }

    /**
     * Normalisasi path lama yang mungkin tersimpan sebagai:
     * motorcycles/file.jpg atau storage/motorcycles/file.jpg.
     */
    private function normalizeMotorcycleImagePath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $path = trim(str_replace('\\', '/', $path));
        $path = preg_replace('#^(public/|storage/)#', '', $path);
        $path = ltrim($path, '/');

        return $path !== '' ? $path : null;
    }

    /**
     * Ambil URL gambar dari Supabase Storage.
     * Jika belum ada di Supabase tetapi masih ada di disk public lokal,
     * salin file tersebut ke Supabase terlebih dahulu.
     */
    private function getMotorcycleImageUrl(?string $imagePath): ?string
    {
        $path = $this->normalizeMotorcycleImagePath($imagePath);

        if (!$path) {
            return null;
        }

        $supabaseDisk = Storage::disk('s3');
        $localDisk = Storage::disk('public');

        if (!$supabaseDisk->exists($path)) {
            if (!$localDisk->exists($path)) {
                return null;
            }

            $contents = $localDisk->get($path);
            $mimeType = $localDisk->mimeType($path) ?: 'application/octet-stream';

            $uploaded = $supabaseDisk->put($path, $contents, [
                'ContentType' => $mimeType,
            ]);

            if (!$uploaded || !$supabaseDisk->exists($path)) {
                return null;
            }
        }

        return $supabaseDisk->url($path);
    }

    /**
     * Hapus gambar dari disk Supabase dan disk lokal.
     * Kedua disk diperiksa agar kompatibel dengan data lama maupun baru.
     */
    private function deleteMotorcycleImage(?string $imagePath): void
    {
        $path = $this->normalizeMotorcycleImagePath($imagePath);

        if (!$path) {
            return;
        }

        Storage::disk('s3')->delete($path);
        Storage::disk('public')->delete($path);
    }

    /**
     * Tampilkan semua motor dengan info dasar.
     */
    public function index(Request $request)
    {
        // Jika request khusus dari chatbot, kembalikan data ringkas.
        if ($request->query('chatbot') === 'true') {
            $motorcycles = Motorcycle::select(
                'brand',
                'category as name',
                'cc',
                'price',
                'status'
            )->get();

            return response()->json([
                'status' => 'success',
                'data' => $motorcycles,
            ]);
        }

        $query = Motorcycle::query();

        // Hanya tampilkan motor available, kecuali all=true.
        if ($request->query('all') !== 'true') {
            $query->where('status', 'available');
        }

        // Filter sederhana.
        if ($request->has('brand')) {
            $query->where('brand', 'like', '%' . $request->brand . '%');
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('search')) {
            $query->where(function ($subQuery) use ($request) {
                $subQuery->where('category', 'like', '%' . $request->search . '%')
                    ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $motorcycles = $query->with('images')->latest()->get()->map(function ($motor) {
            $motor->image_url = $this->getMotorcycleImageUrl($motor->image_path);

            if ($motor->images) {
                foreach ($motor->images as $img) {
                    $img->image_url = $this->getMotorcycleImageUrl($img->image_path);
                }
            }

            return $motor;
        });

        return response()->json([
            'status' => 'success',
            'data' => $motorcycles,
        ]);
    }

    /**
     * Tambah data motor baru (POST).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|string|max:100',
            'brand' => 'required|string|max:100',
            'type' => 'required|string',
            'cc' => 'required|integer',
            'transmission' => 'required|string',
            'fuel_configuration' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'license_plate' => 'required|string|max:20|unique:motorcycles,license_plate',
            'status' => 'nullable|string|in:available,rented,service',
            'image' => 'nullable|image|max:4096',
        ], [
            'license_plate.unique' => 'Plat nomor ini sudah terdaftar di sistem!',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->except('image');
        $data['status'] = $request->status ?? 'available';

        if (isset($data['transmission'])) {
            $trans = strtolower($data['transmission']);
            $data['transmission'] = (
                str_contains($trans, 'auto') || str_contains($trans, 'matic')
            ) ? 'Automatic' : 'Manual';
        }

        if ($request->hasFile('image')) {
            // Gambar baru langsung disimpan ke Supabase Storage.
            $path = $request->file('image')->store('motorcycles', 's3');
            $data['image_path'] = $path;
        }

        $motorcycle = Motorcycle::create($data);

        ActivityLogger::log('CREATE', 'motorcycle', 'Motor ditambahkan melalui API', [
            'entity_id' => $motorcycle->getKey(),
            'brand' => $motorcycle->brand,
            'category' => $motorcycle->category,
            'type' => $motorcycle->type,
            'license_plate' => $motorcycle->license_plate,
        ]);

        $motorcycle->image_url = $this->getMotorcycleImageUrl($motorcycle->image_path);

        return response()->json([
            'status' => 'success',
            'message' => 'Motorcycle created successfully',
            'data' => $motorcycle,
        ], 201);
    }

    /**
     * Tampilkan detail spesifik motor.
     */
    public function show($id)
    {
        $motorcycle = Motorcycle::find($id);

        if (!$motorcycle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Motorcycle not found',
            ], 404);
        }

        $motorcycle->image_url = $this->getMotorcycleImageUrl($motorcycle->image_path);

        $gallery = MotorcycleImage::where('motorcycle_id', $id)->get()->map(function ($img) {
            $img->image_url = $this->getMotorcycleImageUrl($img->image_path);
            return $img;
        });

        $motorcycle->gallery = $gallery;

        return response()->json([
            'status' => 'success',
            'data' => $motorcycle,
        ]);
    }

    /**
     * Tampilkan info service motor.
     */
    public function services(Request $request)
    {
        $query = Motorcycle::query();

        if ($request->has('license_plate')) {
            $query->where('license_plate', 'like', '%' . $request->license_plate . '%');
        }

        $motorcycles = $query->with(['services'])->latest()->get()->map(function ($motor) {
            $motor->image_url = $this->getMotorcycleImageUrl($motor->image_path);
            return $motor;
        });

        return response()->json([
            'status' => 'success',
            'data' => $motorcycles,
        ]);
    }

    /**
     * Update data motor (PUT/PATCH).
     */
    public function update(Request $request, $id)
    {
        $motorcycle = Motorcycle::find($id);

        if (!$motorcycle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Motorcycle not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'category' => 'sometimes|string|max:255',
            'brand' => 'sometimes|string|max:255',
            'type' => 'sometimes|string',
            'cc' => 'sometimes|integer',
            'transmission' => 'sometimes|string',
            'fuel_configuration' => 'sometimes|string',
            'status' => 'sometimes|string|in:available,rented,service',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric|min:0',
            'license_plate' => 'sometimes|string|max:20',
            'image' => 'sometimes|image|max:4096',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Jika ada gambar baru, simpan ke Supabase.
        if ($request->hasFile('image')) {
            $oldImagePath = $motorcycle->image_path;

            $newPath = $request->file('image')->store('motorcycles', 's3');

            if (!$newPath) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Gagal mengunggah gambar ke Supabase Storage.',
                ], 500);
            }

            $motorcycle->image_path = $newPath;

            // Hapus gambar lama setelah gambar baru berhasil diunggah.
            if ($oldImagePath) {
                $this->deleteMotorcycleImage($oldImagePath);
            }
        }

        // Update fillable fields.
        $fillableFields = [
            'category',
            'brand',
            'type',
            'cc',
            'transmission',
            'fuel_configuration',
            'status',
            'description',
            'price',
            'license_plate',
            'device_id',
        ];

        foreach ($fillableFields as $field) {
            if ($request->has($field)) {
                $val = $request->$field;

                if ($field === 'transmission' && is_string($val)) {
                    $trans = strtolower($val);
                    $val = (
                        str_contains($trans, 'auto') || str_contains($trans, 'matic')
                    ) ? 'Automatic' : 'Manual';
                }

                $motorcycle->$field = $val;
            }
        }

        $motorcycle->save();
        $motorcycle->image_url = $this->getMotorcycleImageUrl($motorcycle->image_path);

        return response()->json([
            'status' => 'success',
            'message' => 'Motorcycle updated successfully',
            'data' => $motorcycle,
        ]);
    }

    /**
     * Hapus data motor (DELETE).
     */
    public function destroy($id)
    {
        $motorcycle = Motorcycle::find($id);

        if (!$motorcycle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Motorcycle not found',
            ], 404);
        }

        // Hapus gambar utama dari Supabase dan local storage.
        if ($motorcycle->image_path) {
            $this->deleteMotorcycleImage($motorcycle->image_path);
        }

        // Hapus seluruh gambar galeri.
        $galleryImages = MotorcycleImage::where('motorcycle_id', $id)->get();

        foreach ($galleryImages as $img) {
            if ($img->image_path) {
                $this->deleteMotorcycleImage($img->image_path);
            }

            $img->delete();
        }

        $motorcycle->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Motorcycle deleted successfully',
        ]);
    }
}
