<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Address;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Update member personal information and default address.
     */
    public function update(Request $request)
    {
        $member = $request->user();

        $validated = $request->validate([
            'first_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:100', 'unique:member,email,' . $member->member_id . ',member_id'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:50'],
        ]);

        $member->update([
            'first_name' => $validated['first_name'] ?? $member->first_name,
            'last_name' => $validated['last_name'] ?? $member->last_name,
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? $member->phone,
        ]);

        if (isset($validated['address'])) {
            $address = $member->addresses()->where('is_default', true)->first()
                ?? $member->addresses()->first();

            $fullName = trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? ''));

            if ($address) {
                $address->update([
                    'recipient_name' => $fullName ?: $member->username,
                    'phone' => $member->phone ?? '',
                    'address_line' => $validated['address'],
                    'district' => $validated['city'] ?? $address->district,
                    'province' => $validated['province'] ?? $address->province,
                    'postal_code' => $validated['postal_code'] ?? $address->postal_code,
                ]);
            } else {
                Address::create([
                    'member_id' => $member->member_id,
                    'recipient_name' => $fullName ?: $member->username,
                    'phone' => $member->phone ?? '',
                    'address_line' => $validated['address'],
                    'subdistrict' => '',
                    'district' => $validated['city'] ?? '',
                    'province' => $validated['province'] ?? '',
                    'postal_code' => $validated['postal_code'] ?? '',
                    'is_default' => true,
                ]);
            }
        }

        $member->load('addresses');

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => new UserResource($member),
        ]);
    }

    /**
     * Save or update an address (e.g. from checkout).
     */
    public function saveAddress(Request $request)
    {
        $member = $request->user();

        $validated = $request->validate([
            'recipient_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address_line' => ['required', 'string', 'max:255'],
            'subdistrict' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'is_default' => ['nullable', 'boolean'],
        ]);

        $recipientName = $validated['recipient_name']
            ?: (trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) ?: $member->username);

        $address = Address::create([
            'member_id' => $member->member_id,
            'recipient_name' => $recipientName,
            'phone' => $validated['phone'] ?? ($member->phone ?? ''),
            'address_line' => $validated['address_line'],
            'subdistrict' => $validated['subdistrict'] ?? '',
            'district' => $validated['district'] ?? '',
            'province' => $validated['province'] ?? '',
            'postal_code' => $validated['postal_code'] ?? '',
            'is_default' => $validated['is_default'] ?? true,
        ]);

        if (!empty($validated['is_default'])) {
            Address::where('member_id', $member->member_id)
                ->where('address_id', '!=', $address->address_id)
                ->update(['is_default' => false]);
        }

        $member->load('addresses');

        return response()->json([
            'message' => 'Address saved successfully',
            'address' => $address,
            'user' => new UserResource($member),
        ]);
    }
}
