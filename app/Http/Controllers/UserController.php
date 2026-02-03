<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function listUsers()
    {
        if (!session()->has('usr_id') || session('usr_role') != 'admin') {
            return redirect()->route('login')->with('error', 'Unauthorized access');
        }

        $users = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->select('users.id', 'users.username', 'users.full_name', 'roles.name as role', 'users.status', 'users.created_at')
            ->get();

        return view('pages.users.list', compact('users'));
    }
}
