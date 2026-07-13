<?php

namespace App\Http\Requests\Car;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'car_type_id' => 'required|exists:car_types,id',
            'plate_number' => [
                'required',
                Rule::unique('cars', 'plate_number')->ignore(
                    $this->user()->cars()->first()?->id
                ),
            ],
            'brand' => 'required|string|max:100',
            'model' => 'required|string|max:100',
            'manufacturing_year' => 'required|integer|between:1990,2026',
            'color' => 'required|string|max:50',
        ];
    }
}