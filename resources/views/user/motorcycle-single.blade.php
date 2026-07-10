@extends('user.layouts.app')

@section('content')
    <style>
        /* Fix Nice Select Option Hover Colors */
        
        /* 1. Base style for all options: Yellow BG, Black Text */
        .nice-select .list .option {
            background-color: var(--gorent-base) !important;
            color: var(--gorent-black) !important;
            transition: all 0.2s;
        }

        /* 2. Selected style (Normal): Black BG, Yellow Text */
        .nice-select .list .option.selected {
            background-color: var(--gorent-black) !important;
            color: var(--gorent-base) !important;
            font-weight: bold;
        }

        /* 3. Revert Selected style when List IS hovered (The "Switch" effect) */
        .nice-select .list:hover .option.selected {
            background-color: var(--gorent-base) !important; /* Back to Yellow */
            color: var(--gorent-black) !important; /* Back to Black */
        }

        /* 4. Hover style for ANY option: Black BG, Yellow Text */
        /* Must override rule #3, so we add specificity or target the selected state specifically when hovered */
        .nice-select .list .option:hover,
        .nice-select .list .option.focus,
        .nice-select .list:hover .option.selected:hover {
            background-color: var(--gorent-black) !important;
            color: var(--gorent-white) !important; /* Use White text for high contrast on Black, as requested in previous step "Hover State: Black BG... White Text" */
        }
    </style>
    <section class="page-header">
        <div class="page-header__bg"
            style="background-image: url('{{ $motorcycle->image_path ? asset('storage/' . $motorcycle->image_path) : asset('assets/images/resources/RIDEnotrasparan.png') }}');">
        </div>
        <div class="page-header__shape-1"
            style="background-image: url('{{ asset('assets/images/shapes/page-header-shape-1.png') }}');"></div>
        <div class="container">
            <div class="page-header__inner">
                <h3>{{ $motorcycle->brand }} {{ str_replace('_', ' ', ucfirst($motorcycle->type)) }}</h3>
                <div class="thm-breadcrumb__inner">
                    <ul class="thm-breadcrumb list-unstyled">
                        <li><a href="{{ route('welcome') }}">Home</a></li>
                        <li><span class="icon-arrow-left"></span></li>
                        <li><a href="{{ route('motorcycles.index') }}">Motorcycles</a></li>
                        <li><span class="icon-arrow-left"></span></li>
                        <li>{{ $motorcycle->brand }} {{ str_replace('_', ' ', ucfirst($motorcycle->type)) }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <section class="listing-single">
        <div class="container">
            <div class="listing-single__top">
                <div class="listing-single__top-left">
                    <h3 class="listing-single__title">{{ $motorcycle->brand }}
                        {{ str_replace('_', ' ', ucfirst($motorcycle->type)) }}</h3>
                    <p class="listing-single__sub-title">{{ $motorcycle->category }}</p>
                    <div class="listing-single__car-details-box">
                        <ul class="list-unstyled listing-single__car-details">
                            <li><span class="icon-date"></span>
                                <p>{{ \Carbon\Carbon::parse($motorcycle->created_at)->year }}</p>
                            </li>
                            <li><span class="icon-fuel-type"></span>
                                <p>{{ $motorcycle->fuel_configuration }}</p>
                            </li>
                            <li><span class="icon-Carrier"></span>
                                <p>{{ $motorcycle->transmission }}</p>
                            </li>
                            <li><span class="icon-engine"></span>
                                <p>{{ $motorcycle->cc }} CC</p>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="listing-single__top-right">
                    <h2 class="listing-single__price">Rp {{ number_format($motorcycle->price, 0, ',', '.') }}<span>/
                            Day</span></h2>
                </div>
            </div>

            <div class="row mb-5">
                <div class="col-md-10">
                    <div class="listing-single__main-content">
                        <div class="main-image-box">
                            @php
                                $mainImgUrl = asset('assets/images/resources/RIDEnotrasparan.png');
                                if ($motorcycle->image_path) {
                                    if (file_exists(public_path('storage/motorcycles/' . $motorcycle->image_path))) {
                                        $mainImgUrl = asset('storage/motorcycles/' . $motorcycle->image_path);
                                    } elseif (file_exists(public_path('storage/' . $motorcycle->image_path))) {
                                        $mainImgUrl = asset('storage/' . $motorcycle->image_path);
                                    } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($motorcycle->image_path)) {
                                         $mainImgUrl = asset('storage/' . $motorcycle->image_path);
                                    } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists('motorcycles/' . $motorcycle->image_path)) {
                                         $mainImgUrl = asset('storage/motorcycles/' . $motorcycle->image_path);
                                    }
                                }
                            @endphp
                            <img id="main-display-img"
                                src="{{ $mainImgUrl }}"
                                style="width: 100%; height: 500px; object-fit: cover; border-radius: 15px 0 0 15px;">
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div
                        style="height: 500px; overflow-y: auto; background: #f4f4f4; border-radius: 0 15px 15px 0; padding: 10px;">
                        <div class="d-flex flex-column gap-2">
                            <div class="thumbnail-item" style="cursor: pointer;">
                                <img src="{{ $mainImgUrl }}"
                                    style="width: 100%; height: 80px; object-fit: cover; border-radius: 5px; border: 2px solid #e62e2d;"
                                    onclick="changeMainImage(this.src, this)">
                            </div>
                            @foreach ($motorcycle->images as $img)
                                @php
                                    $galImgUrl = asset('assets/images/resources/RIDEnotrasparan.png');
                                    if ($img->image_path) {
                                        if (file_exists(public_path('storage/motorcycles/gallery/' . $img->image_path))) {
                                            $galImgUrl = asset('storage/motorcycles/gallery/' . $img->image_path);
                                        } elseif (file_exists(public_path('storage/' . $img->image_path))) {
                                            $galImgUrl = asset('storage/' . $img->image_path);
                                        } elseif (\Illuminate\Support\Facades\Storage::disk('public')->exists($img->image_path)) {
                                            $galImgUrl = asset('storage/' . $img->image_path);
                                        }
                                    }
                                @endphp
                                <div class="thumbnail-item" style="cursor: pointer;">
                                    <img src="{{ $galImgUrl }}"
                                        style="width: 100%; height: 80px; object-fit: cover; border-radius: 5px; opacity: 0.7;"
                                        onclick="changeMainImage(this.src, this)">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-xl-8 col-lg-7">
                    <div class="listing-single__car-overview">
                        <h3 class="listing-single__car-overview-title">Motorcycle Overview</h3>
                        <p class="listing-single__text">{{ $motorcycle->description ?? 'No description available.' }}</p>

                        <h3 class="listing-single__car-overview-title mt-5">Specifications</h3>
                        <ul class="list-unstyled listing-single__car-overview-point">
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span><i class="icon-car1"></i> Brand</span>
                                <strong>{{ $motorcycle->brand }}</strong>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span><i class="icon-car1"></i> Type</span>
                                <strong>{{ str_replace('_', ' ', ucfirst($motorcycle->type)) }}</strong>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span><i class="icon-car-washing"></i> Category</span>
                                <strong>{{ $motorcycle->category }}</strong>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span><i class="icon-fuel-type"></i> Fuel</span>
                                <strong>{{ $motorcycle->fuel_configuration }}</strong>
                            </li>
                            <li class="d-flex justify-content-between border-bottom py-2">
                                <span><i class="icon-gear"></i> Transmission</span>
                                <strong>{{ $motorcycle->transmission }}</strong>
                            </li>
                             <li class="d-flex justify-content-between border-bottom py-2">
                                <span><i class="icon-engine"></i> Engine (CC)</span>
                                <strong>{{ $motorcycle->cc }} CC</strong>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="col-xl-4 col-lg-5">
                    <div class="listing-single__sidebar">
                        <div class="listing-single__rent-car listing-single__single-box shadow p-4 bg-white rounded">
                            <h3 class="listing-single__rent-car-title mb-4">Book This Motorcycle</h3>
                            <form id="booking-form">
                                @csrf
                                <input type="hidden" name="motorcycle_id" value="{{ $motorcycle->id }}"
                                    id="motorcycle_id">

                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold">Start Date</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="small font-weight-bold">End Date</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label for="delivery_type">Delivery Type</label>
                                    <select id="delivery_type" name="delivery_type" class="form-control">
                                        <option value="pickup">Pick Up (Ambil Sendiri)</option>
                                        <option value="delivery">Delivery (Antar ke Lokasi)</option>
                                    </select>
                                </div>

                                <div id="address-container" style="display: none; margin-top: 15px;">
                                    <label for="delivery_address">Delivery Address</label>
                                    <input type="text" id="delivery_address" name="delivery_address" class="form-control"
                                        placeholder="Type your address or street name...">
                                    <div id="address-results"
                                        style="background: white; border: 1px solid #ddd; display: none; position: absolute; z-index: 1000; width: 100%;">
                                    </div>

                                    <input type="hidden" name="latitude" id="latitude">
                                    <input type="hidden" name="longitude" id="longitude">
                                    <input type="hidden" name="distance_km" id="distance_km">

                                    <small id="distance-info" class="text-primary mt-2 d-block"></small>
                                </div>

                                <div class="mt-4">
                                    <h6 class="small font-weight-bold">Add Extra Accessories:</h6>
                                    <ul class="list-unstyled">
                                        @foreach ($accessories as $acc)
                                            <li class="d-flex justify-content-between align-items-center mb-2">
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input"
                                                        name="accessories[]" id="acc_{{ $acc->id }}"
                                                        value="{{ $acc->id }}"
                                                        data-price="{{ $acc->daily_price }}"> <!-- Pastikan ini integer di DB -->
                                                    <label class="custom-control-label small"
                                                        for="acc_{{ $acc->id }}">{{ $acc->accessory_name }}</label>
                                                </div>
                                                <span class="small text-muted">Rp
                                                    {{ number_format($acc->daily_price, 0, ',', '.') }}</span>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="bg-light p-3 rounded mt-4" id="price-summary">
                                    <div class="d-flex justify-content-between small">
                                        <span>Rental Duration</span>
                                        <div><span id="total-days">0</span> Days</div>
                                    </div>
                                    <div class="d-flex justify-content-between small mt-1">
                                        <span>Motorcycle Price</span>
                                        <span id="base-price-display">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between small mt-1">
                                        <span>Accessories</span>
                                        <span id="accessories-price-display">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between small mt-1">
                                        <span>Delivery Fee</span>
                                        <span id="delivery-fee-display">Rp 0</span>
                                    </div>
                                    <div class="d-flex justify-content-between font-weight-bold border-top mt-2 pt-2 text-danger">
                                        <span>Total Payable</span>
                                        <span id="total-payable-display">Rp 0</span>
                                    </div>
                                </div>
                                    <button type="button" id="pay-button" class="thm-btn w-100 mt-4 d-flex justify-content-center align-items-center">Rent Now</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Midtrans Snap is loaded globally in the header to avoid duplicate loads -->
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('services.midtrans.client_key') }}">
    </script>
    <script>
        // Gallery Image Switcher
        function changeMainImage(src, element) {
            // Update Main Image
            document.getElementById('main-display-img').src = src;
            
            // Get all thumbnails
            const thumbnails = document.querySelectorAll('.thumbnail-item img');
            
            // Reset styles for all
            thumbnails.forEach(img => {
                img.style.border = 'none';
                img.style.opacity = '0.7';
            });
            
            // Set style for active
            element.style.border = '2px solid #e62e2d'; // Theme Red
            element.style.opacity = '1';
        }

        document.addEventListener('DOMContentLoaded', function() {
            // 1. KONFIGURASI
            const storeLoc = {
                lat: -8.798599,
                lng: 115.162452
            }; // Lokasi RideNusa
            const pricePerKm = 5000; // Ongkos kirim per KM
            const bikePricePerDay = {{ $motorcycle->price ?? 0 }}; // Mengambil harga motor dari PHP (Safe Fallback)

            // 2. ELEMENT SELECTOR
            const deliveryTypeSelect = document.getElementById('delivery_type');
            const addressContainer = document.getElementById('address-container');
            const addressInput = document.getElementById('delivery_address');
            const resultsDiv = document.getElementById('address-results');
            const distanceInfo = document.getElementById('distance-info');
            const accessoryCheckboxes = document.querySelectorAll('input[name="accessories[]"]');

            const bookingForm = document.getElementById('booking-form');
            if (bookingForm) {
                bookingForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                });
            }

            let currentDeliveryFee = 0;

            // 3. FUNGSI TOGGLE ALAMAT
            function toggleAddressInput() {
                if (deliveryTypeSelect.value === 'delivery') {
                    addressContainer.style.display = 'block';
                    addressInput.setAttribute('required', 'required');
                } else {
                    addressContainer.style.display = 'none';
                    addressInput.removeAttribute('required');
                    addressInput.value = '';
                    distanceInfo.innerText = '';
                    currentDeliveryFee = 0;
                    calculateTotal();
                }
            }

            // Jalankan saat ada perubahan dropdown
            // Tambahkan listener jQuery untuk kompatibilitas dengan plugin tema (seperti NiceSelect)
            if (typeof jQuery !== 'undefined') {
                jQuery('#delivery_type').on('change', toggleAddressInput);
            }
            deliveryTypeSelect.addEventListener('change', toggleAddressInput);
            
            // Panggil sekali saat halaman dimuat untuk memastikan status awal benar
            toggleAddressInput();

            // 4. AUTOCOMPLETE ALAMAT (OSM NOMINATIM)
            addressInput.addEventListener('input', function() {
                const query = this.value;
                if (query.length < 3) {
                    resultsDiv.style.display = 'none';
                    return;
                }

                // Cari alamat di Bali menggunakan OpenStreetMap
                fetch(
                        `https://nominatim.openstreetmap.org/search?format=json&q=${query}&viewbox=114.4,-8.1,115.7,-8.9&bounded=1`)
                    .then(res => res.json())
                    .then(data => {
                        resultsDiv.innerHTML = '';
                        if (data.length > 0) {
                            resultsDiv.style.display = 'block';
                            data.slice(0, 5).forEach(item => {
                                const div = document.createElement('div');
                                div.style.padding = '10px';
                                div.style.cursor = 'pointer';
                                div.style.borderBottom = '1px solid #eee';
                                div.innerHTML =
                                    `<i class="fa fa-map-marker-alt"></i> ${item.display_name}`;

                                div.onclick = function() {
                                    addressInput.value = item.display_name;
                                    resultsDiv.style.display = 'none';

                                    // Simpan Koordinat ke input sesuai nama di form
                                    const latEl = document.getElementById('latitude');
                                    const lngEl = document.getElementById('longitude');
                                    if (latEl) latEl.value = item.lat;
                                    if (lngEl) lngEl.value = item.lon;

                                    // Hitung Jarak & Ongkir
                                    const dist = calculateDistance(storeLoc.lat, storeLoc
                                        .lng, parseFloat(item.lat), parseFloat(item.lon)
                                        );
                                    currentDeliveryFee = Math.ceil(dist) * pricePerKm;

                                    document.getElementById('distance_km').value = dist
                                        .toFixed(2);
                                    distanceInfo.innerText =
                                        `Jarak: ${dist.toFixed(1)} km (Ongkir: Rp ${currentDeliveryFee.toLocaleString('id-ID')})`;

                                    calculateTotal();
                                };
                                resultsDiv.appendChild(div);
                            });
                        }
                    });

            // Prevent form submit on Enter key inside the address input
            addressInput.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                }
            });

            // 5. FUNGSI HITUNG TOTAL
            function calculateTotal() {
                const startDateInput = document.getElementById('start_date');
                const endDateInput = document.getElementById('end_date');
                const start = startDateInput.value ? new Date(startDateInput.value) : null;
                const end = endDateInput.value ? new Date(endDateInput.value) : null;

                let days = 0;
                // Pastikan kedua tanggal valid dan tanggal akhir tidak sebelum tanggal mulai
                if (start && end && end >= start) {
                    // Tambah 1 untuk membuat periode sewa inklusif dengan hari terakhir
                    days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
                }

                // Hitung biaya dasar sewa motor
                const basePrice = days * bikePricePerDay;

                // Hitung total biaya aksesori
                let accessoriesTotal = 0;
                // Untuk display aksesori, jika user belum pilih tanggal (days=0), 
                // kita anggap 1 hari agar user bisa lihat estimasi harga aksesori.
                const calculationDays = days > 0 ? days : 1; 

                accessoryCheckboxes.forEach(acc => {
                    if (acc.checked) {
                        accessoriesTotal += parseFloat(acc.dataset.price) * calculationDays;
                    }
                });
                
                const displayAccessoriesTotal = accessoriesTotal; 

                // Kalkulasi total akhir untuk Payable.
                // Jika user belum pilih hari (days=0), maka Total Payable hanya menampilkan:
                // Base Price (0) + Accessories (1 day preview) + Delivery Fee.
                // Ini memberikan feedback harga langsung ke user.
                const total = basePrice + displayAccessoriesTotal + currentDeliveryFee;

                // Update elemen display
                document.getElementById('total-days').innerText = days;
                document.getElementById('base-price-display').innerText = 'Rp ' + basePrice.toLocaleString('id-ID');
                document.getElementById('accessories-price-display').innerText = 'Rp ' + displayAccessoriesTotal.toLocaleString('id-ID');
                document.getElementById('delivery-fee-display').innerText = 'Rp ' + currentDeliveryFee.toLocaleString('id-ID');
                document.getElementById('total-payable-display').innerText = 'Rp ' + total.toLocaleString('id-ID');
            }

            // 6. RUMUS HAVERSINE (HITUNG JARAK)
            function calculateDistance(lat1, lon1, lat2, lon2) {
                const R = 6371;
                const dLat = (lat2 - lat1) * Math.PI / 180;
                const dLon = (lon2 - lon1) * Math.PI / 180;
                const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                    Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) * Math.sin(dLon / 2) * Math.sin(
                        dLon / 2);
                return R * (2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a)));
            }

            // Pasang listener pada tanggal agar total update otomatis
            const startDateElem = document.getElementById('start_date');
            const endDateElem = document.getElementById('end_date');

            startDateElem.addEventListener('change', function() {
                if (this.value) {
                    // Set minimal End Date sama dengan Start Date
                    endDateElem.min = this.value;

                    // Hitung maksimal End Date (Start Date + 30 hari)
                    const start = new Date(this.value);
                    const maxDate = new Date(start);
                    maxDate.setDate(start.getDate() + 30);

                    // Format tanggal ke YYYY-MM-DD
                    const yyyy = maxDate.getFullYear();
                    const mm = String(maxDate.getMonth() + 1).padStart(2, '0');
                    const dd = String(maxDate.getDate()).padStart(2, '0');
                    const maxDateString = `${yyyy}-${mm}-${dd}`;

                    endDateElem.max = maxDateString;

                    // Jika End Date yang sudah dipilih sebelumnya tidak valid (diluar range), reset
                    if (endDateElem.value && (endDateElem.value > maxDateString || endDateElem.value < this.value)) {
                        endDateElem.value = '';
                        Swal.fire({
                            icon: 'warning',
                            title: 'Rental Duration Limit',
                            text: 'Maximum rental period is 30 days.',
                            confirmButtonColor: '#d33'
                        });
                    }
                }
                calculateTotal();
            });

            endDateElem.addEventListener('change', calculateTotal);

            // Pasang listener pada checkbox aksesori
            accessoryCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', calculateTotal);
            });

            // HANDLE: Rent Now -> create checkout & call Midtrans Snap
            const payButton = document.getElementById('pay-button');
            payButton.addEventListener('click', async function() {
                calculateTotal();

                const start = document.getElementById('start_date').value;
                const end = document.getElementById('end_date').value;
                if (!start || !end) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Missing Rental Dates',
                        text: 'Please select both start and end dates for your rental.',
                        confirmButtonColor: '#d33'
                    });
                    return;
                }

                // If delivery is selected, ensure address and coordinates are present
                if (deliveryTypeSelect.value === 'delivery') {
                    const addr = document.getElementById('delivery_address') ? document.getElementById('delivery_address').value.trim() : '';
                    const lat = document.getElementById('latitude') ? document.getElementById('latitude').value.trim() : '';
                    const lng = document.getElementById('longitude') ? document.getElementById('longitude').value.trim() : '';
                    if (!addr || !lat || !lng) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Delivery Address Required',
                            text: 'Please select a delivery address from the suggestions to save coordinates.',
                            confirmButtonColor: '#f39c12'
                        });
                        return;
                    }
                }

                const token = document.querySelector('input[name="_token"]').value;

                const payload = {
                    motorcycle_id: document.getElementById('motorcycle_id').value,
                    start_date: start,
                    end_date: end,
                    delivery_type: document.getElementById('delivery_type').value,
                    delivery_address: document.getElementById('delivery_address') ? document.getElementById('delivery_address').value : null,
                    latitude: document.getElementById('latitude') ? document.getElementById('latitude').value : null,
                    longitude: document.getElementById('longitude') ? document.getElementById('longitude').value : null,
                    distance_km: document.getElementById('distance_km').value || 0,
                    accessories: Array.from(document.querySelectorAll('input[name="accessories[]"]:checked')).map(i => i.value)
                };

                // Debug: print payload to console to help trace missing fields
                console.log('Checkout payload:', payload);

                try {
                    const res = await fetch("{{ route('booking.checkout') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify(payload)
                    });

                    const json = await res.json();
                    if (!res.ok) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Checkout Failed',
                            text: json.error || 'A server error occurred during checkout. Please try again.',
                            confirmButtonColor: '#d33'
                        });
                        return;
                    }

                    if (json.snap_token) {
                        window.snap.pay(json.snap_token, {
                            onSuccess: function(result) {
                                // Optimistically notify server that client completed payment, then redirect
                                fetch("{{ route('payment.client_confirm') }}", {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        order_id: result.order_id || result.orderId || null,
                                        transaction_status: result.transaction_status || result.transactionStatus || 'success'
                                    })
                                }).finally(() => {
                                    window.location = "{{ route('booking.success') }}";
                                });
                            },
                            onPending: function(result) {
                                window.location = "{{ route('booking.success') }}";
                            },
                            onError: function(err) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Payment Error',
                                    text: 'An error occurred during the payment process. Please try again.',
                                    confirmButtonColor: '#d33'
                                });
                                console.error(err);
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Payment Token Missing',
                            text: 'Payment authorization token was not received. Please try again.',
                            confirmButtonColor: '#d33'
                        });
                    }
                } catch (err) {
                    console.error(err);
                    Swal.fire({
                        icon: 'error',
                        title: 'Connection Error',
                        text: 'Failed to connect to the server. Please check your internet connection and try again.',
                        confirmButtonColor: '#d33'
                    });
                }
            });
        });
    </script>
@endsection
