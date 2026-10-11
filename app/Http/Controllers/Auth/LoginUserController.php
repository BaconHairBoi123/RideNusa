<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Support\ActivityLogger;

class LoginUserController extends Controller
{
    /**
     * Tampilkan formulir login.
     */
    public function showLoginForm()
    {
        // Jika user biasa sudah login, redirect ke user home
        if (Auth::guard('web')->check()) {
            return redirect()->route('user.home');
        }
        // Jika admin sudah login, redirect ke admin dashboard
        if (Auth::guard('admin')->check()) {
            return redirect()->route('admin.dashboard');
        }
        return view('auth.login');
    }

    /**
     * Handle proses login.
     */
    public function login(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            // Input field yang digunakan untuk email atau username (misalnya: name="credential")
            'credential' => 'required|string',
            'password' => 'required|string',
        ]);

        // 2. Tentukan field otentikasi (email atau username)
        // Kita berasumsi input field di form Anda bernama 'credential'
        $credential = $request->input('credential');

        // Cek apakah input terlihat seperti format email
        if (filter_var($credential, FILTER_VALIDATE_EMAIL)) {
            $field = 'email';
        } else {
            $field = 'username';
        }

        // 3. Persiapkan kredensial untuk percobaan Auth
        $attemptCredentials = [
            $field => $credential, // Menggunakan field yang dipilih
            'password' => $request->password,
        ];

        // 4. Coba login
        if (Auth::attempt($attemptCredentials, $request->boolean('remember'))) {

            $request->session()->regenerate();

            ActivityLogger::log('LOGIN', 'user', 'User berhasil login', [
                'credential' => $credential,
            ]);

            // Selalu arahkan ke User Dashboard, jangan gunakan intended()
            return redirect()->route('user.home')
                ->with('success', 'Login Berhasil!');
        }

        // 5. Login Gagal
        ActivityLogger::log('LOGIN_FAILED', 'user', 'Percobaan login user gagal', [
            'credential' => $credential,
        ]);

        throw ValidationException::withMessages([
            'credential' => ['Email/Username atau password salah.'],
        ]);
    }

    /**
     * Proses logout user.
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
