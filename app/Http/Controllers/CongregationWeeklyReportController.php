<?php

namespace App\Http\Controllers;

use App\Models\Congregation;
use App\Models\Week;
use App\Services\CongregationScheduleReport;
use App\Services\WeeklyShoppingListCalculator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;

class CongregationWeeklyReportController extends Controller
{
    public function __invoke(
        Week $week,
        Congregation $congregation,
        WeeklyShoppingListCalculator $calculator,
        CongregationScheduleReport $scheduleReport,
    ): View {
        Gate::authorize('viewFull', $week);
        Gate::authorize('view', $congregation);

        $reportWeek = $scheduleReport->build($congregation);

        return view('congregation-weekly-report', [
            'week' => $reportWeek,
            'congregation' => $congregation,
            'shoppingList' => $calculator->calculate($reportWeek),
        ]);
    }
}
