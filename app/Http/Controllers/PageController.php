<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class PageController extends Controller
{
    public function showLogin(){
        return view('pages.auth.login');
    }
    public function userincactive(){
        return view('pages.auth.login')->with('error','Your account is inactive. Please login again.');
    }
    public function main(){
        if(!session()->has('usr_id')){
            return redirect('/login')->with('error','Please login first.');
        }

        if(session()->has('usr_role')){
            switch (session('usr_role')) {
                case '1':
                    return view('pages.dashboards.admin');
                case '2':
                    return view('pages.dashboards.official');
                case '3':
                    return view('pages.dashboards.tresurer');
                case '4':
                    return view('pages.dashboards.member');
                default:
                    return redirect()->action([PageController::class, 'showLogin'])->with('error','Invalid Login Credentials.');
            }
        }
    }

    public function showAddUser(){
        return view('pages.users.create');
    }
}
