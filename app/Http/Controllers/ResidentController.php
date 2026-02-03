<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ResidentController extends Controller
{
    public function index()
    {
        $residents = DB::table('residents')
            ->join('users', 'residents.user_id', '=', 'users.id')
            ->join('barangays', 'residents.barangay_id', '=', 'barangays.id')
            ->select('residents.id', 'users.full_name', 'users.username', 'barangays.name as barangay', 'residents.created_at')
            ->get();

        return view('pages.residents.index', compact('residents'));
    }

    public function create()
    {
        if (!in_array(session('usr_role'), ['admin', 'official'])) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized');
        }

        $barangays = DB::table('barangays')->where('status', 1)->get();
        return view('pages.residents.create', compact('barangays'));
    }

    public function store(Request $request)
    {
        if (!in_array(session('usr_role'), ['admin', 'official'])) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized');
        }

        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:8',
            'full_name' => 'required|string|max:100',
            'barangay_id' => 'required|exists:barangays,id',
        ]);

        $userId = DB::table('users')->insertGetId([
            'username' => $validated['username'],
            'password' => bcrypt($validated['password']),
            'full_name' => $validated['full_name'],
            'role_id' => 4, // resident
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('residents')->insert([
            'user_id' => $userId,
            'barangay_id' => $validated['barangay_id'],
            'created_at' => now(),
        ]);

        return redirect()->route('residents.index')->with('success', 'Resident added successfully');
    }

    public function edit($id)
    {
        if (!in_array(session('usr_role'), ['admin', 'official'])) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized');
        }

        $resident = DB::table('residents')
            ->join('users', 'residents.user_id', '=', 'users.id')
            ->where('residents.id', $id)
            ->select('residents.*', 'users.username', 'users.full_name')
            ->first();

        if (!$resident) {
            return redirect()->route('residents.index')->with('error', 'Resident not found');
        }

        $barangays = DB::table('barangays')->where('status', 1)->get();
        return view('pages.residents.edit', compact('resident', 'barangays'));
    }

    public function update(Request $request, $id)
    {
        if (!in_array(session('usr_role'), ['admin', 'official'])) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized');
        }

        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:users,username,' . DB::table('residents')->where('id', $id)->value('user_id') . ',id',
            'full_name' => 'required|string|max:100',
            'barangay_id' => 'required|exists:barangays,id',
        ]);

        $resident = DB::table('residents')->where('id', $id)->first();
        if (!$resident) {
            return redirect()->route('residents.index')->with('error', 'Resident not found');
        }

        DB::table('users')->where('id', $resident->user_id)->update([
            'username' => $validated['username'],
            'full_name' => $validated['full_name'],
            'updated_at' => now(),
        ]);

        DB::table('residents')->where('id', $id)->update([
            'barangay_id' => $validated['barangay_id'],
        ]);

        return redirect()->route('residents.index')->with('success', 'Resident updated successfully');
    }

    public function destroy($id)
    {
        if (!in_array(session('usr_role'), ['admin', 'official'])) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized');
        }

        $resident = DB::table('residents')->where('id', $id)->first();
        if (!$resident) {
            return redirect()->route('residents.index')->with('error', 'Resident not found');
        }

        DB::table('residents')->where('id', $id)->delete();
        DB::table('users')->where('id', $resident->user_id)->delete();

        return redirect()->route('residents.index')->with('success', 'Resident deleted successfully');
    }
}