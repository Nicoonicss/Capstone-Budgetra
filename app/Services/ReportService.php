<?php
namespace App\Services;

use App\Models\Trip;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportService
{
    public function __construct(private BudgetService $budget) {}

    public function generatePdf(Trip $trip): \Barryvdh\DomPDF\PDF
    {
        $summary  = $this->budget->summary($trip->load('budgets'));
        $expenses = $trip->expenses()->orderBy('expense_date')->get();

        $pdf = Pdf::loadView('traveler.reports.pdf', compact('trip', 'summary', 'expenses'));
        $pdf->setPaper('a4', 'portrait');
        return $pdf;
    }
}
