<x-admin-layout title="Motorcycles">

    <div class="flex justify-between mb-6">
        <h1 class="text-2xl font-bold">Motorcycles</h1>

        <a href="{{ route('admin.motorcycles_Management.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded">
            + Add Motorcycle
        </a>
    </div>

    @if (session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <table class="w-full">
            <thead>
                <tr class="border-b text-left">
                    <th class="p-3">Image</th>
                    <th class="p-3">Name</th>
                    <th class="p-3">Brand</th>
                    <th class="p-3">Type</th>
                    <th class="p-3">Plate</th>
                    <th class="p-3">Transmission</th>
                    <th class="p-3">Price</th>
                    <th class="p-3">Status</th>
                    <th class="p-3 text-center">Action</th>
                </tr>
            </thead>


            <tbody>
                @foreach ($motorcycles as $motor)
                    <tr class="border-b">
                        <td class="p-3">
                            @if ($motor->image_path && file_exists(public_path('storage/motorcycles/' . $motor->image_path)))
                                <img src="{{ asset('storage/motorcycles/' . $motor->image_path) }}"
                                     class="h-16 w-20 object-cover rounded shadow-sm" alt="{{ $motor->category }}">
                            @elseif ($motor->image_path && file_exists(public_path('storage/' . $motor->image_path)))
                                <img src="{{ asset('storage/' . $motor->image_path) }}"
                                     class="h-16 w-20 object-cover rounded shadow-sm" alt="{{ $motor->category }}">
                            @else
                                <div class="h-16 w-20 bg-gray-100 rounded flex items-center justify-center">
                                    <span class="text-gray-400 italic text-sm">No Image</span>
                                </div>
                            @endif
                        </td>
                        <td class="p-3">{{ $motor->category }}</td>
                        <td class="p-3">{{ $motor->brand }}</td>
                        <td class="p-3">
                            @php
                                $typeKey = strtolower(str_replace([' ', '-'], '_', $motor->type ?? ''));
                            @endphp
                            @switch($typeKey)
                                @case('small_matic')
                                @case('smallmatic')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                                        Small Automatic
                                    </span>
                                    @break

                                @case('big_matic')
                                @case('bigmatic')
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-700">
                                        Big Automatic
                                    </span>
                                    @break

                                @default
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
                                        {{ $motor->type ? ucfirst(strtolower($motor->type)) : 'Manual' }}
                                    </span>
                            @endswitch
                        </td>

                        <td class="p-3">{{ $motor->license_plate }}</td>
                        <td class="p-3">
                            @php $trans = strtolower($motor->transmission ?? ''); @endphp
                            @if ($trans === 'automatic' || $trans === 'automatic')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">
                                    Automatic
                                </span>
                            @else
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
                                    Manual
                                </span>
                            @endif
                        </td>
                        <td class="p-3">Rp {{ number_format($motor->price) }}</td>
                        <td class="p-3">
                            @if(strtolower($motor->status ?? '') === 'rented')
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700" title="Locked: active rental">
                                    Rented
                                </span>
                            @else
                                <form action="{{ route('admin.motorcycles_Management.toggleStatus', $motor->id) }}" method="POST" class="inline">
                                    @csrf
                                    <select name="status" onchange="this.form.submit()" 
                                            class="text-xs font-semibold rounded-full px-3 py-1 border cursor-pointer focus:outline-none transition-colors duration-150
                                            {{ strtolower($motor->status ?? '') === 'available' ? 'bg-green-100 border-green-200 text-green-700' : 'bg-yellow-100 border-yellow-200 text-yellow-700' }}">
                                        <option value="available" {{ strtolower($motor->status ?? '') === 'available' ? 'selected' : '' }}>
                                            Available
                                        </option>
                                        <option value="service" {{ strtolower($motor->status ?? '') === 'service' ? 'selected' : '' }}>
                                            Service
                                        </option>
                                    </select>
                                </form>
                            @endif
                        </td>

                        <td class="p-3 text-center">
                            <div class="flex justify-center items-center gap-2">
                                <a href="{{ route('admin.motorcycles_Management.edit', $motor->id) }}"
                                    class="px-4 py-1.5 bg-yellow-500 text-white rounded text-sm hover:bg-yellow-600 transition shadow-sm font-medium">
                                    Edit
                                </a>

                                <form action="{{ route('admin.motorcycles_Management.destroy', $motor->id) }}" method="POST"
                                    onsubmit="return confirm('Delete this motorcycle?');" class="inline">

                                    @csrf
                                    @method('DELETE')

                                    <button class="px-4 py-1.5 bg-red-600 text-white rounded text-sm hover:bg-red-700 transition shadow-sm font-medium">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>

                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4">
            {{ $motorcycles->links('pagination::tailwind') }}
        </div>
    </div>
</x-admin-layout>
