<?php

namespace App\Http\Controllers;

use App\Models\Cattle;
use App\Models\Sale;
use App\Models\Vaccine;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_cattle' => Cattle::count(),
            'available_cattle' => Cattle::where('status', 'Disponible')->count(),
            'sold_cattle' => Cattle::where('status', 'Vendido')->count(),
            'applied_vaccines' => Vaccine::count(),
            'upcoming_vaccines' => Vaccine::whereNotNull('next_date')
                ->whereBetween('next_date', [Carbon::today(), Carbon::today()->addDays(30)])
                ->count(),
            'total_sales' => Sale::sum('total_amount'),
        ];

        $recentCattle = Cattle::latest()->take(5)->get();
        $recentSales = Sale::with('cattle')->latest('sale_date')->take(5)->get();

        return view('dashboard', compact('stats', 'recentCattle', 'recentSales'));
    }
}
