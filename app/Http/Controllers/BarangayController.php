<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BarangayController extends Controller
{
    public function index()
    {
        if ($redirect = $this->requireRole(['admin'])) {
            return $redirect;
        }

        $search = trim((string) request('q', ''));

        $query = DB::table('barangays')
            ->select('barangays.*')
            ->selectSub(function ($query) {
                $query->from('residents')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('residents.barangay_id', 'barangays.id');
            }, 'resident_count');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('barangays.name', 'like', '%' . $search . '%')
                    ->orWhere('barangays.brgy_code', 'like', '%' . $search . '%')
                    ->orWhere('barangays.address', 'like', '%' . $search . '%')
                    ->orWhereRaw('CAST(barangays.payment_amount_per_bill AS CHAR) LIKE ?', ['%' . $search . '%']);
            });
        }

        $barangays = $query
            ->orderBy('barangays.created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return view('pages.barangays.index', compact('barangays', 'search'));
    }

    public function create()
    {
        if ($redirect = $this->requireRole(['admin'])) {
            return $redirect;
        }

        return view('pages.barangays.create');
    }

    public function store(Request $request)
    {
        if ($redirect = $this->requireRole(['admin'])) {
            return $redirect;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:barangays,name',
            'address' => 'required|string|max:255',
            'status' => 'required|in:1,0',
            'payment_amount_per_bill' => 'required|numeric|min:0',
        ]);

        DB::table('barangays')->insert([
            'name' => $validated['name'],
            'brgy_code' => $this->generateBarangayCode($validated['name']),
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
        if ($redirect = $this->requireRole(['admin'])) {
            return $redirect;
        }

        $barangay = DB::table('barangays')->where('id', $id)->first();
        if (!$barangay) {
            return redirect()->route('barangays.index')->with('error', 'Barangay not found');
        }

        return view('pages.barangays.edit', compact('barangay'));
    }

    public function update(Request $request, $id)
    {
        if ($redirect = $this->requireRole(['admin'])) {
            return $redirect;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:barangays,name,' . $id,
            'address' => 'required|string|max:255',
            'status' => 'required|in:1,0',
            'payment_amount_per_bill' => 'required|numeric|min:0',
        ]);

        $barangay = DB::table('barangays')->where('id', $id)->first();
        if (!$barangay) {
            return redirect()->route('barangays.index')->with('error', 'Barangay not found');
        }

        DB::table('barangays')->where('id', $id)->update([
            'name' => $validated['name'],
            'brgy_code' => $barangay->brgy_code ?: $this->generateBarangayCode($validated['name'], (int) $id),
            'address' => $validated['address'],
            'status' => $validated['status'],
            'payment_amount_per_bill' => $validated['payment_amount_per_bill'],
            'updated_at' => now(),
        ]);

        return redirect()->route('barangays.index')->with('success', 'Barangay updated successfully');
    }

    public function destroy($id)
    {
        if ($redirect = $this->requireRole(['admin'])) {
            return $redirect;
        }

        DB::table('barangays')->where('id', $id)->update([
            'status' => 'inactive',
            'updated_at' => now(),
        ]);
        return redirect()->route('barangays.index')->with('success', 'Barangay deleted successfully');
    }

    private function generateBarangayCode(string $name, ?int $excludeId = null): string
    {
        $normalized = strtoupper((string) preg_replace('/[^A-Za-z]/', '', $name));
        $consonants = preg_replace('/[AEIOU]/', '', $normalized);
        $base = Str::substr((string) $consonants, 0, 4);

        if (Str::length((string) $base) < 3) {
            $base = Str::substr($normalized, 0, 4);
        }

        if (Str::length((string) $base) < 3) {
            $base = str_pad((string) $base, 3, 'X');
        }

        $base = Str::upper((string) $base);
        $candidate = $base;
        $suffix = 2;

        while (true) {
            $exists = DB::table('barangays')
                ->when($excludeId !== null, fn ($q) => $q->where('id', '!=', $excludeId))
                ->where('brgy_code', $candidate)
                ->exists();

            if (!$exists) {
                return $candidate;
            }

            $candidate = Str::substr($base, 0, max(1, 8 - strlen((string) $suffix))) . $suffix;
            $suffix++;
        }
    }
}
