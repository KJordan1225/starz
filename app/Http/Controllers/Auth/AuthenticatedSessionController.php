<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Display the login view.
     */
    public function tenantCreate(): View
    {
        return view('auth.tenant.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Handle an incoming authentication request.
     */
    public function tenantStore(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $tenantId = request()->segment(1);
        $user = auth()->user();
        
        $isAdmin = $user->hasRole('admin', $tenantId);

        if ($isAdmin)
        {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        } else {
            $request->session()->regenerate();

            return redirect()->intended(route('tenant.show.subscribe', ['tenant' => $tenantId], absolute: false));
        } 
    
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
