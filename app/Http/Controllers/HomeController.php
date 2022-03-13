<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::where('id', '!=', Auth::user()->id)->get();
        $group = DB::select('SELECT
    tb_group.id_group, 
    tb_group.name_group
FROM
    tb_group
WHERE
    tb_group.id_user ='.Auth::user()->id);


        return view('home', compact('users'))
        ->with('group',$group);
    }
}
