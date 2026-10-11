<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Support\ActivityLogger;

class AdminAccountController extends Controller
{
    public function index()
    {
        return view('admin.admins.index', [
            'admins' => Admin::latest()->paginate(10)
        ]);
    }

    public function create()
    {
        return view('admin.admins.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'username' => 'required|unique:admins',
            'email' => 'required|email|unique:admins',
            'password' => 'required|min:6'
        ]);

        $admin = Admin::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        ActivityLogger::log('CREATE', 'admin', 'Admin menambahkan akun admin', [
            'entity_id' => $admin->getKey(),
            'name' => $admin->name,
            'username' => $admin->username,
            'email' => $admin->email,
        ]);

        return redirect()->route('admin.admins.index')
            ->with('success', 'Admin created successfully.');
    }

    public function edit(Admin $admin)
    {
        return view('admin.admins.edit', compact('admin'));
    }

    public function update(Request $request, Admin $admin)
    {
        $request->validate([
            'name' => 'required',
            'username' => 'required|unique:admins,username,' . $admin->id,
            'email' => 'required|email|unique:admins,email,' . $admin->id,
            'password' => 'nullable|min:6',
        ]);

        $data = $request->only(['name', 'username', 'email']);

        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        $admin->update($data);

        return redirect()->route('admin.admins.index')
            ->with('success', 'Admin updated successfully.');
    }

    public function destroy(Admin $admin)
    {
        $admin->delete();

        return back()->with('success', 'Admin deleted.');
    }
}
