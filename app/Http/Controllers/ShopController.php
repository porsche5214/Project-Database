<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * Serve the React SPA shell.
     *
     * The build lives in public/spa, not public/shop: a real public/shop folder makes
     * PHP's built-in server (Sail) handle /shop itself instead of passing it to Laravel.
     */
    public function index()
    {
        $indexPath = public_path('spa/index.html');

        if (file_exists($indexPath)) {
            return response(file_get_contents($indexPath))
                ->header('Content-Type', 'text/html');
        }

        return view('shop');
    }
}
