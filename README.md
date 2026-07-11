<p align="center">
  <img src="./Mobile/assets/images/logo_ridenusa_white.png" width="150" alt="RideNusa Logo" />
</p>

<h1 align="center">RideNusa</h1>

<p align="center">
  <b>Sistem Penyewaan Motor & IoT Control Berbasis Mobile & Web</b>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-10.x-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel" />
  <img src="https://img.shields.io/badge/Flutter-Framework-02569B?style=flat-square&logo=flutter&logoColor=white" alt="Flutter" />
  <img src="https://img.shields.io/badge/Platform-Android-3DDC84?style=flat-square&logo=android&logoColor=white" alt="Android" />
  <img src="https://img.shields.io/badge/Database-MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white" alt="MySQL" />
  <img src="https://img.shields.io/badge/License-MIT-yellow?style=flat-square" alt="License" />
</p>

<p align="center">
  RideNusa adalah platform digital penyewaan sepeda motor modern terintegrasi. Dirancang untuk memudahkan penyewa memesan kendaraan melalui <b>Aplikasi Mobile (Flutter)</b> serta membantu pemilik rental mengelola administrasi, memantau posisi unit, dan mengontrol kelistrikan mesin secara <i>real-time</i> via <b>Dashboard Web Admin (Laravel)</b> berbasis IoT (GPS Tracker & Relay).
</p>

---

<p align="center">
  <img src="https://media.giphy.com/media/L33oPLIhkxgW0yCu1A/giphy.gif" width="220" alt="RideNusa Motorcycle Ride" />
</p>

---

## 📥 Download Aplikasi Mobile (RideNusa APK)

Bagi penguji, dosen, atau responden yang ingin langsung menginstal aplikasi mobile RideNusa di smartphone Android tanpa melakukan kompilasi kode sumber, silakan unduh berkas APK rilis di bawah ini:

*   🚀 **[Download APK (arm64-v8a) - Rekomendasi HP Modern](./Mobile/release/app-arm64-v8a-release.apk)**  
    *(Ukuran lebih ringan, performa optimal, cocok untuk 95% HP Android keluaran terbaru).*
*   📱 **[Download APK (armeabi-v7a) - Untuk HP Tipe Lama](./Mobile/release/app-armeabi-v7a-release.apk)**  
    *(Cocok untuk perangkat Android 32-bit/tipe lama).*

> 💡 **Petunjuk Instalasi APK:**
> 1. Unduh file `.apk` di atas melalui smartphone Anda.
> 2. Buka file hasil unduhan, lalu berikan izin *"Install from Unknown Sources"* (Instal dari sumber tidak dikenal) jika diminta oleh sistem keamanan Android.
> 3. Selesaikan proses instalasi dan aplikasi siap digunakan.
>
> *(Catatan untuk Pengembang: Pastikan Anda telah menyalin berkas APK hasil build dari `Mobile/build/app/outputs/flutter-apk/` ke dalam folder `Mobile/release/` sebelum melakukan commit/push ke repositori).*

---

## ✨ Fitur Utama (Key Features)

### 📱 Sisi Aplikasi Mobile (Penyewa)
*   **Katalog Motor & Filter:** Pencarian sepeda motor berdasarkan kategori transmisi, cc, dan harga sewa.
*   **Verifikasi Identitas:** Unggah foto SIM C untuk keamanan verifikasi sebelum melakukan penyewaan.
*   **Pemesanan Fleksibel (Booking):** Pilihan waktu sewa serta opsi layanan pengantaran motor (*delivery*) langsung ke lokasi pelanggan.
*   **Pembayaran Otomatis:** Integrasi *Midtrans Payment Gateway* (E-Wallet, Transfer Bank, QRIS).

### 💻 Sisi Dashboard Web Admin (Operator)
*   **Verifikasi Pengguna:** Modul verifikasi dokumen SIM C untuk menyetujui akun baru.
*   **Manajemen Inventaris:** Pengelolaan armada motor beserta data aksesoris pendukung (helm, jas hujan).
*   **Kontrol Pengembalian & Denda:** Fitur otomatisasi hitung denda keterlambatan serta pencatatan denda kerusakan fisik motor dengan catatan lengkap.
*   **Kontrol IoT & GPS (Smart Security):** 
    *   **Real-time GPS Tracking:** Memantau sebaran lokasi unit motor sewaan di peta Google Maps.
    *   **Engine Kill Switch (Relay):** Memutus kelistrikan koil/starter motor jarak jauh langsung dari dashboard admin untuk mengantisipasi pencurian.
*   **Servis & Perawatan:** Modul monitoring riwayat servis unit motor secara berkala.

---

## 🛠️ Prasyarat (Prerequisites)

Sebelum memulai, pastikan perangkat Anda telah terpasang:
*   **Web Server:** Laragon (Sangat direkomendasikan untuk Windows) atau XAMPP.
*   **PHP:** Versi 8.0 ke atas.
*   **Composer:** Dependensi manager PHP.
*   **Node.js & NPM:** Untuk membangun aset frontend.
*   **Flutter SDK:** Versi terbaru untuk menjalankan aplikasi mobile.
*   **Android Studio / VS Code:** Sebagai Text Editor dan Emulator Android/iOS.

