<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function show()
    {
        $member = Auth::user();
        $addresses = $member->addresses;
        return view('profile.show', compact('member', 'addresses'));
    }
}
