<?php

namespace App\Filament\Widgets;

use App\Models\DailyMeal;
use App\Services\DailyMessageBuilder;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Gate;

class TomorrowMessageWidget extends Widget
{
    protected string $view = 'filament.widgets.tomorrow-message';

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public function getMessageProperty(): ?string
    {
        $meal = DailyMeal::query()
            ->whereDate('meal_date', today()->addDay())
            ->with(['menu', 'soupMenu', 'dessertMenu', 'congregation', 'workTeams', 'volunteers'])
            ->first();

        if ($meal === null) {
            return null;
        }

        return app(DailyMessageBuilder::class)->dayMessage($meal);
    }

    public static function canView(): bool
    {
        return Gate::allows('viewAny', DailyMeal::class);
    }
}
