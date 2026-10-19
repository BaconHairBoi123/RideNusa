<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Device;
use App\Models\Locations;

class GpsController extends Controller
{
    /**
     * Endpoint untuk menerima update koordinat dari ESP32
     * POST /api/gps/update
     * * Menggunakan sistem UPDATE berdasarkan device_id agar database TIDAK membengkak.
     */
    public function updateLocation(Request $request)
    {
        // 1. Validasi data yang masuk dari ESP32
        $request->validate([
            'device_code' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // 2. Cek apakah device_code sudah terdaftar di sistem
        $device = Device::where('device_code', $request->device_code)->first();

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device GPS tidak terdaftar di sistem Ride Nusa!'
            ], 404);
        }

        // 3. OPTIMASI: Update lokasi terakhir, jangan buat baris baru terus-menerus
        // Mencari apakah sudah ada baris lokasi untuk device ini sebelumnya
        $location = Locations::where('device_id', $device->id)->first();

        if ($location) {
            // Jika sudah ada data lama, kita TIMPA / UPDATE dengan koordinat baru
            $location->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);
            $message = 'Koordinat berhasil diperbarui (Updated)';
        } else {
            // Jika benar-benar baru pertama kali mengirim data, kita BUAT baris pertamanya
            $location = Locations::create([
                'device_id' => $device->id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);
            $message = 'Koordinat awal berhasil dibuat (Created)';
        }

        // Simpan ulang koordinat sebagai Point PostGIS agar dapat dipakai
        // untuk perhitungan jarak dan pencarian radius.
        $this->syncSpatialPoint($location);

        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $location
        ], 200);
    }

    /**
     * Mencari lokasi GPS dalam radius tertentu dari titik pengguna.
     * POST /api/gps/radius
     */
    public function nearbyLocations(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_meter' => 'required|integer|min:1|max:50000',
        ]);

        $point = "extensions.ST_SetSRID(extensions.ST_MakePoint(CAST(? AS double precision), CAST(? AS double precision)), 4326)::extensions.geography";

        $locations = DB::select(
            "SELECT
                l.id,
                l.device_id,
                l.latitude,
                l.longitude,
                m.id AS motorcycle_id,
                m.brand,
                m.type,
                m.license_plate,
                ROUND(extensions.ST_Distance(l.location_point::extensions.geography, {$point}))::integer AS distance_meter
             FROM public.locations l
             LEFT JOIN public.devices d ON d.id = l.device_id
             LEFT JOIN public.motorcycles m ON m.device_id = d.id
             WHERE l.location_point IS NOT NULL
               AND extensions.ST_DWithin(
                   l.location_point::extensions.geography,
                   {$point},
                   CAST(? AS double precision)
               )
             ORDER BY distance_meter ASC",
            [
                $validated['longitude'], $validated['latitude'],
                $validated['longitude'], $validated['latitude'],
                $validated['radius_meter'],
            ]
        );

        return response()->json([
            'status' => 'success',
            'search_center' => [
                'latitude' => (float) $validated['latitude'],
                'longitude' => (float) $validated['longitude'],
            ],
            'radius_meter' => (int) $validated['radius_meter'],
            'result_count' => count($locations),
            'data' => $locations,
        ]);
    }

    private function syncSpatialPoint(Locations $location): void
    {
        DB::statement(
            'UPDATE public.locations
             SET location_point = extensions.ST_SetSRID(
                 extensions.ST_MakePoint(CAST(? AS double precision), CAST(? AS double precision)),
                 4326
             )
             WHERE id = ?',
            [$location->longitude, $location->latitude, $location->id]
        );
    }

    /**
     * Endpoint untuk mendapatkan koordinat terbaru dari device tertentu
     * GET /api/gps/latest/{device_id}
     */
    public function statusRelay(Request $request)
    {
        $request->validate([
            'device_code' => 'required|string',
        ]);

        $device = Device::where('device_code', $request->device_code)->first();

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device GPS tidak terdaftar'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'device_code' => $device->device_code,
                'relay_status' => $device->relay_status ?? 'ON',
                'motorcycle_plate' => $device->motorcycle?->license_plate,
            ]
        ], 200);
    }

    public function getLatestLocation($device_id)
    {
        $device = Device::find($device_id);

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device tidak ditemukan'
            ], 404);
        }

        // Karena kita menggunakan sistem update, data di tabel dipastikan adalah yang paling baru
        $location = Locations::where('device_id', $device->id)->first();

        if (!$location) {
            return response()->json([
                'status' => 'error',
                'message' => 'Belum ada data koordinat untuk device ini'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $location
        ], 200);
    }

    /**
     * Endpoint untuk mendapatkan riwayat perjalanan motor
     * GET /api/gps/history/{motorcycle_id}
     */
    public function getMotorcycleHistory($motorcycle_id)
    {
        $motorcycle = \App\Models\Motorcycle::find($motorcycle_id);

        if (!$motorcycle) {
            return response()->json([
                'status' => 'error',
                'message' => 'Motor tidak ditemukan'
            ], 404);
        }

        if (!$motorcycle->device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Motor belum dipasangi device GPS'
            ], 404);
        }

        // CATATAN: Karena sistemnya UPDATE total, fungsi history ini hanya akan mengembalikan 1 titik lokasi terakhir saja.
        $location = Locations::where('device_id', $motorcycle->device->id)->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'motorcycle_id' => $motorcycle->id,
                'motorcycle_license_plate' => $motorcycle->license_plate,
                'device_code' => $motorcycle->device->device_code,
                'relay_status' => $motorcycle->device->relay_status ?? 'ON',
                'locations' => $location // Hanya berisi 1 record lokasi terbaru
            ]
        ], 200);
    }

    /**
     * Endpoint untuk pairing device ke motor
     * POST /api/gps/pair
     */
    public function pairDeviceToMotorcycle(Request $request)
    {
        $request->validate([
            'device_code' => 'required|string',
            'motorcycle_id' => 'required|integer|exists:motorcycles,id',
        ]);

        $device = Device::where('device_code', $request->device_code)->first();

        if (!$device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device GPS tidak terdaftar!'
            ], 404);
        }

        $motorcycleWithThisDevice = \App\Models\Motorcycle::where('device_id', $device->id)->first();
        
        if ($motorcycleWithThisDevice && $motorcycleWithThisDevice->id !== $request->motorcycle_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Device sudah terpasang ke motor lain (' . $motorcycleWithThisDevice->license_plate . ')'
            ], 400);
        }

        $motorcycle = \App\Models\Motorcycle::findOrFail($request->motorcycle_id);
        $motorcycle->update(['device_id' => $device->id]);
        $device->update(['status' => 'active']);

        return response()->json([
            'status' => 'success',
            'message' => 'Device GPS berhasil dipasang ke motor',
            'data' => [
                'device_code' => $device->device_code,
                'motorcycle_license_plate' => $motorcycle->license_plate,
                'device_status' => 'active'
            ]
        ], 200);
    }

    /**
     * Endpoint untuk lepas device dari motor
     * POST /api/gps/unpair
     */
    public function unpairDeviceFromMotorcycle(Request $request)
    {
        $request->validate([
            'motorcycle_id' => 'required|integer|exists:motorcycles,id',
        ]);

        $motorcycle = \App\Models\Motorcycle::findOrFail($request->motorcycle_id);

        if (!$motorcycle->device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Motor tidak memiliki device GPS'
            ], 404);
        }

        $device = $motorcycle->device;
        $motorcycle->update(['device_id' => null]);
        $device->update(['status' => 'available']);

        return response()->json([
            'status' => 'success',
            'message' => 'Device GPS berhasil dilepas dari motor',
            'data' => [
                'device_code' => $device->device_code,
                'device_status' => 'available'
            ]
        ], 200);
    }

    /**
     * Endpoint untuk mengontrol relay (engine ignition) dari aplikasi mobile
     * POST /api/gps/relay
     */
    public function updateRelayStatus(Request $request)
    {
        $request->validate([
            'motorcycle_id' => 'required|integer|exists:motorcycles,id',
            'relay_status' => 'required|in:ON,OFF',
        ]);

        $motorcycle = \App\Models\Motorcycle::find($request->motorcycle_id);

        if (!$motorcycle || !$motorcycle->device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Motor atau device GPS tidak ditemukan!'
            ], 404);
        }

        // Validasi: Pastikan user login yang merequest memiliki penyewaan aktif untuk motor ini
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 401);
        }

        $bookings = \App\Models\Booking::where('user_id', $user->id)
            ->where('motorcycle_id', $motorcycle->id)
            ->whereIn('payment_status', ['paid', 'settlement'])
            ->get();

        $hasActiveBooking = false;
        foreach ($bookings as $booking) {
            $payment = \App\Models\Payment::where('invoice_number', $booking->order_id)->first();
            $hasReturn = false;
            if ($payment && $payment->rental_id) {
                $rental = \App\Models\Rental::with('returns')->find($payment->rental_id);
                if ($rental && $rental->returns) {
                    $hasReturn = true;
                }
            }
            if (!$hasReturn) {
                $hasActiveBooking = true;
                break;
            }
        }

        if (!$hasActiveBooking) {
            return response()->json([
                'status' => 'error',
                'message' => 'Anda tidak memiliki penyewaan aktif untuk motor ini!'
            ], 403);
        }

        $device = $motorcycle->device;
        $device->update(['relay_status' => $request->relay_status]);

        return response()->json([
            'status' => 'success',
            'message' => 'Status relay berhasil diperbarui.',
            'data' => [
                'device_code' => $device->device_code,
                'relay_status' => $device->relay_status,
            ]
        ], 200);
    }
}
