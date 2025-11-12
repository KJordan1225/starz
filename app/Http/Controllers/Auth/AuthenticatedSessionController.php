<?php

namespace App\Http\Controllers\Auth;

use Illuminate\View\View;
use Stancl\Tenancy\Tenancy;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Http\Requests\Auth\LoginRequest;

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
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = $request->user();

        // Detect (or gently initialize) tenant context
        /** @var Tenancy $tenancy */
        $tenancy  = app(Tenancy::class);
        $isTenant = tenancy()->initialized ? true : false;

        // Extract first segment from url
        // Get the full URL path
            $urlPath = parse_url(request()->getRequestUri(), PHP_URL_PATH);
            // Remove the leading slash and split the URL into segments
            $segments = explode('/', trim($urlPath, '/'));
            // Get the first segment
            $firstSegment = $segments[0] ?? null; 


        if (! $isTenant) {
            // Optional: try to initialize from route param if present
            if ($tenantParam = request()->query('tenant')) {
                $tenantModel = \App\Models\Tenant::query()
                    ->where('id', $tenantParam)
                    ->first();
                if ($tenantModel) {
                    $tenancy->initialize($tenantModel);
                    $isTenant = true;
                }
            }
        }

        $tenantId = $isTenant ? tenant('id') : null;
        // 1) Landlord-level super admin?
        if ($user->hasRole('super-admin', null)) {
            dd('super admin');
        //    return redirect()->intended(route('landlord.dashboard.index')); // adjust to your landlord route name
        }
        // 2) Tenant admin?
        if ($isTenant && $user->hasRole('admin', $tenantId)) {
            dd('tenant admin');
            // return redirect()
            //     ->intended(route('tenant.admin.dashboard', ['tenant' => $tenantId]))
            //     ->with([
            //         'user_id'   => $user->id,
            //     ]);                      
        }
        
        if ($isTenant) { 
            dd('tenant user');          
            // return redirect()->intended(route('tenant.landing', ['tenant' => $tenantId]));                       
        }

        // 4) Fallback: central guest
        return redirect()->intended(route('tenant.landing', ['tenant' => $tenantId]));
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
