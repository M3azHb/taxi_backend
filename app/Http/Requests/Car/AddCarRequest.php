<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;

class AddCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'car_type_id' => 'required|exists:car_types,id',
            'plate_number' => 'required|unique:cars,plate_number',
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'manufacturing_year' => 'required|integer|between:1990,2026',
            'color' => 'required|string|max:50',
        ];
    }
}