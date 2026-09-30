<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MotorcycleController;
use App\Http\Controllers\Api\AccessoryController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\GpsController;
use App\Http\Controllers\Api\ServiceTypeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// ==========================================
// PUBLIC ROUTES (TIDAK PERLU LOGIN / TOKEN)
// ==========================================

// Autentikasi
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::get('/users', [AuthController::class, 'index']);

// Master Data Katalog
Route::get('/motorcycles', [MotorcycleController::class, 'index']);
Route::get('/motorcycles/services', [MotorcycleController::class, 'services']);
Route::get('/motorcycles/{id}', [MotorcycleController::class, 'show']);
Route::post('/motorcycles', [MotorcycleController::class, 'store']);
Route::put('/motorcycles/{id}', [MotorcycleController::class, 'update']);
Route::patch('/motorcycles/{id}', [MotorcycleController::class, 'update']);
Route::delete('/motorcycles/{id}', [MotorcycleController::class, 'destroy']);
Route::get('/accessories', [AccessoryController::class, 'index']);
Route::get('/accessories/{id}', [AccessoryController::class, 'show']);
Route::post('/accessories', [AccessoryController::class, 'store']);
Route::put('/accessories/{id}', [AccessoryController::class, 'update']);
Route::patch('/accessories/{id}', [AccessoryController::class, 'update']);
Route::delete('/accessories/{id}', [AccessoryController::class, 'destroy']);
Route::get('/locations', [LocationController::class, 'index']);

// Service Types (Mendukung strip '-' dan underscore '_')
Route::get('/service-types', [ServiceTypeController::class, 'index']);
Route::get('/service-types/{id}', [ServiceTypeController::class, 'show']);
Route::post('/service-types', [ServiceTypeController::class, 'store']);
Route::put('/service-types/{id}', [ServiceTypeController::class, 'update']);
Route::patch('/service-types/{id}', [ServiceTypeController::class, 'update']);
Route::delete('/service-types/{id}', [ServiceTypeController::class, 'destroy']);

Route::get('/service_types', [ServiceTypeController::class, 'index']);
Route::get('/service_types/{id}', [ServiceTypeController::class, 'show']);
Route::post('/service_types', [ServiceTypeController::class, 'store']);
Route::put('/service_types/{id}', [ServiceTypeController::class, 'update']);
Route::patch('/service_types/{id}', [ServiceTypeController::class, 'update']);
Route::delete('/service_types/{id}', [ServiceTypeController::class, 'destroy']);

// GPS API (dari ESP32) - PUBLIC karena ESP32 tidak ada token
Route::post('/gps/update', [GpsController::class, 'updateLocation']);
Route::get('/gps/status-relay', [GpsController::class, 'statusRelay']);
Route::get('/gps/latest/{device_id}', [GpsController::class, 'getLatestLocation']);
Route::get('/gps/history/{motorcycle_id}', [GpsController::class, 'getMotorcycleHistory']);
Route::post('/gps/pair', [GpsController::class, 'pairDeviceToMotorcycle']);
Route::post('/gps/unpair', [GpsController::class, 'unpairDeviceFromMotorcycle']);

// ==========================================
// PROTECTED ROUTES (WAJIB BAWA TOKEN)
// ==========================================

Route::middleware('auth:sanctum')->group(function () {
    
    // Profil & Logout
    Route::get('/user', [AuthController::class, 'profile']);
    Route::put('/user', [AuthController::class, 'updateProfile']);
    Route::patch('/user', [AuthController::class, 'updateProfile']);
    Route::delete('/user', [AuthController::class, 'deleteAccount']);
    Route::post('/user/update-verification', [AuthController::class, 'updateVerification']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Transaksi / Pemesanan
    Route::post('/bookings', [BookingController::class, 'checkout']);
    Route::get('/bookings/history', [BookingController::class, 'history']);
    Route::post('/bookings/cancel', [BookingController::class, 'cancel']);

    // GPS Relay Control
    Route::post('/gps/relay', [GpsController::class, 'updateRelayStatus']);

});
