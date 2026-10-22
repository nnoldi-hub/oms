<?php

namespace App\Http\Controllers;

use App\Models\Week;
use App\Services\WeeklyPlanReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class WeeklyPlanPdfController extends Controller
{
    public function __invoke(Request $request, Week $week, WeeklyPlanReportBuilder $builder): Response
    {
        Gate::authorize('downloadPlan', $week);

        $includePhones = $request->boolean('include_phones', true);

        $pdf = Pdf::loadView('weekly-plan-report', [
            'report' => $builder->build($week, $includePhones),
        ])
            ->setPaper('a4', 'landscape')
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('enableRemote', false)
            ->setOption('enablePhp', false);

        return $pdf->download('plan-saptamana-'.$week->week_number.'.pdf');
    }
}
