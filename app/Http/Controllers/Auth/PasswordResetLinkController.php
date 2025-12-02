<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    
    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // 1) Resolve tenant from route or first segment
        $tenantKey = $request->route('tenant') ?? $request->segment(1);

        $tenant = Tenant::query()
            ->where('id', $tenantKey)
            // ->orWhere('slug', $tenantKey) // if you use slugs
            ->first();

        if (! $tenant) {
            return back()->withErrors([
                'email' => 'Invalid tenant context.',
            ]);
        }

        // 2) Initialize tenancy context if not already
        if (! tenancy()->initialized) {
            tenancy()->initialize($tenant);
        }

        // 3) Make sure the email belongs to THIS tenant
        $user = User::query()
            ->where('email', $request->email)
            ->where('tenant_id', $tenant->id)
            ->first();

        if (! $user) {
            return back()->withErrors([
                'email' => __('We can’t find a user with that email for this site.'),
            ]);
        }

        // 4) Ask Laravel to send the reset link for that user
        $status = Password::broker()->sendResetLink([
            'email' => $user->email,
        ]);

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

}
