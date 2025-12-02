<?php

namespace App\Http\Controllers\Auth;

use App\Models\Role;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Auth\Events\Registered;
use Stancl\Tenancy\Facades\Tenancy;
use App\Services\TenantService;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    public function tenantCreate(Request $request): View
    {
        $tenantId = request()->segment(1);
        return view('auth.tenant.register', compact('tenantId'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        
       
    
        $tenantService = new TenantService();
        $tenantId = $tenantService->getTenantId();
    
       
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Find the tenant you want to initialize
        // $tenant = Tenant::find('acme'); // or whatever your tenant ID is

        // Initialize tenancy
        // Tenancy::initialize($tenantId);

        
        $user = User::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        if ($user->name === 'Super Admin') {
            $tid = NULL;
            $tAdmin = Role::firstOrCreate(['name' => 'super-admin', 'tenant_id' => $tid]);
            // Assign the role to new user
            $user->roles()->attach($tAdmin->id, ['tenant_id' => $tenant->id ?? null]);
        }

        if (! is_null($user->tenant_id)) {
            $tid = $user->tenant_id;

            $tUser = Role::firstOrCreate(
                ['name' => 'user', 'tenant_id' => $tid],
                // optional defaults:
                ['name' => 'user', 'guard_name' => 'web', 'scope' => 'tenant']
            );

            // Assign the role to the new user
            $user->roles()->attach($tUser->id, [
                'tenant_id' => $tenant->id ?? null,
            ]);
        }

        Auth::login($user);

        return redirect(route('guest.home', absolute: false));
    }



    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function tenantStore(Request $request): RedirectResponse
    {
       
        $tenantService = new TenantService();
        $tenantId = $tenantService->getTenantId();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // $tenantId = request()->segment(1);
        // // Find the tenant you want to initialize
        // $tenant = Tenant::find($tenantId); // or whatever your tenant ID is

        // Initialize tenancy
        // Tenancy::initialize($tenant);

        
        $user = User::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        if ($user->name === 'Super Admin') {
            $tid = NULL;
            $tAdmin = Role::firstOrCreate(['name' => 'super-admin', 'tenant_id' => $tid]);
            // Assign the role to new user
            $user->roles()->attach($tAdmin->id, ['tenant_id' => $tenant->id ?? null]);
        }

        if (! is_null($user->tenant_id)) {
            $tid = $user->tenant_id;

            $tUser = Role::firstOrCreate(
                ['name' => 'user', 'tenant_id' => $tid],
                // optional defaults:
                ['name' => 'user', 'guard_name' => 'web', 'scope' => 'tenant']
            );

            // Assign the role to the new user
            $user->roles()->attach($tUser->id, [
                'tenant_id' => $tenant->id ?? null,
            ]);
        }

        Auth::login($user);

        return redirect(route('guest.home', absolute: false));
    }
    
}
