<x-admin-layout title="Returns Management">

<h1 class="text-2xl font-bold mb-4">Returns Management</h1>

@if(session('success'))
<div class="bg-green-100 text-green-700 p-2 rounded mb-3">
    {{ session('success') }}
</div>
@endif

@if ($errors->any())
<div class="bg-red-100 text-red-700 p-2 rounded mb-3">
    <ul class="list-disc list-inside text-sm">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="bg-white shadow rounded overflow-x-auto">
<table class="w-full">
<thead class="bg-gray-100">
<tr>
    <th class="p-3">User</th>
    <th class="p-3">Motor</th>
    <th class="p-3">Return Date</th>
    <th class="p-3">Condition</th>
    <th class="p-3">Damage Fee</th>
    <th class="p-3">Notes</th>
    <th class="p-3 text-center">Action</th>
</tr>
</thead>
<tbody>

@forelse($rentals as $r)
<tr class="border-t text-center">
    <td class="p-2">{{ $r->user_name }}</td>
    <td class="p-2">
        {{ $r->brand }} {{ $r->type }}<br>
        ({{ $r->license_plate }})
    </td>
    <td class="p-2">{{ $r->return_date }}</td>

    <td class="p-2">
        <form id="return-form-{{ $r->id }}" method="POST" action="{{ route('admin.returns.store') }}">
            @csrf
            <input type="hidden" name="rental_id" value="{{ $r->id }}">
        </form>

        <select name="condition" form="return-form-{{ $r->id }}" class="border rounded p-1" required>
            <option value="good">Good</option>
            <option value="minor_damage">Minor Damage</option>
            <option value="major_damage">Major Damage</option>
        </select>
    </td>

    <td class="p-2">
        <input type="number" name="damage_fee" form="return-form-{{ $r->id }}" placeholder="0" class="border rounded p-1 w-24">
    </td>

    <td class="p-2">
        <input type="text" name="notes" form="return-form-{{ $r->id }}" class="border rounded p-1">
    </td>

    <td class="p-2 text-center">
        <div class="flex justify-center items-center">
            <button type="submit" form="return-form-{{ $r->id }}" class="px-4 py-1.5 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 transition shadow-sm font-medium">
                Submit
            </button>
        </div>
    </td>
</tr>
@empty
<tr>
    <td colspan="7" class="text-center p-4 text-gray-500">
        No return data.
    </td>
</tr>
@endforelse

</tbody>
</table>
</div>

</x-admin-layout>
