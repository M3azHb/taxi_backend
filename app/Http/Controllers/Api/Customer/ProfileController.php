<?php

namespace App\Http\Controllers\Api\Customer;

use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $customer = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'id'    => $customer->id,
                'name'  => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'stats' => [
                    'total_rides'     => $customer->rides()->count(),
                    'completed_rides' => $customer->rides()->where('status', 'completed')->count(),
                ],
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $customer = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'unique:customers,phone,' . $customer->id],
        ]);

        $customer->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $customer,
        ]);
    }
    public function changePassword(Request $request): JsonResponse
{
    $customer = $request->user();

    $data = $request->validate([
        'current_password' => ['required'],
        'new_password' => ['required', 'min:8', 'confirmed'],
    ]);

    if (!Hash::check($data['current_password'], $customer->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Current password is incorrect.',
        ], 422);
    }

    $customer->update([
        'password' => bcrypt($data['new_password']),
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Password changed successfully.',
    ]);
}
}