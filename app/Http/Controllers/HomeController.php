<?php

namespace App\Http\Controllers;

use App\Models\UsefulLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /** Show the application dashboard. */
    public function index(Request $request)
    {
        $usage = 0;

        foreach (Auth::user()->servers as $server){
            $usage += $server->product->price;
        }

        return view('home')->with([
            'useage' => $usage,
            'useful_links' => UsefulLink::all()->sortBy('id')
        ]);
        return view('layouts.index')->with([
            'user' => Auth::user(),
            'credits_reward_after_verify_discord' => Configuration::getValueByKey('CREDITS_REWARD_AFTER_VERIFY_DISCORD'),
            'force_email_verification' => Configuration::getValueByKey('FORCE_EMAIL_VERIFICATION'),
            'force_discord_verification' => Configuration::getValueByKey('FORCE_DISCORD_VERIFICATION'),
        ]);
    }
}
