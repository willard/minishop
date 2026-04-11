<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('customer');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'customer' => $user->customer ? [
                'id' => $user->customer->id,
                'phone' => $user->customer->phone,
                'is_active' => $user->customer->is_active,
            ] : null,
        ]);
    }
}
