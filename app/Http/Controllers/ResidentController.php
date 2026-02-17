<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ResidentController extends Controller
{
    public function index()
    {
        if ($redirect = $this->requireRole(['official'])) {
            return $redirect;
        }

        $search = trim((string) request('q', ''));

        $query = DB::table('residents')
            ->join('users', 'residents.user_id', '=', 'users.id')
            ->join('barangays', 'residents.barangay_id', '=', 'barangays.id')
            ->select('residents.id', 'users.full_name', 'users.username', 'barangays.name as barangay', 'residents.created_at')
            ->where('users.status', 'active')
            ->orderBy('residents.created_at', 'desc');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('users.full_name', 'like', '%' . $search . '%')
                    ->orWhere('users.username', 'like', '%' . $search . '%')
                    ->orWhere('barangays.name', 'like', '%' . $search . '%')
                    ->orWhereRaw('CAST(residents.id AS CHAR) LIKE ?', ['%' . $search . '%']);
            });
        }

        if (session('usr_role') === 'official') {
            $barangayId = $this->currentUserBarangayId();
            if (!$barangayId) {
                return redirect()->route('dashboard')->with('error', 'Barangay assignment required.');
            }
            $query->where('residents.barangay_id', $barangayId);
        }

        $residents = $query->paginate(10)->withQueryString();

        return view('pages.residents.index', compact('residents', 'search'));
    }

    public function create()
    {
        if ($redirect = $this->requireRole(['official'])) {
            return $redirect;
        }

        $barangayId = $this->currentUserBarangayId();
        if (!$barangayId) {
            return redirect()->route('dashboard')->with('error', 'Barangay assignment required.');
        }
        $barangay = DB::table('barangays')->where('id', $barangayId)->first();
        return view('pages.residents.create', ['barangays' => collect([$barangay])]);
    }

    public function store(Request $request)
    {
        if ($redirect = $this->requireRole(['official'])) {
            return $redirect;
        }

        $validated = $request->validate([
            'full_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'full_name')->where(fn ($q) => $q->where('role_id', 4)),
            ],
            'address' => ['required', Rule::in([
                'Purok 1',
                'Purok 2',
                'Purok 3',
                'Purok 4',
                'Purok 5',
                'Purok 6',
                'Purok 7',
                'Purok 8',
                'Purok 9',
                'Purok 10',
            ])],
            'contact_number' => 'nullable|string|max:20|regex:/^[0-9+\\-\\s()]{7,20}$/',
        ], [
            'full_name.unique' => 'This resident full name already exists.',
            'address.in' => 'Please select a valid Purok.',
            'contact_number.regex' => 'Contact number format is invalid.',
        ]);

        $barangayId = $this->currentUserBarangayId();
        if (!$barangayId) {
            return redirect()->route('dashboard')->with('error', 'Barangay assignment required.');
        }

        [$username, $password] = $this->generateCredentials(4, $barangayId);

        $userId = DB::table('users')->insertGetId([
            'username' => $username,
            'password' => bcrypt($password),
            'full_name' => $validated['full_name'],
            'role_id' => 4, // resident
            'status' => 'active',
            'barangay_id' => $barangayId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('residents')->insert([
            'user_id' => $userId,
            'barangay_id' => $barangayId,
            'address' => $validated['address'],
            'contact_number' => $validated['contact_number'] ?? null,
            'created_at' => now(),
        ]);

        return redirect()->route('residents.index')->with('success', "Resident added. Username/Password: {$username}");
    }

    public function edit($id)
    {
        if ($redirect = $this->requireRole(['official'])) {
            return $redirect;
        }

        $resident = DB::table('residents')
            ->join('users', 'residents.user_id', '=', 'users.id')
            ->where('residents.id', $id)
            ->select('residents.*', 'users.username', 'users.full_name')
            ->first();

        if (!$resident) {
            return redirect()->route('residents.index')->with('error', 'Resident not found');
        }

        if ($redirect = $this->requireRoleInBarangay(['official'], (int) $resident->user_id)) {
            return $redirect;
        }

        $barangayId = $this->currentUserBarangayId();
        if (!$barangayId) {
            return redirect()->route('dashboard')->with('error', 'Barangay assignment required.');
        }
        $barangay = DB::table('barangays')->where('id', $barangayId)->first();
        return view('pages.residents.edit', [
            'resident' => $resident,
            'barangays' => collect([$barangay]),
        ]);
    }

    public function update(Request $request, $id)
    {
        if ($redirect = $this->requireRole(['official'])) {
            return $redirect;
        }

        $residentUserId = DB::table('residents')->where('id', $id)->value('user_id');

        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:users,username,' . $residentUserId . ',id',
            'full_name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('users', 'full_name')
                    ->ignore($residentUserId, 'id')
                    ->where(fn ($q) => $q->where('role_id', 4)),
            ],
            'address' => ['required', Rule::in([
                'Purok 1',
                'Purok 2',
                'Purok 3',
                'Purok 4',
                'Purok 5',
                'Purok 6',
                'Purok 7',
                'Purok 8',
                'Purok 9',
                'Purok 10',
            ])],
            'contact_number' => 'nullable|string|max:20|regex:/^[0-9+\\-\\s()]{7,20}$/',
            'barangay_id' => 'required|exists:barangays,id',
        ], [
            'full_name.unique' => 'This resident full name already exists.',
            'address.in' => 'Please select a valid Purok.',
            'contact_number.regex' => 'Contact number format is invalid.',
        ]);

        $resident = DB::table('residents')->where('id', $id)->first();
        if (!$resident) {
            return redirect()->route('residents.index')->with('error', 'Resident not found');
        }

        if ($redirect = $this->requireRoleInBarangay(['official'], (int) $resident->user_id, (int) $validated['barangay_id'])) {
            return $redirect;
        }

        DB::table('users')->where('id', $resident->user_id)->update([
            'username' => $validated['username'],
            'full_name' => $validated['full_name'],
            'updated_at' => now(),
        ]);

        DB::table('residents')->where('id', $id)->update([
            'barangay_id' => $validated['barangay_id'],
            'address' => $validated['address'],
            'contact_number' => $validated['contact_number'] ?? null,
        ]);

        return redirect()->route('residents.index')->with('success', 'Resident updated successfully');
    }

    public function destroy($id)
    {
        if ($redirect = $this->requireRole(['official'])) {
            return $redirect;
        }

        $resident = DB::table('residents')->where('id', $id)->first();
        if (!$resident) {
            return redirect()->route('residents.index')->with('error', 'Resident not found');
        }

        if ($redirect = $this->requireRoleInBarangay(['official'], (int) $resident->user_id)) {
            return $redirect;
        }
        DB::table('users')->where('id', $resident->user_id)->update([
            'status' => 'inactive',
            'updated_at' => now(),
        ]);

        return redirect()->route('residents.index')->with('success', 'Resident deleted successfully');
    }
}
