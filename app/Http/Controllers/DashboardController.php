<?php

namespace App\Http\Controllers;

use App\Application\Rentals\RentalQueryService;

final class DashboardController extends Controller
{
    public function __invoke(RentalQueryService $queries) { return view('dashboard', $queries->dashboard()); }
}
