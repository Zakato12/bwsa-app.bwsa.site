<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session as Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login( Request $request){
        $usr_name = $request->input('username');
        $usr_pass = $request->input('password');

        $throttleKey = 'login:' . Str::lower((string) $usr_name) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->with('error', "Too many login attempts. Try again in {$seconds} seconds.");
        }

        $users = DB::table('users')
        ->where('username', $usr_name)
        ->where('status', '=', 'active')
        ->first();

        if($users && Hash::check($usr_pass, $users->password)){
            RateLimiter::clear($throttleKey);

            $request->session()->regenerate();
            $request->session()->regenerateToken();

            $role = DB::table('roles')->where('id', $users->role_id)->value('name');
            session()->put('usr_id', $users->id);
            session()->put('usr_name', $users->username);
            session()->put('usr_role', $role);
            session()->put('last_activity', time());
            session()->put('auth_ip', $request->ip());
            session()->put('auth_ua_hash', hash('sha256', (string) $request->userAgent()));

            Log::info('auth.login', [
                'user_id' => $users->id,
                'username' => $users->username,
                'ip' => $request->ip(),
            ]);

            return redirect()->action([PageController::class, 'main']);
        } else {
            RateLimiter::hit($throttleKey, 60);
            Log::warning('auth.login_failed', [
                'username' => $usr_name,
                'ip' => $request->ip(),
            ]);
            return redirect()->action([PageController::class, 'showLogin'])->with('error','Invalid Login Credentials.');
        }
    }
    

    public function logout(Request $request){
        Log::info('auth.logout', [
            'user_id' => session('usr_id'),
            'ip' => $request->ip(),
        ]);

        $request->session()->flush();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->action([PageController::class,'showLogin'])->with('success', 'Successfuly signed out.');
    }

//     public function logout() {
//     session()->flush(); // Clear all session data
//     return redirect('/')->with('success', 'Logged out successfully.');
// }

    public function changePass(Request $request)
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

        $validated = $request->validate([
            'currpassword' => 'required|string',
            'newpassword' => 'required|string|min:8',
            'confirmpassword' => 'required|string',
        ]);

        $user = DB::table('users')->where('id', session('usr_id'))->first();

        if (!$user || !Hash::check($validated['currpassword'], $user->password)) {
            Log::warning('auth.password_change_failed', [
                'user_id' => session('usr_id'),
                'reason' => 'current_password_invalid',
            ]);
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

        $request->session()->regenerate();
        Log::info('auth.password_changed', [
            'user_id' => session('usr_id'),
        ]);
        return back()->with('success', 'Password updated successfully');
    }

    public function addUser(Request $request)
    {
        if ($redirect = $this->requireLogin()) {
            return $redirect;
        }

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
            'full_name' => 'required|string|max:100',
            'role_id' => 'required|in:' . implode(',', $allowedRoles),
            'status' => 'required|in:active,inactive',
        ];

        if ((int) $request->role_id === 1) {
            $rules['username'] = 'required|string|max:50|unique:users,username';
            $rules['password'] = 'required|string|min:8';
        }

        if (in_array($request->role_id, [2, 3])) { // official or treasurer
            $rules['barangay_id'] = 'required|exists:barangays,id';
        }

        $validated = $request->validate($rules);

        if ($currentRole === 'official') {
            if ($redirect = $this->requireRoleInBarangay(['official'], null, $validated['barangay_id'] ?? null)) {
                return $redirect;
            }
        }

        $barangayId = $validated['barangay_id'] ?? null;
        if ((int) $validated['role_id'] === 4 && !$barangayId) {
            $barangayId = $this->currentUserBarangayId();
        }

        if ((int) $validated['role_id'] === 1) {
            $username = $validated['username'];
            $password = $validated['password'];
        } else {
            [$username, $password] = $this->generateCredentials((int) $validated['role_id'], $barangayId);
        }

        $data = [
            'username' => $username,
            'password' => Hash::make($password),
            'full_name' => $validated['full_name'],
            'role_id' => $validated['role_id'],
            'status' => $validated['status'],
            'created_at' => now(),
        ];

        if ($barangayId) {
            $data['barangay_id'] = $barangayId;
        }

        $userId = DB::table('users')->insertGetId($data);

        if ($validated['role_id'] == 4) { // resident
            DB::table('residents')->insert([
                'user_id' => $userId,
                'barangay_id' => $barangayId,
                'created_at' => now(),
            ]);
        }

        Log::info('users.created', [
            'actor_id' => session('usr_id'),
            'new_user_id' => $userId,
            'role_id' => $validated['role_id'],
        ]);
        $this->appendAuditLedger('users.created', [
            'new_user_id' => $userId,
            'role_id' => $validated['role_id'],
            'barangay_id' => $validated['barangay_id'] ?? null,
        ]);

        return redirect()->back()->with('success', "User added successfully. Username/Password: {$username}");
    }
}
