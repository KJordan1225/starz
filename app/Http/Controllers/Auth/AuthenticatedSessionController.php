<?php

namespace App\Http\Controllers\Auth;


use Stancl\Tenancy\Tenancy;
use Illuminate\Http\Request;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse; 
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Models\User;
use App\Models\Tenant;

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
        // Authenticate the user
        $request->authenticate();

        // Regenerate the session to prevent session fixation attacks
        $request->session()->regenerate();

        // Retrieve the authenticated user
        $user = Auth::user();

        $tenantId = $user->tenant_id;        

        // Check the user's role and redirect accordingly
        if ($user->hasRole('super-admin')) {
            // Redirect to the admin dashboard if the user has the admin role
            return redirect()->route('super-admin.dashboard');
        }

        if ($user->hasRole('admin', $tenantId) && $tenantId !== null) {
            // Redirect to the admin dashboard if the user has the admin role
            return redirect()->intended(route('tenant.admin.home', ['tenant' => $tenantId], absolute: false));
        }

        if ($user->hasRole('user', $tenantId) && $tenantId !== null) {            
            // Redirect to the user dashboard if the user has the user role
            return redirect()->intended(route('tenant.creator.images.creatorImagePageTwo', ['tenant' => $tenantId], absolute: false));
        }

        // Default redirect (if no specific role is matched)
        return redirect()->intended(route('dashboard', absolute: false));
        // return redirect()->intended(route('dashboard', absolute: false));

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
        $tenant = Tenant::find($tenantId)->first();

        $currentTenantActiveSubscription = $user->hasActiveSubscriptionForTenant($tenant);
        
        if ($isAdmin)
        {
            $request->session()->regenerate();

            return redirect()->intended(route('tenant.admin.home', ['tenant' => $tenantId], absolute: false));
        } else {
            $request->session()->regenerate();

            // if auth user has subscription to curren tenant account: goto user dashboard
            if ($currentTenantActiveSubscription) {
                return redirect()->intended(route('tenant.user.home', ['tenant' => $tenantId], absolute: false));
            } else {            
            // else allow user to subscribe to current tenant account            
                return redirect()->intended(route('tenant.creator.images.creatorImagePageTwo', ['tenant' => $tenantId], absolute: false));
            }
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

    /**
     * Destroy an authenticated session.
     */
    public function tenantDestroy(Request $request): RedirectResponse
    {         
        $tenantId = tenant('id');
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // return redirect('/');
        return redirect("/{$tenantId}");
    }
}
