<?php

namespace App\Http\Requests\Ride;

use Illuminate\Foundation\Http\FormRequest;

class CreateRideRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'driver_id'              => ['required', 'exists:drivers,id'],
            'car_type_id'            => ['required', 'exists:car_types,id'],
            'pickup_latitude'        => ['required', 'numeric', 'between:-90,90'],
            'pickup_longitude'       => ['required', 'numeric', 'between:-180,180'],
            'pickup_address'         => ['nullable', 'string', 'max:255'],
            'destination_latitude'   => ['required', 'numeric', 'between:-90,90'],
            'destination_longitude'  => ['required', 'numeric', 'between:-180,180'],
            'destination_address'    => ['nullable', 'string', 'max:255'],
            'discount_code'          => ['nullable', 'string'],
        ];
    }
}
