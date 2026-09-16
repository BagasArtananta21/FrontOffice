<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SuperAdminTest extends Controller {
    public function superAdmin(): View
    {
        return view('super-admin.super-admin-test');
    }
}