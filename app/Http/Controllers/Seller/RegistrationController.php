<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function index(): View
    {
        return view('seller.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'business_name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'gst_number' => 'nullable|string|max:20',
            'terms' => 'required|accepted',
        ]);

        DB::transaction(function () use ($validated) {
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => 'seller',
            ]);

            Seller::create([
                'user_id' => $user->id,
                'business_name' => $validated['business_name'],
                'slug' => Str::slug($validated['business_name']) . '-' . Str::random(5),
                'description' => $validated['description'] ?? null,
                'gst_number' => $validated['gst_number'] ?? null,
                'status' => 'pending',
            ]);
        });

        return redirect()->route('seller.register')
            ->with('success', 'Your seller application has been submitted. We will review and get back to you shortly.');
    }
}
