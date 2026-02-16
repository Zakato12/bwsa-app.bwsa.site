<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function listUsers()
    {
        if ($redirect = $this->requireRole(['admin'])) {
            return $redirect;
        }

        $search = trim((string) request('q', ''));

        $query = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->leftJoin('barangays', 'users.barangay_id', '=', 'barangays.id')
            ->select('users.id', 'users.username', 'users.full_name', 'roles.name as role', 'barangays.name as barangay_name', 'users.status', 'users.created_at', 'users.barangay_id', 'users.role_id')
            ->where('users.id', '!=', '4')
            ->where('users.id', '!=', '1');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('users.username', 'like', '%' . $search . '%')
                    ->orWhere('users.full_name', 'like', '%' . $search . '%')
                    ->orWhere('roles.name', 'like', '%' . $search . '%')
                    ->orWhere('users.status', 'like', '%' . $search . '%')
                    ->orWhere('barangays.name', 'like', '%' . $search . '%');
            });
        }

        $users = $query
            ->orderBy('users.created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $barangays = DB::table('barangays')->select('id', 'name')->orderBy('name')->get();

        return view('pages.users.list', compact('users', 'barangays', 'search'));
    }

    public function updateUser(Request $request, $id)
    {
        if ($redirect = $this->requireRole(['admin'])) {
            return $redirect;
        }

        $validated = $request->validate([
            'username' => 'required|string|max:50|unique:users,username,' . $id,
            'full_name' => 'required|string|max:100',
            'role_id' => 'required|exists:roles,id',
            'status' => 'required|in:active,inactive',
            'barangay_id' => 'nullable|exists:barangays,id',
        ]);

        $update = [
            'username' => $validated['username'],
            'full_name' => $validated['full_name'],
            'role_id' => $validated['role_id'],
            'status' => $validated['status'],
            'updated_at' => now(),
        ];

        if (in_array((int) $validated['role_id'], [2, 3, 4], true)) {
            $update['barangay_id'] = $validated['barangay_id'] ?? null;
        } else {
            $update['barangay_id'] = null;
        }

        DB::table('users')->where('id', $id)->update($update);

        $this->appendAuditLedger('users.updated', [
            'user_id' => (int) $id,
            'role_id' => (int) $validated['role_id'],
            'status' => $validated['status'],
        ]);

        return redirect()->route('users.list')->with('success', 'User updated successfully.');
    }

    public function deleteUser($id)
    {
        if ($redirect = $this->requireRole(['admin'])) {
            return $redirect;
        }

        DB::table('users')->where('id', $id)->update(['status' => 'inactive']);

        $this->appendAuditLedger('users.deleted', [
            'user_id' => (int) $id,
        ]);

        return redirect()->route('users.list')->with('success', 'User deleted successfully.');
    }
}
