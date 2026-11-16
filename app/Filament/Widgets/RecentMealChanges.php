<?php

namespace App\Filament\Widgets;

use App\Models\MealChangeLog;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;

class RecentMealChanges extends Widget
{
    protected string $view = 'filament.widgets.recent-meal-changes';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = 'full';

    /**
     * @return Collection<int, MealChangeLog>
     */
    public function getChangesProperty(): Collection
    {
        return MealChangeLog::query()
            ->with(['congregation', 'user'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();
    }

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}
