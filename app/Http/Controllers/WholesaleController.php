<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class WholesaleController extends Controller
{
    public function index(): View
    {
        return view('wholesale.index');
    }
}
