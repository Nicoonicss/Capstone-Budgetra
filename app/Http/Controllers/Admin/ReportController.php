<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\OcrLog;
use App\Models\Review;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users'    => User::count(),
            'total_trips'    => Trip::count(),
            'total_expenses' => Expense::sum('amount') ?? 0,
            'total_reviews'  => Review::count(),
            'ocr_success'    => OcrLog::where('status', 'success')->count(),
        ];

        $topDestinations = Trip::select('destination', DB::raw('count(*) as trip_count'))
            ->groupBy('destination')
            ->orderByDesc('trip_count')
            ->limit(10)
            ->get();

        return view('admin.reports.index', compact('stats', 'topDestinations'));
    }
}
