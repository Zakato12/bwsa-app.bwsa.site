<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session as Session;
use Laravel\Prompts\Table;

class AuthController extends Controller
{
    public function login( Request $request){
        $usr_name = $request->input('username');
        $usr_pass = $request->input('password');

        $users = DB::table('users')
        ->where('username', $usr_name)
        ->where('status', '=', 'active')
        ->first();

        if($users && Hash::check($usr_pass, $users->password)){

            $role = DB::table('roles')->where('id', $users->role_id)->value('name');
            session()->put('usr_id', $users->id);
            session()->put('usr_name', $users->username);
            session()->put('usr_role', $role);
            session()->put('last_activity', time());

            return redirect()->action([PageController::class, 'main']);
        } else {
            return redirect()->action([PageController::class, 'showLogin'])->with('error','Invalid Login Credentials.');
        }
    }
    

    public function logout(Request $request){
        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerate();

        return redirect()->action([PageController::class,'showLogin'])->with('success', 'Successfuly signed out.');
    }

//     public function logout() {
//     session()->flush(); // Clear all session data
//     return redirect('/')->with('success', 'Logged out successfully.');
// }

    public function changePass(Request $request)
    {
        
        
        $validated = $request->validate([
            'currpassword' => 'required|string',
            'newpassword' => 'required|string|min:8',
            'confirmpassword' => 'required|string',
        ]);

        $user = DB::table('users')->where('id', session('usr_id'))->first();

        if (!$user || !Hash::check($validated['currpassword'], $user->password)) {
            return back()->with('error', 'Current password is incorrect');
        }

        if ($validated['newpassword'] != $validated['confirmpassword']) {
            return back()->with('error', 'Passwords do not match');
        }

        DB::table('users')
            ->where('id', session('usr_id'))
            ->update([
                'password'=> Hash::make($validated['confirmpassword']),
                'updated_at' => now()
            ]);
        return back()->with('success', 'Password updated successfully');
    }

    public function addUser(Request $request)
    {
        $currentRole = session('usr_role');
        $allowedRoles = [];

        if ($currentRole == 'admin') {
            $allowedRoles = [1, 2, 3]; // admin, official, treasurer
        } elseif ($currentRole == 'official') {
            $allowedRoles = [4]; // resident
        } else {
            return redirect()->back()->with('error', 'Unauthorized to add users');
        }

        $rules = [
            'username' => 'required|string|max:50|unique:users,username',
            'password' => 'required|string|min:8',
            'full_name' => 'required|string|max:100',
            'role_id' => 'required|in:' . implode(',', $allowedRoles),
            'status' => 'required|in:active,inactive',
        ];

        if (in_array($request->role_id, [2, 3])) { // official or treasurer
            $rules['barangay_id'] = 'required|exists:barangays,id';
        }

        $validated = $request->validate($rules);

        $data = [
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'full_name' => $validated['full_name'],
            'role_id' => $validated['role_id'],
            'status' => $validated['status'],
            'created_at' => now(),
        ];

        if (isset($validated['barangay_id'])) {
            $data['barangay_id'] = $validated['barangay_id'];
        }

        $userId = DB::table('users')->insertGetId($data);

        if ($validated['role_id'] == 4) { // resident
            DB::table('residents')->insert([
                'user_id' => $userId,
                'barangay_id' => $validated['barangay_id'] ?? null, // assume barangay for resident
                'created_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'User added successfully');
    }
}


