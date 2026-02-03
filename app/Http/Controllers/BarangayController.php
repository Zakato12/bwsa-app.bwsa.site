<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BarangayController extends Controller
{
    public function index()
    {
        if (session('usr_role') != 'admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized');
        }

        $barangays = DB::table('barangays')->get();
        return view('pages.barangays.index', compact('barangays'));
    }

    public function create()
    {
        if (session('usr_role') != 'admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized');
        }

        return view('pages.barangays.create');
    }

    public function store(Request $request)
    {
        if (session('usr_role') != 'admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:barangays,name',
            'address' => 'required|string|max:255',
            'status' => 'required|in:1,0',
            'payment_amount_per_bill' => 'required|numeric|min:0',
        ]);

        DB::table('barangays')->insert([
            'name' => $validated['name'],
            'address' => $validated['address'],
            'status' => $validated['status'],
            'payment_amount_per_bill' => $validated['payment_amount_per_bill'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('barangays.index')->with('success', 'Barangay added successfully');
    }

    public function edit($id)
    {
        if (session('usr_role') != 'admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized');
        }

        $barangay = DB::table('barangays')->where('id', $id)->first();
        if (!$barangay) {
            return redirect()->route('barangays.index')->with('error', 'Barangay not found');
        }

        return view('pages.barangays.edit', compact('barangay'));
    }

    public function update(Request $request, $id)
    {
        if (session('usr_role') != 'admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:barangays,name,' . $id,
            'address' => 'required|string|max:255',
            'status' => 'required|in:1,0',
            'payment_amount_per_bill' => 'required|numeric|min:0',
        ]);

        DB::table('barangays')->where('id', $id)->update([
            'name' => $validated['name'],
            'address' => $validated['address'],
            'status' => $validated['status'],
            'payment_amount_per_bill' => $validated['payment_amount_per_bill'],
            'updated_at' => now(),
        ]);

        return redirect()->route('barangays.index')->with('success', 'Barangay updated successfully');
    }

    public function destroy($id)
    {
        if (session('usr_role') != 'admin') {
            return redirect()->route('dashboard')->with('error', 'Unauthorized');
        }

        DB::table('barangays')->where('id', $id)->delete();
        return redirect()->route('barangays.index')->with('success', 'Barangay deleted successfully');
    }
}
