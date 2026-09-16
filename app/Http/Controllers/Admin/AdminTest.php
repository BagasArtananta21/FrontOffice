<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use illuminate\View\View;

class AdminTest extends Controller {
    public function admin(): View
    {
        return view('admin.admin-test');
    }
}