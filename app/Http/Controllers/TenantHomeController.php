<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TenantHomeController extends Controller
{
    public function show()
    {
        $tenantId = request()->segment(1);
        return view('tenant.home', ['tenant' => $tenantId]);
    }
}
