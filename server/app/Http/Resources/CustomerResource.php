<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Customer
 */
class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $personal = trim("{$this->first_name} {$this->last_name}");

        return [
            'id' => $this->id,
            'name' => $this->company_name ?: ($personal ?: 'Customer'),
            'company_name' => $this->company_name,
            'contact_name' => $personal ?: null,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'zip' => $this->zip,
        ];
    }
}
