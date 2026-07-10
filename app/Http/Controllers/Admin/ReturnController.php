<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturnController extends Controller
{
    public function index()
    {
        // daftar rental yang BELUM dikembalikan DAN status pembayaran SUKSES
        $rentals = DB::table('rentals')
            ->leftJoin('returns', 'rentals.id', '=', 'returns.rental_id')
            ->join('users', 'rentals.user_id', '=', 'users.id')
            ->join('motorcycles', 'rentals.motorcycle_id', '=', 'motorcycles.id')
            ->join('payments', 'rentals.id', '=', 'payments.rental_id') // Join dengan payment
            ->whereNull('returns.id')
            ->where('payments.status', 'success') // Hanya tampilkan yang sudah lunas/sukses
            ->select(
                'rentals.*',
                'users.name as user_name',
                'motorcycles.brand',
                'motorcycles.type',
                'motorcycles.license_plate'
            )
            ->orderBy('rentals.return_date', 'asc')
            ->get();

        return view('admin.returns.index', compact('rentals'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'rental_id' => 'required',
            'condition' => 'required|in:good,minor_damage,major_damage',
            'damage_fee' => 'required_if:condition,minor_damage,major_damage|nullable|numeric|min:1',
            'notes' => 'required_if:condition,minor_damage,major_damage|nullable|string|min:3'
        ], [
            'damage_fee.required_if' => 'The damage fee is required when the motorcycle has damage.',
            'damage_fee.min' => 'The damage fee must be at least Rp 1.',
            'notes.required_if' => 'Notes describing the damage are required when the motorcycle has damage.',
            'notes.min' => 'Notes must be at least 3 characters long.'
        ]);

        $rental = DB::table('rentals')->where('id', $request->rental_id)->first();

        // simpan return
        DB::table('returns')->insert([
            'rental_id' => $request->rental_id,
            'motorcycle_id' => $rental->motorcycle_id,
            'user_id' => $rental->user_id,
            'return_date' => now(),
            'condition' => $request->condition,
            'damage_fee' => $request->damage_fee ?? 0,
            'notes' => $request->notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // update motor jadi available
        DB::table('motorcycles')
            ->where('id', $rental->motorcycle_id)
            ->update(['status' => 'available']);

        return back()->with('success', 'Motor berhasil dikembalikan.');
    }
}
