<?php

namespace App\Http\Controllers;

use App\Models\PublicCongregationWeekLink;
use App\Services\CongregationScheduleReport;
use App\Services\WeeklyShoppingListCalculator;
use Illuminate\Contracts\View\View;

class PublicCongregationWeekSheetController extends Controller
{
    public function __invoke(
        PublicCongregationWeekLink $publicCongregationWeekLink,
        WeeklyShoppingListCalculator $calculator,
        CongregationScheduleReport $scheduleReport,
    ): View {
        $publicCongregationWeekLink->loadMissing('congregation');
        $congregation = $publicCongregationWeekLink->congregation;
        $week = $scheduleReport->build($congregation);

        return view('public-congregation-week-sheet', [
            'week' => $week,
            'congregation' => $congregation,
            'shoppingList' => $calculator->calculate($week),
        ]);
    }
}
