<?php

namespace App\Filament\Widgets;

use App\Services\WeekScheduleSummaryService;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class WeeklyScheduleSummary extends Widget
{
    protected string $view = 'filament.widgets.weekly-schedule-summary';

    protected static ?int $sort = -2;

    protected int|string|array $columnSpan = 'full';

    /** @return Collection<int, array{week_id: int, week_number: int, period: string, working_days: string, cooking: string, days_count: int}> */
    public function getWeeksProperty(): Collection
    {
        return app(WeekScheduleSummaryService::class)->summaries();
    }

    public static function canView(): bool
    {
        return app(WeekScheduleSummaryService::class)->canView();
    }
}
