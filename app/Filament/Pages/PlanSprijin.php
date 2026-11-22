<?php

namespace App\Filament\Pages;

use App\Models\DailyMeal;
use App\Models\Donation;
use App\Models\Week;
use App\Services\SupportPlanService;
use App\Support\ContactStatus;
use Carbon\CarbonImmutable;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class PlanSprijin extends Page
{
    protected string $view = 'filament.pages.plan-sprijin';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Plan sprijin';

    protected static string|\UnitEnum|null $navigationGroup = 'Aprovizionare';

    protected static ?int $navigationSort = 7;

    public string $weekFilter = '';

    /** @var array<string, string> cheie „mealId:congregationId” => notite */
    public array $contactNotes = [];

    public ?int $addingMealId = null;

    public ?int $addingCongregationId = null;

    public string $newKind = Donation::KIND_SWEET_SNACK;

    public string $newDescription = '';

    public string $newQuantity = '';

    public string $newUnit = '';

    public function mount(): void
    {
        $requested = request()->query('week');

        if (filled($requested) && Week::query()->whereKey($requested)->exists()) {
            $this->weekFilter = (string) $requested;
        } else {
            $nextMonday = CarbonImmutable::now()->startOfWeek()->addWeek()->toDateString();
            $this->weekFilter = (string) (Week::query()
                ->whereDate('start_date', '>=', $nextMonday)
                ->orderBy('start_date')
                ->value('id')
                ?? Week::query()->orderByDesc('start_date')->value('id') ?? '');
        }

        $this->loadContactNotes();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function updatedWeekFilter(): void
    {
        $this->closeAddContribution();
        $this->loadContactNotes();
    }

    /** @return array<string, string> */
    public function getWeekOptionsProperty(): array
    {
        $options = ['all' => 'Toată perioada'];

        foreach (Week::query()->orderByDesc('start_date')->get() as $week) {
            $options[(string) $week->id] = sprintf(
                'Săptămâna %d (%s – %s)',
                $week->week_number,
                $week->start_date->translatedFormat('d.m'),
                $week->start_date->copy()->addDays(6)->translatedFormat('d.m'),
            );
        }

        return $options;
    }

    public function getSelectedWeekProperty(): ?Week
    {
        return $this->weekFilter === 'all' || $this->weekFilter === ''
            ? null
            : Week::query()->find((int) $this->weekFilter);
    }

    /** @return array<string, mixed> */
    public function getPlanProperty(): array
    {
        return app(SupportPlanService::class)->build($this->selectedWeek);
    }

    /** @return array<string, string> */
    public function getKindOptionsProperty(): array
    {
        return Donation::CONTRIBUTION_CATEGORIES;
    }

    /** @return array<string, string> */
    public function getContactStatusOptionsProperty(): array
    {
        return ContactStatus::options();
    }

    public function setContactStatus(int $mealId, int $congregationId, string $status): void
    {
        $this->authorizeAdmin();

        if (! array_key_exists($status, ContactStatus::options())) {
            return;
        }

        $meal = DailyMeal::query()->findOrFail($mealId);
        $meal->updateContributorContact($congregationId, $status, null, $status !== ContactStatus::NOT_CONTACTED);

        Notification::make()->title('Statusul contactului a fost salvat.')->success()->send();
    }

    public function markContacted(int $mealId, int $congregationId): void
    {
        $this->setContactStatus($mealId, $congregationId, ContactStatus::CONTACTED);
    }

    public function saveContactNotes(int $mealId, int $congregationId): void
    {
        $this->authorizeAdmin();

        $meal = DailyMeal::query()->with('contributingCongregations')->findOrFail($mealId);
        $congregation = $meal->contributingCongregations()->whereKey($congregationId)->first();

        if ($congregation === null) {
            return;
        }

        $status = (string) ($congregation->pivot->contact_status ?? ContactStatus::NOT_CONTACTED);
        $notes = (string) ($this->contactNotes[$this->noteKey($mealId, $congregationId)] ?? '');

        $meal->updateContributorContact($congregationId, $status, $notes !== '' ? $notes : null);
    }

    public function openAddContribution(int $mealId, int $congregationId): void
    {
        $this->authorizeAdmin();

        $this->addingMealId = $mealId;
        $this->addingCongregationId = $congregationId;
        $this->newKind = Donation::KIND_SWEET_SNACK;
        $this->newDescription = '';
        $this->newQuantity = '';
        $this->newUnit = '';
    }

    public function closeAddContribution(): void
    {
        $this->addingMealId = null;
        $this->addingCongregationId = null;
    }

    public function saveContribution(): void
    {
        $this->authorizeAdmin();

        $this->validate([
            'addingMealId' => ['required', 'integer', 'exists:daily_meals,id'],
            'addingCongregationId' => ['required', 'integer', 'exists:congregations,id'],
            'newKind' => ['required', 'in:'.implode(',', array_keys(Donation::CONTRIBUTION_CATEGORIES))],
            'newDescription' => ['required', 'string', 'max:255'],
            'newQuantity' => ['nullable', 'numeric', 'min:0'],
            'newUnit' => ['nullable', 'string', 'max:20'],
        ]);

        $meal = DailyMeal::query()->findOrFail($this->addingMealId);

        Donation::query()->create([
            'congregation_id' => $this->addingCongregationId,
            'donor_congregation_id' => $this->addingCongregationId,
            'daily_meal_id' => $meal->id,
            'date' => $meal->meal_date?->toDateString(),
            'kind' => $this->newKind,
            'status' => Donation::STATUS_PROMISED,
            'description' => trim($this->newDescription),
            'quantity' => $this->newQuantity !== '' ? (float) $this->newQuantity : null,
            'unit' => $this->newUnit !== '' ? $this->newUnit : null,
            'recorded_by' => auth()->id(),
        ]);

        $this->closeAddContribution();
        $this->newDescription = '';
        $this->newQuantity = '';
        $this->newUnit = '';

        Notification::make()->title('Contribuția a fost adăugată ca promisă.')->success()->send();
    }

    public function markReceived(int $donationId): void
    {
        $this->authorizeAdmin();

        $donation = Donation::query()->findOrFail($donationId);

        if ($donation->status === Donation::STATUS_PROMISED) {
            $donation->status = Donation::STATUS_RECEIVED;
            $donation->save();
        }

        Notification::make()->title('Contribuția a fost marcată primită.')->success()->send();
    }

    private function noteKey(int $mealId, int $congregationId): string
    {
        return $mealId.'_'.$congregationId;
    }

    private function loadContactNotes(): void
    {
        $this->contactNotes = [];

        if ($this->weekFilter === '') {
            return;
        }

        $meals = $this->selectedWeek !== null
            ? $this->selectedWeek->dailyMeals()->with('contributingCongregations')->get()
            : DailyMeal::query()->with('contributingCongregations')->get();

        foreach ($meals as $meal) {
            foreach ($meal->contributingCongregations as $congregation) {
                $this->contactNotes[$this->noteKey($meal->id, $congregation->id)] = (string) ($congregation->pivot->contact_notes ?? '');
            }
        }
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin() ?? false, 403);
    }
}
