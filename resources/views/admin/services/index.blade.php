<x-admin-layout title="Service & Maintenance">

    <h1 class="text-2xl font-bold mb-4">Service & Maintenance</h1>

    @if (session('success'))
        <div class="bg-green-100 text-green-700 p-2 rounded mb-3">
            {{ session('success') }}
        </div>
    @endif
    {{-- 1. BAGIAN PENCARIAN MOTOR --}}
    <div class="bg-white p-6 rounded-lg shadow-md mb-6 border-l-4 border-blue-500">
        <h2 class="text-lg font-semibold mb-4 text-gray-700">Langkah 1: Pilih Kendaraan</h2>
        <div class="relative">
            <input type="text" id="motorcycle-autocomplete-service" placeholder="Ketik Plat Nomor atau Merk Motor... (Contoh: B 1234 ABC)"
                class="w-full border-2 border-gray-200 p-3 rounded-lg focus:border-blue-500 focus:outline-none transition">

            {{-- Hasil pencarian akan muncul melayang di bawah input ini --}}
            <div id="search-results"
                class="hidden absolute z-10 w-full bg-white border shadow-xl rounded-b-lg max-h-60 overflow-y-auto">
            </div>
        </div>

        {{-- Info Motor yang Terpilih --}}
        <div id="selected-motor-info"
            class="hidden mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md flex justify-between items-center">
            <div>
                <span class="text-sm text-blue-600 font-bold uppercase">Motor Terpilih:</span>
                <p id="motor-display-name" class="text-lg font-bold text-gray-800"></p>
            </div>
            <button type="button" onclick="resetSelection()"
                class="text-red-500 hover:text-red-700 text-sm underline">Ganti Motor</button>
        </div>
    </div>

    {{-- 2. FORM DETAIL SERVICE (Hanya muncul jika motor sudah dipilih) --}}
    <div id="service-form-section" class="hidden bg-white p-6 rounded-lg shadow-md mb-6 opacity-50 pointer-events-none">
        <form method="POST" action="{{ route('admin.services.store') }}">
            @csrf
            <input type="hidden" name="motorcycle_id" id="motor-id-input">

            <h2 class="text-lg font-semibold mb-4 text-gray-700">Langkah 2: Detail Service</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Tanggal Service</label>
                    <input type="date" name="service_date" value="{{ date('Y-m-d') }}" required
                        class="w-full border p-2 rounded">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Kilometer Saat Ini</label>
                    <input type="number" name="kilometer" required placeholder="00000"
                        class="w-full border p-2 rounded">
                </div>
            </div>

            <h2 class="text-sm font-bold mb-3 text-gray-500 uppercase tracking-wider">Jenis Pekerjaan:</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                @foreach ($serviceTypes as $st)
                    <label
                        class="group flex items-center gap-3 p-3 border rounded-xl hover:bg-blue-50 cursor-pointer transition">
                        <input type="checkbox" name="services[]" value="{{ $st->id }}"
                            class="w-5 h-5 text-blue-600">
                        <span class="text-gray-700 group-hover:text-blue-700">{{ $st->service_name }}</span>
                    </label>
                @endforeach
            </div>

            <button
                class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-lg shadow-lg transition">
                Simpan Riwayat Service
            </button>
        </form>
    </div>

    {{-- LIST RIWAYAT SERVIS --}}
    <div class="bg-white shadow rounded overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2">Motor</th>
                    <th class="p-2">Service Types</th>
                    <th class="p-2">Date</th>
                    <th class="p-2">KM</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($services as $s)
                    @php
                        $detail = DB::table('motorcycle_service_details')
                            ->join(
                                'service_types',
                                'motorcycle_service_details.service_type_id',
                                '=',
                                'service_types.id',
                            )
                            ->where('motorcycle_service_details.service_id', $s->id)
                            ->pluck('service_name')
                            ->toArray();
                    @endphp

                    <tr class="border-t text-center">
                        <td class="p-2">
                            {{ $s->brand }} {{ $s->type }} ({{ $s->license_plate }})
                        </td>
                        <td class="p-2">{{ implode(', ', $detail) }}</td>
                        <td class="p-2">{{ $s->service_date }}</td>
                        <td class="p-2">{{ $s->kilometer }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <script>
        const motorcycles = @json($motorcycles);
        const searchInput = document.getElementById('motorcycle-autocomplete-service');
        const resultsDiv = document.getElementById('search-results');
        const formSection = document.getElementById('service-form-section');

        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            if (query.length < 2) {
                resultsDiv.classList.add('hidden');
                return;
            }

            const filtered = motorcycles.filter(m =>
                m.license_plate.toLowerCase().includes(query) ||
                m.brand.toLowerCase().includes(query) ||
                m.type.toLowerCase().includes(query)
            );

            let html = '';
            filtered.forEach(m => {
                html += `<div onclick="selectMotor(${m.id}, '${m.brand} ${m.type} - ${m.license_plate}')" 
                      class="p-3 hover:bg-gray-100 cursor-pointer border-b">
                    ${m.brand} ${m.type} <span class="font-bold text-blue-600">(${m.license_plate})</span>
                 </div>`;
            });

            resultsDiv.innerHTML = html;
            resultsDiv.classList.remove('hidden');
        });

        function selectMotor(id, name) {
            document.getElementById('motor-id-input').value = id;
            document.getElementById('motor-display-name').innerText = name;

            // Tampilkan info terpilih & buka form bawah
            document.getElementById('selected-motor-info').classList.remove('hidden');
            resultsDiv.classList.add('hidden');
            searchInput.classList.add('hidden');

            formSection.classList.remove('hidden', 'opacity-50', 'pointer-events-none');
        }

        function resetSelection() {
            location.reload(); // Cara paling aman untuk reset form
        }
    </script>

</x-admin-layout>
