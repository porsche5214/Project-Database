<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Get the authenticated user (or null).
     */
    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response('null', 200, ['Content-Type' => 'application/json']);
        }

        $user->load('addresses');

        return response()->json(new UserResource($user));
    }
}
