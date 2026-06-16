<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // السماح للمستخدم المسجل فقط بالوصول (يتم التحكم به بالـ Middleware)
    }

    public function rules(): array
    {
        return [
            'ride_id' => 'required|exists:rides,id',
            'reason'  => 'required|string|min:10|max:1000',
        ];
    }
}
