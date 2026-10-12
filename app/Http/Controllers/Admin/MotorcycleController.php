<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Motorcycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Support\ActivityLogger;

class MotorcycleController extends Controller
{
    public function index()
    {
        return view('admin.motorcycles.index', [
            'motorcycles' => Motorcycle::latest()->paginate(10)
        ]);
    }

    public function create()
    {
        return view('admin.motorcycles.create');
    }

    public function show(Motorcycle $motorcycle)
    {
        return view('admin.motorcycles.show', compact('motorcycle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:100',
            'brand' => 'required|string|max:100',
            'type' => 'required|in:small_matic,big_matic,manual',
            'cc' => 'required|integer',
            'fuel_configuration' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'transmission' => 'required|in:manual,automatic',
            'price' => 'required|numeric',
            'license_plate' => 'required|unique:motorcycles,license_plate',
            'status' => 'required|in:available,service',
            'image' => 'nullable|image|max:2048'
        ], [
            'license_plate.unique' => 'Plat nomor ini sudah terdaftar di sistem! Silakan gunakan plat nomor lain.'
        ]);

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            // hasil: motorcycles/filename.jpg
            $data['image_path'] = $request->file('image')
                ->store('motorcycles', 'public');
        }

        $motorcycle = Motorcycle::create($data);

        ActivityLogger::log('CREATE', 'motorcycle', 'Admin menambahkan motor', [
            'entity_id' => $motorcycle->getKey(),
            'brand' => $motorcycle->brand,
            'category' => $motorcycle->category,
            'type' => $motorcycle->type,
            'license_plate' => $motorcycle->license_plate,
        ]);

        return redirect()
            ->route('admin.motorcycles_Management.index')
            ->with('success', 'Motorcycle created successfully.');
    }

    public function edit(Motorcycle $motorcycle)
    {
        return view('admin.motorcycles.edit', compact('motorcycle'));
    }

    public function update(Request $request, Motorcycle $motorcycle)
    {
        $request->validate([
            'category' => 'required|string|max:100',
            'brand' => 'required|string|max:100',
            'type' => 'required|in:small_matic,big_matic,manual',
            'cc' => 'required|integer',
            'fuel_configuration' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'transmission' => 'required|in:manual,automatic',
            'price' => 'required|numeric',
            'license_plate' => 'required|unique:motorcycles,license_plate,' . $motorcycle->id,
            'status' => 'required|in:available,rented,service',
            'image' => 'nullable|image|max:2048'
        ], [
            'license_plate.unique' => 'Plat nomor ini sudah terdaftar di sistem! Silakan gunakan plat nomor lain.'
        ]);

        // Prevent editing rented motorcycle status manually to something else
        if ($motorcycle->status === 'rented' && $request->status !== 'rented') {
            return back()->withErrors(['status' => 'Status of a rented motorcycle cannot be changed manually.']);
        }
        
        // Prevent manually setting a non-rented motorcycle to rented
        if ($motorcycle->status !== 'rented' && $request->status === 'rented') {
            return back()->withErrors(['status' => 'You cannot change the motorcycle status to Rented manually.']);
        }

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            // Hapus file lama jika ada (untuk fungsi update)
            if (isset($motorcycle) && $motorcycle->image_path) {
                Storage::disk('public')->delete($motorcycle->image_path);
            }

            // Simpan file. Laravel otomatis membuat folder 'motorcycles' jika belum ada
            // store() akan mengembalikan path seperti: "motorcycles/AbCd123.jpg"
            $data['image_path'] = $request->file('image')->store('motorcycles', 'public');
        }

        $motorcycle->update($data);

        return redirect()
            ->route('admin.motorcycles_Management.index')
            ->with('success', 'Motorcycle updated successfully.');
    }

    public function destroy(Motorcycle $motorcycle)
    {
        if ($motorcycle->image_path) {
            Storage::disk('public')->delete($motorcycle->image_path);
        }

        $motorcycle->delete();

        return back()->with('success', 'Motorcycle deleted.');
    }

    public function toggleStatus(Request $request, Motorcycle $motorcycle)
    {
        if ($motorcycle->status === 'rented') {
            return back()->with('error', 'Cannot change the status of a rented motorcycle.');
        }

        if ($request->has('status') && in_array($request->status, ['available', 'service'])) {
            $motorcycle->status = $request->status;
        } else {
            $motorcycle->status = ($motorcycle->status === 'available') ? 'service' : 'available';
        }
        
        $motorcycle->save();

        return back()->with('success', 'Motorcycle status updated to ' . ucfirst($motorcycle->status) . ' successfully.');
    }
}
