<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * Serve the React SPA shell.
     */
    public function index()
    {
        $indexPath = public_path('shop/index.html');

        if (file_exists($indexPath)) {
            return response(file_get_contents($indexPath))
                ->header('Content-Type', 'text/html');
        }

        return view('shop');
    }
}
