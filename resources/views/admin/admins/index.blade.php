@extends('admin.layouts.app')

@section('title', 'Admin Management')

@section('content')
<div class="p-6">
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-2xl font-bold">Admin Accounts</h1>
        <a href="{{ route('admin.admins.create') }}" 
           class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
           + Add New Admin
        </a>
    </div>

    @if(session('success'))
        <div class="p-3 bg-green-100 text-green-800 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow">
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-100 border-b">
                    <th class="p-3 text-left">Name</th>
                    <th class="p-3 text-left">Username</th>
                    <th class="p-3 text-left">Email</th>
                    <th class="p-3 text-center w-32">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($admins as $admin)
                <tr class="border-b">
                    <td class="p-3">{{ $admin->name }}</td>
                    <td class="p-3">{{ $admin->username }}</td>
                    <td class="p-3">{{ $admin->email }}</td>
                    <td class="p-3">
                        <div class="flex justify-center items-center gap-2">
                            <a href="{{ route('admin.admins.edit', $admin->id) }}" 
                               class="px-4 py-1.5 bg-yellow-500 text-white rounded text-sm hover:bg-yellow-600 transition shadow-sm font-medium">Edit</a>

                            <form action="{{ route('admin.admins.destroy', $admin->id) }}" 
                                  method="POST"
                                  onsubmit="return confirm('Delete this admin?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button class="px-4 py-1.5 bg-red-600 text-white rounded text-sm hover:bg-red-700 transition shadow-sm font-medium">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="p-4">
            {{ $admins->links('pagination::tailwind') }}
        </div>
    </div>
</div>
@endsection