---

## 🚀 Panduan Instalasi & Konfigurasi

<details>
<summary><b>💻 Langkah 1: Setup Web Backend & Admin Panel (Laravel)</b></summary>

### 1. Penyiapan Folder di Laragon
1. Pindahkan folder proyek `PenyewaanMotor` ke direktori root Laragon Anda (biasanya di `C:\laragon\www\PenyewaanMotor`).
2. Jalankan aplikasi **Laragon**, lalu klik tombol **Start All** untuk menyalakan server Apache dan MySQL.

### 2. Konfigurasi File Environment (`.env`)
1. Salin berkas `.env.example` di direktori utama, lalu ganti namanya menjadi `.env`.
2. Buka berkas `.env` tersebut dan sesuaikan kredensial koneksi database MySQL:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=db_ridenusa   # Nama database di phpMyAdmin
   DB_USERNAME=root
   DB_PASSWORD=
   ```
3. Tambahkan konfigurasi API Midtrans Sandbox Anda di bagian paling bawah berkas `.env`:
   ```env
   MIDTRANS_SERVER_KEY=your_server_key_here
   MIDTRANS_CLIENT_KEY=your_client_key_here
   MIDTRANS_IS_PRODUCTION=false
   ```

### 3. Jalankan Instalasi Dependensi
Buka terminal/command prompt di direktori root proyek (`PenyewaanMotor`), lalu jalankan berturut-turut:
```bash
# 1. Instal library PHP
composer install

# 2. Hasilkan kunci enkripsi aplikasi
php artisan key:generate

# 3. Hubungkan penyimpanan media ke publik
php artisan storage:link

# 4. Instal package Javascript & jalankan aset compiler
npm install
npm run dev
```

### 4. Setup Database & Seeder
1. Buat database baru bernama `db_ridenusa` di phpMyAdmin.
2. Jalankan migrasi tabel beserta data contoh bawaan (*seeder*) menggunakan perintah:
   ```bash
   php artisan migrate --seed
   ```

### 5. Mengakses Dashboard Web Admin
*   Jika menggunakan Laragon, buka browser dan akses URL: **`http://PenyewaanMotor.test`**
*   Atau Anda dapat menjalankannya secara manual dengan perintah `php artisan serve` lalu buka **`http://127.0.0.1:8000/login`**.
*   **Kredensial Default Admin:**
    *   *Email:* `admin@gmail.com`
    *   *Password:* `password`

</details>

<details>
<summary><b>📱 Langkah 2: Setup Aplikasi Mobile (Flutter)</b></summary>

### 1. Masuk ke Folder Mobile & Instal Dependensi
Buka terminal baru di direktori root proyek, lalu jalankan:
```bash
cd Mobile
flutter pub get
```

### 2. Konfigurasi Endpoint API
Hubungkan aplikasi Flutter dengan server Laravel lokal dengan membuka file `Mobile/lib/REST-API/api_config.dart`:
```dart
class ApiConfig {
  // Ganti URL dengan IP komputer lokal Anda atau URL Ngrok Anda
  static const String baseUrl = 'https://URL_NGROK_ATAU_IP_LOKAL.ngrok-free.dev/api';
  static const String imageUrl = 'https://URL_NGROK_ATAU_IP_LOKAL.ngrok-free.dev/storage';
  static const String chatbotWebhookUrl = 'https://URL_CHATBOT.ngrok-free.dev/webhook/...';
}
```
*   *Catatan:* Jika menggunakan HP fisik, pastikan HP dan laptop terhubung ke jaringan Wi-Fi yang sama, lalu gunakan IP lokal laptop Anda (contoh: `192.168.1.15`). Jika menggunakan emulator Android, gunakan IP `10.0.2.2`.

### 3. Menjalankan Aplikasi Mobile
Hubungkan perangkat Android fisik (aktifkan USB Debugging) atau buka Emulator, lalu jalankan:
```bash
flutter run
```

</details>

---

## 📡 Panduan Uji Coba Fitur IoT (GPS & Relay)

Aplikasi RideNusa mendukung pemutus arus mesin (*kill switch*) dan pelacakan lokasi secara terintegrasi:

1.  **Pelacakan Lokasi (GPS):**
    *   Pastikan perangkat GPS tracker dalam kondisi menyala (*online*).
    *   Buka menu **Devices & Map** di Dashboard Admin untuk memantau titik koordinat lokasi motor sewaan secara *real-time* di Google Maps.
2.  **Mekanisme Pemutus Arus (Relay):**
    *   Buka halaman kelola penyewaan di Web Admin.
    *   Klik tombol **Disable Engine**. Server backend Laravel akan mengirim perintah API ke alat GPS tracker secara nirkabel (via GPRS) untuk mengaktifkan modul relay fisik, sehingga kelistrikan koil/starter terputus dan mesin motor mati seketika.
    *   Klik **Enable Engine** untuk menyambungkan kembali arus kelistrikan agar motor dapat di-starter kembali.

---

## 📄 Lisensi

Platform RideNusa dirilis di bawah **[MIT License](https://opensource.org/licenses/MIT)**.
