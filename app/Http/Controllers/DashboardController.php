<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function superAdminDashboard()
    {
        return view('landlord.superAdmin.dashboard');
    }
}
