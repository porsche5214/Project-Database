<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Address;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

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

    /**
     * Handle member registration.
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:50', 'unique:member,username'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:member,email'],
            'password' => ['required', 'string', 'min:8'],
            'first_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $member = Member::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password_hash' => Hash::make($validated['password']),
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'role' => 'member',
        ]);

        if (!empty($validated['address'])) {
            $fullName = trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));
            Address::create([
                'member_id' => $member->member_id,
                'recipient_name' => $fullName ?: $member->username,
                'phone' => $validated['phone'] ?? '',
                'address_line' => $validated['address'],
                'subdistrict' => '',
                'district' => '',
                'province' => '',
                'postal_code' => '',
                'is_default' => true,
            ]);
        }

        Auth::login($member);
        $request->session()->regenerate();
        $member->load('addresses');

        return response()->json([
            'message' => 'Registration successful',
            'user' => new UserResource($member),
        ], 201);
    }

    /**
     * Handle member login with username or email.
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required_without_all:username,email', 'string'],
            'username' => ['nullable', 'string'],
            'email' => ['nullable', 'string'],
            'password' => ['required', 'string'],
        ]);

        $input = $request->input('login') ?? $request->input('username') ?? $request->input('email');
        $field = filter_var($input, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (!Auth::attempt([$field => $input, 'password' => $request->password], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials do not match our records.'],
            ]);
        }

        $request->session()->regenerate();
        $user = Auth::user();
        $user->load('addresses');

        return response()->json([
            'message' => 'Login successful',
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Handle member logout.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }
}
