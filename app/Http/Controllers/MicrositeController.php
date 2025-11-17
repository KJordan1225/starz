<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MicrositeController extends Controller
{
    public function show()
    {
        return view('landlord.microsite.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id'             => ['required', 'string', 'regex:/^[a-z0-9_-]+$/', 'max:191', 'unique:tenants,id'],
            'display_name'   => ['required', 'string', 'max:255'],           

            // Validation for email, password, and confirm password
            'email' => [
                'required', 
                'email', 
                'max:255', 
                'unique:users,email,NULL,id,tenant_id,' . tenant('id')  // Ensure email is unique within tenant
            ],
            'password'       => ['required', 'string', 'min:8', 'confirmed'], // Confirmed automatically checks password_confirmation field
            'password_confirmation' => ['required', 'string', 'min:8'], // Ensure password confirmation matches
        
        ]);

        $tenant =Tenant::create([
            'id'   => $data['id'],
            'data' => [],
            'display_name' => $data['display_name'], // Adjust domain as needed
            'creator_email' => $data['email'],
        ]);

        // 2) Seed per-tenant roles & permissions
        $tid = method_exists($tenant, 'getTenantKey') ? $tenant->getTenantKey() : $tenant->id;

        // Roles
        $tAdmin = Role::firstOrCreate(['name' => 'admin', 'tenant_id' => $tid]);
        $tUser  = Role::firstOrCreate(['name' => 'user',  'tenant_id' => $tid]);
       
        // (Optional) auto-assign the creating user as tenant admin:
        // if (auth()->check()) {
        //     auth()->user()->roles()->syncWithoutDetaching([$tAdmin->id]);
        // }

        // 3) Register the user within the tenant
        // Create the user
        $user = User::create([
            'name' => $data['display_name'],
            'tenant_id' => $data['id'],
            'email' => $request->email,
            'password' => Hash::make($request->password), // Hash the password
        ]);

        // Assign the roleto new user
        $user->roles()->attach($tAdmin->id, ['tenant_id' => $tenant->id ?? null]);

        Auth::login($user);

        return redirect()->route('guest.home')->with('status', 'Tenant created.');
    }

    
}
