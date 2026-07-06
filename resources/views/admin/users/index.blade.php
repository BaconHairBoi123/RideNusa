<x-admin-layout title="Users Management">

    <h1 class="text-3xl font-bold mb-6">Customer List</h1>

    @if(session('success'))
        <div class="p-3 mb-4 bg-green-100 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    {{-- FORM PENCARIAN --}}
   {{-- FORM PENCARIAN --}}
<div class="mb-6">
    <form id="search-form" action="{{ route('admin.users.index') }}" method="GET" class="flex gap-2">
        <input type="text" 
               id="search-input"
               name="search" 
               value="{{ request('search') }}" 
               placeholder="Ketik nama atau email untuk mencari..." 
               class="w-full max-w-md border border-gray-300 p-2 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
        
        {{-- Tombol reset muncul jika ada pencarian --}}
        <a href="{{ route('admin.users.index') }}" 
           id="reset-btn"
           class="{{ request('search') ? '' : 'hidden' }} bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition">
            Reset
        </a>
    </form>
</div>

{{-- Tambahkan ID pada container table agar bisa diganti isinya --}}
<div id="user-table-container">
    <div class="bg-white p-6 rounded-xl shadow">
        <table class="w-full table-auto">
            {{-- ... isi table anda tetap sama ... --}}
            <thead>
                <tr class="text-left border-b">
                    <th class="py-2">Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th class="py-2 text-center">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                <tr class="border-b">
                    <td class="py-2">{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->phone_number ?? '-' }}</td>
                    <td>
                        @if ($user->verification_status === 'verified')
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">Verified</span>
                        @else
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700">Unverified</span>
                        @endif
                    </td>
                    <td class="p-3 text-center">
                        <div class="flex justify-center items-center gap-2">
                            <a href="{{ route('admin.users.show', $user->id) }}" class="px-4 py-1.5 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 transition shadow-sm font-medium">View</a>
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="px-4 py-1.5 bg-yellow-500 text-white rounded text-sm hover:bg-yellow-600 transition shadow-sm font-medium">Edit</a>
                            <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" class="inline">
                                @csrf @method('DELETE')
                                <button class="px-4 py-1.5 bg-red-600 text-white rounded text-sm hover:bg-red-700 transition shadow-sm font-medium" onclick="return confirm('Delete?')">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="py-10 text-center text-gray-500">Data tidak ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">
            {{ $users->withQueryString()->links('pagination::tailwind') }}
        </div>
    </div>
</div>

<script>
    const searchInput = document.getElementById('search-input');
    const tableContainer = document.getElementById('user-table-container');
    const resetBtn = document.getElementById('reset-btn');
    let timeout = null;

    searchInput.addEventListener('keyup', function() {
        // Hapus timeout sebelumnya agar tidak spam request ke server
        clearTimeout(timeout);

        // Tunggu 500ms setelah user berhenti mengetik, baru jalankan pencarian
        timeout = setTimeout(function() {
            const searchValue = searchInput.value;
            
            // Tampilkan/Sembunyikan tombol reset
            if(searchValue.length > 0) {
                resetBtn.classList.remove('hidden');
            } else {
                resetBtn.classList.add('hidden');
            }

            // Lakukan request AJAX secara background
            fetch(`{{ route('admin.users.index') }}?search=${searchValue}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.text())
            .then(html => {
                // Ambil hanya bagian tabel dari response HTML
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newTable = doc.getElementById('user-table-container').innerHTML;
                
                // Ganti isi tabel lama dengan yang baru
                tableContainer.innerHTML = newTable;
            });
        }, 500);
    });
</script>
</x-admin-layout>