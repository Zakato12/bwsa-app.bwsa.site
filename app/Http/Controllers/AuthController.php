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
        // ->where('active', '=', '1')
        ->first();

        if($users && Hash::check($usr_pass, $users->password)){

            session()->put('usr_id', $users->id);
            session()->put('usr_name', $users->username);
            session()->put('usr_role', $users->role);
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

        // Plain-text comparison
        if ($validated['currpassword'] != session('usr_pass')) {
            return back()->with('error', 'Current password is incorrect');
        }

        if ($validated['newpassword'] != $validated['confirmpassword']) {
            return back()->with('error', 'Passwords do not match');
        }

        DB::table('users')
            ->where('id', session('usr_id'))
            ->update([
                'password'=> Hash::make($validated['confirmpassword'])
                ]);
            session()->put('usr_pass', $validated['confirmpassword']);
        return back()->with('success', 'Password updated successfully');
    }
}


