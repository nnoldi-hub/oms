<?php

namespace App\Services;

use App\Models\DailyMeal;
use App\Models\MealChangeLog;
use App\Models\Menu;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Inregistreaza in jurnal schimbarile de plan facute de coordonatori si
 * trimite o notificare in aplicatie administratorilor.
 *
 * Modificarile facute de administrator NU sunt jurnalizate si nu trimit notificari.
 */
class MealChangeNotifier
{
    public function __construct(private MenuUsageInsight $insight) {}

    public function dishChanged(DailyMeal $meal, string $type, ?int $oldMenuId, ?int $newMenuId): void
    {
        $menu = $newMenuId === null ? null : Menu::find($newMenuId);
        $oldName = $oldMenuId === null ? 'fara' : (Menu::find($oldMenuId)?->name ?? '-');
        $newName = $menu?->name ?? 'fara';

        $warnings = $menu === null ? [] : $this->insight->notes($menu, $meal->meal_date, $meal);

        $body = sprintf(
            '%s: %s schimbat de la „%s” la „%s”.',
            $meal->congregationNames() ?: 'Congregatie',
            self::typeLabel($type),
            $oldName,
            $newName,
        );

        $this->recordAndNotify(
            $meal,
            MealChangeLog::EVENT_DISH_CHANGED,
            $oldName,
            $newName,
            $warnings,
            'Plan modificat de un coordonator',
            $body,
        );
    }

    public function cookedDifferent(DailyMeal $meal, ?int $plannedMenuId, ?int $actualMenuId): void
    {
        $actual = $actualMenuId === null ? null : Menu::find($actualMenuId);
        $plannedName = $plannedMenuId === null ? 'fara' : (Menu::find($plannedMenuId)?->name ?? '-');
        $actualName = $actual?->name ?? '-';

        $warnings = $actual === null ? [] : $this->insight->notes($actual, $meal->meal_date, $meal);

        $body = sprintf(
            '%s: s-a gatit „%s” in loc de „%s” (%s).',
            $meal->congregationNames() ?: 'Congregatie',
            $actualName,
            $plannedName,
            $meal->meal_date?->translatedFormat('d M Y') ?? '-',
        );

        $this->recordAndNotify(
            $meal,
            MealChangeLog::EVENT_COOKED_DIFFERENT,
            $plannedName,
            $actualName,
            $warnings,
            'Fel gatit diferit de plan',
            $body,
        );
    }

    public function recipeCreated(Menu $menu, ?User $user = null): void
    {
        $user ??= auth()->user();

        $body = sprintf(
            '%s a creat reteta „%s” (%s), care asteapta verificare.',
            $menu->creatorCongregation?->name ?? 'O congregatie',
            $menu->name,
            self::typeLabel((string) $menu->type),
        );

        MealChangeLog::create([
            'daily_meal_id' => null,
            'week_id' => null,
            'congregation_id' => $menu->created_by_congregation_id,
            'user_id' => $user?->id,
            'meal_date' => null,
            'event_type' => MealChangeLog::EVENT_RECIPE_CREATED,
            'old_value' => null,
            'new_value' => $menu->name,
            'warnings' => null,
        ]);

        $this->notifyAdmins('Reteta noua de verificat', $body, []);
    }

    public function costDeviation(DailyMeal $meal, float $estimated, float $actual): void
    {
        $percent = $estimated > 0 ? (int) round((($actual - $estimated) / $estimated) * 100) : 0;

        $body = sprintf(
            '%s: costul real (%s RON) depaseste cu %d%% costul estimat (%s RON) pentru ziua %s.',
            $meal->congregationNames() ?: 'Congregatie',
            number_format($actual, 2, '.', ' '),
            $percent,
            number_format($estimated, 2, '.', ' '),
            $meal->meal_date?->translatedFormat('d M Y') ?? '-',
        );

        $this->recordAndNotify(
            $meal,
            MealChangeLog::EVENT_COST_DEVIATION,
            number_format($estimated, 2, '.', ''),
            number_format($actual, 2, '.', ''),
            [],
            'Cost real mult diferit de estimat',
            $body,
        );
    }

    /**
     * @param  array<int, string>  $warnings
     */
    private function recordAndNotify(
        DailyMeal $meal,
        string $eventType,
        ?string $oldValue,
        ?string $newValue,
        array $warnings,
        string $title,
        string $body,
    ): void {
        MealChangeLog::create([
            'daily_meal_id' => $meal->id,
            'week_id' => $meal->week_id,
            'congregation_id' => $meal->congregation_id,
            'user_id' => auth()->id(),
            'meal_date' => $meal->meal_date,
            'event_type' => $eventType,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'warnings' => $warnings === [] ? null : $warnings,
        ]);

        $this->notifyAdmins($title, $body, $warnings);
    }

    /**
     * @param  array<int, string>  $warnings
     */
    private function notifyAdmins(string $title, string $body, array $warnings): void
    {
        $admins = $this->admins();

        if ($admins->isEmpty()) {
            return;
        }

        if ($warnings !== []) {
            $body .= ' Avertismente: '.implode(' ', $warnings);
        }

        $notification = Notification::make()
            ->title($title)
            ->body($body);

        if ($warnings !== []) {
            $notification->warning();
        } else {
            $notification->info();
        }

        $notification->sendToDatabase($admins, true);
    }

    /**
     * @return Collection<int, User>
     */
    private function admins(): Collection
    {
        return User::query()->where('role', 'admin')->get();
    }

    private static function typeLabel(string $type): string
    {
        return [
            'main' => 'fel principal',
            'soup' => 'ciorba',
            'dessert' => 'desert',
        ][$type] ?? $type;
    }
}
