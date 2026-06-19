<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboard,
    ) {}

    public function index()
    {
        $data = $this->dashboard->getDashboardData();

        return view('dashboard.index', $data);
    }
}
