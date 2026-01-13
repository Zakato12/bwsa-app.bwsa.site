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
    public function main(){
        if(!session()->has('usr_id')){
            return redirect('/login')->with('error','Please login first.');
        }
        return view('pages.dashboards.admin');
    }

    // Dashboard pages
    public function admindashboard(){
        return view('pages.dashboards.admin');
    }
    public function offdashboard(){
        return view('pages.dashboards.official');
    }
    public function tresdashboard(){
        return view('pages.dashboards.treasurer');
    }
    public function memdashboard(){
        return view('pages.dashboards.member');
    }
}
