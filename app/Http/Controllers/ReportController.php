<?php

namespace App\Http\Controllers;

use App\Models\Cattle;
use App\Models\Sale;
use App\Models\Vaccine;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index()
    {
        $report = [
            'total_cattle' => Cattle::count(),
            'available_cattle' => Cattle::where('status', 'Disponible')->count(),
            'sold_cattle' => Cattle::where('status', 'Vendido')->count(),
            'completed_sales' => Sale::count(),
            'total_sales_amount' => Sale::sum('total_amount'),
            'total_sold_weight' => Sale::sum('weight'),
            'average_price_per_kg' => Sale::avg('price_per_kg') ?? 0,
            'applied_vaccines' => Vaccine::count(),
            'upcoming_vaccines' => Vaccine::whereNotNull('next_date')
                ->whereBetween('next_date', [Carbon::today(), Carbon::today()->addDays(30)])
                ->count(),
            'overdue_vaccines' => Vaccine::whereNotNull('next_date')
                ->whereDate('next_date', '<', Carbon::today())
                ->count(),
        ];

        return view('reports.index', compact('report'));
    }
}
