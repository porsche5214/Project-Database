<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $defaultAddress = $this->addresses ? ($this->addresses->firstWhere('is_default', true) ?? $this->addresses->first()) : null;

        return [
            'member_id' => $this->member_id,
            'username' => $this->username,
            'email' => $this->email,
            'first_name' => $this->first_name ?? '',
            'last_name' => $this->last_name ?? '',
            'phone' => $this->phone ?? '',
            'role' => $this->role,
            'default_address' => $defaultAddress ? [
                'address_id' => $defaultAddress->address_id,
                'recipient_name' => $defaultAddress->recipient_name,
                'phone' => $defaultAddress->phone,
                'address_line' => $defaultAddress->address_line,
                'subdistrict' => $defaultAddress->subdistrict,
                'district' => $defaultAddress->district,
                'province' => $defaultAddress->province,
                'postal_code' => $defaultAddress->postal_code,
                'full_address' => $defaultAddress->fullAddress(),
            ] : null,
            'addresses' => $this->addresses ? $this->addresses->map(fn ($addr) => [
                'address_id' => $addr->address_id,
                'recipient_name' => $addr->recipient_name,
                'phone' => $addr->phone,
                'address_line' => $addr->address_line,
                'subdistrict' => $addr->subdistrict,
                'district' => $addr->district,
                'province' => $addr->province,
                'postal_code' => $addr->postal_code,
                'is_default' => (bool) $addr->is_default,
                'full_address' => $addr->fullAddress(),
            ]) : [],
        ];
    }
}
