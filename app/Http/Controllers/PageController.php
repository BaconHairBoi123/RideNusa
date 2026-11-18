<?php

namespace App\Http\Controllers;

use App\Models\Motorcycle;
use App\Models\MotorcycleService;
use App\Models\AdditionalAccessories;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PageController extends Controller
{
    // --- Bagian Halaman Umum ---
    public function home()
    {
        $motorcycles = \App\Models\Motorcycle::latest()->take(6)->get();
        return view('user.home', compact('motorcycles'));
    }

    // Halaman About Us
    public function about()
    {
        // return view('user.about');
        // Ambil data motor sama seperti di halaman welcome
        // Sertakan eager loading 'lastService' agar tidak error saat memanggil kilometer
        $motorcycles = Motorcycle::with('lastService')->get();

        // Kirim variabel ke view user/about
        return view('user.about', compact('motorcycles'));
    }

    // Halaman Contact
    public function contact()
    {
        return view('user.contact');
    }

    // Halaman Services
    public function services(Request $request)
    {
        $query = Motorcycle::query();

        if ($request->filled('license_plate')) {
            $query->where('license_plate', 'like', '%' . $request->license_plate . '%');
        }

        $motorcycles = $query->get();

        // Buat URL gambar yang mendukung Supabase Storage dan penyimpanan lokal.
        $motorcycles->transform(function ($motorcycle) {
            $motorcycle->image_url = $this->imageUrl($motorcycle->image_path);
            return $motorcycle;
        });

        // AJAX request untuk pencarian real-time
        if ($request->ajax()) {
            // Perbaikan: sesuaikan path ke folder 'partials' (bukan user.partials)
            return view('partials.service_list', compact('motorcycles'))->render();
        }

        return view('user.services', compact('motorcycles'));
    }

    public function serviceDetail($id)
    {
        // Mengambil data motor beserta relasi riwayat servisnya
        $motor = Motorcycle::with('services')->findOrFail($id);

        // Perbaikan: sesuaikan path ke folder 'partials' (bukan user.partials)
        return view('partials.service_detail', compact('motor'))->render();
    }

    // Halaman FAQs
    public function faq()
    {
        return view('user.faq');
    }

    // Halaman Error 404 Placeholder
    public function errorPage()
    {
        return view('user.error-page');
    }



    public function motorcycles(Request $request)
    {
        // 1. Ambil data unik untuk kebutuhan filter (checkbox / dropdown)
        $brands = Motorcycle::distinct()->pluck('brand')->filter()->sort();
        $types = Motorcycle::distinct()->pluck('type')->filter()->sort();
        $transmissions = Motorcycle::distinct()->pluck('transmission')->filter()->sort();
        $fuels = Motorcycle::distinct()->pluck('fuel_configuration')->filter()->sort();

        // 2. Query utama
        $query = Motorcycle::query();

        // Filter Category (text)
        if ($request->filled('category')) {
            $query->where('category', 'like', '%' . $request->category . '%');
        }

        // Filter Price (range)
        if ($request->filled('min_price') && $request->filled('max_price')) {
            $min = (int) preg_replace('/[^0-9]/', '', $request->min_price);
            $max = (int) preg_replace('/[^0-9]/', '', $request->max_price);
            $query->whereBetween('price', [$min, $max]);
        }

        // Filter Brand
        if ($request->filled('brand')) {
            $query->whereIn('brand', $request->brand);
        }

        // Filter Type
        if ($request->filled('type')) {
            $query->whereIn('type', $request->type);
        }

        // Filter Transmission
        if ($request->filled('transmission')) {
            $query->whereIn('transmission', $request->transmission);
        }

        // Filter Fuel
        if ($request->filled('fuel')) {
            $query->whereIn('fuel_configuration', $request->fuel);
        }

        // 3. Pagination
        $motorcycles = $query->paginate(9)->withQueryString();

        // Buat URL gambar untuk setiap motor pada halaman katalog.
        $motorcycles->getCollection()->transform(function ($motorcycle) {
            $motorcycle->image_url = $this->imageUrl($motorcycle->image_path);
            return $motorcycle;
        });

        // 4. Kirim ke view
        return view('user.motorcycles', compact(
            'motorcycles',
            'brands',
            'types',
            'transmissions',
            'fuels'
        ));
    }



    /**
     * Menghasilkan URL gambar dari Supabase Storage atau penyimpanan lokal
     * tanpa melakukan blocking network request.
     */
    private function imageUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        // Jika path sudah berupa URL lengkap, gunakan langsung.
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // Cek disk lokal terlebih dahulu (sangat cepat di SSD, 0ms latency).
        if (Storage::disk('public')->exists($path)) {
            return asset('storage/' . $path);
        }

        // Generate URL Supabase di memori tanpa HTTP HEAD request ke cloud
        try {
            return Storage::disk('s3')->url($path);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function showMotorcycle($id)
    {
        // Cek status verifikasi user: hanya user yang `verified` boleh melihat halaman detail
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && ($user->verification_status ?? 'unverified') !== 'verified') {
            return redirect()->route('user.profile')
                ->with('error', 'Akun Anda belum terverifikasi. Tunggu verifikasi admin untuk melihat detail motor.');
        }

        $motorcycle = \App\Models\Motorcycle::with(['images', 'services'])->findOrFail($id);
        // Fetch Accessories for the calculator
        $accessories = \App\Models\AdditionalAccessories::all();

        return view('user.motorcycle-single', compact('motorcycle', 'accessories'));
    }

    // --- Bagian SHOP ---



    // --- Bagian BLOG (Optional: Keep if blog is needed, remove otherwise. User asked for optimization) ---
    // Keeping for now as they might be used, but commented out if truly unused in routes.
    // For now, I will remove them to clean up as requested.

    /* 
    public function blog() { return view('user.blog'); }
    public function blogDetails() { return view('user.blog-details'); }
    */


    /*
    |--------------------------------------------------------------------------
    | USER DASHBOARD (PROTECTED ROUTES) 🚨 KODE BARU 🚨
    |--------------------------------------------------------------------------
    | Ditambahkan untuk menangani Route yang ada di kelompok 'auth:web' di web.php
    */

    // public function home()
    // {
    //     // View untuk /user/home
    //     return view('user.home'); 
    // }

    public function profile()
    {
        // View untuk /user/profile
        return view('user.profile');
    }
    public function welcome()
    {
        // Ubah 'home' menjadi 'user.home' agar sesuai dengan route yang baru
        if (\Illuminate\Support\Facades\Auth::check()) {
            return redirect()->route('user.home');
        }

        $motorcycles = \App\Models\Motorcycle::with('lastService')->get();
        return view('welcome', compact('motorcycles'));
    }
}
