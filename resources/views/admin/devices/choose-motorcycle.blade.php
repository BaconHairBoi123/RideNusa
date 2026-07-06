<x-admin-layout title="Pairing GPS Device to Motorcycle">

    <div class="max-w-2xl mx-auto">
        <h1 class="text-2xl font-bold mb-6">Pairing GPS Device to Motorcycle</h1>
        <p class="text-gray-600 mb-6">Cari motor berdasarkan plat nomer, lalu pilih device GPS yang akan dipasang</p>

        <div class="bg-white p-6 rounded shadow">
            <label class="block text-sm font-bold text-gray-700 mb-2">Cari Motor (Plat Nomer / Brand / Type)*</label>
            <input 
                type="text" 
                id="motorcycle-autocomplete-device" 
                placeholder="Contoh: B 1234 AB atau Honda atau CB"
                class="w-full border p-3 rounded mb-4 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                autocomplete="off"
            >

            <div id="search-results" class="space-y-2 mb-4">
                <!-- Results akan muncul di sini -->
            </div>
        </div>
    </div>

    <script>
        const searchInput = document.getElementById('motorcycle-autocomplete-device');
        const searchResults = document.getElementById('search-results');
        let searchTimeout;

        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.trim();
            
            clearTimeout(searchTimeout);
            
            if (query.length < 1) {
                searchResults.innerHTML = '';
                return;
            }

            // Debounce search
            searchTimeout = setTimeout(() => {
                fetch(`{{ route('admin.devices.search_motorcycle') }}?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        searchResults.innerHTML = '';

                        if (data.status === 'success' && data.data.length > 0) {
                            data.data.forEach(bike => {
                                const div = document.createElement('div');
                                div.className = 'p-4 border rounded cursor-pointer hover:bg-blue-50 hover:border-blue-400 transition';
                                div.innerHTML = `
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <div class="font-bold text-lg text-blue-600">${bike.license_plate}</div>
                                            <div class="text-sm text-gray-700">${bike.brand} ${bike.type}</div>
                                            <div class="text-xs text-gray-500 mt-1">${bike.device_status}</div>
                                        </div>
                                        <i class="ri-arrow-right-line text-gray-400"></i>
                                    </div>
                                `;
                                div.onclick = () => goToAssign(bike.id);
                                searchResults.appendChild(div);
                            });
                        } else {
                            const div = document.createElement('div');
                            div.className = 'p-4 text-center text-gray-500';
                            div.textContent = 'Tidak ada motor ditemukan';
                            searchResults.appendChild(div);
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        searchResults.innerHTML = '<div class="p-4 text-center text-red-500">Error mencari motor</div>';
                    });
            }, 300); // Debounce 300ms
        });

        function goToAssign(motorcycleId) {
            window.location.href = `{{ route('admin.devices.assign', ':id') }}`.replace(':id', motorcycleId);
        }
    </script>

</x-admin-layout>

