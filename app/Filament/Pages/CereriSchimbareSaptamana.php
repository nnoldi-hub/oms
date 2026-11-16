<?php

namespace App\Filament\Pages;

use App\Models\WeekChangeRequest;
use App\Services\WeekChangeRequestService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class CereriSchimbareSaptamana extends Page
{
    protected string $view = 'filament.pages.cereri-schimbare-saptamana';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?string $navigationLabel = 'Cereri schimbare saptamana';

    protected static string|\UnitEnum|null $navigationGroup = 'Planificare';

    protected static ?int $navigationSort = 3;

    public ?int $rejectingId = null;

    public string $resolutionNote = '';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->isAdmin() || $user?->isCoordinator());
    }

    public function isAdmin(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    /**
     * @return Collection<int, WeekChangeRequest>
     */
    public function getRequestsProperty(): Collection
    {
        return WeekChangeRequest::query()
            ->visibleTo(auth()->user())
            ->with(['week', 'congregation', 'user', 'preferredCongregation', 'preferredWeek', 'resolvedBy'])
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->orderByDesc('created_at')
            ->get();
    }

    public function approve(int $requestId): void
    {
        abort_unless($this->isAdmin(), 403);

        $request = WeekChangeRequest::query()->findOrFail($requestId);
        app(WeekChangeRequestService::class)->approve($request, auth()->user(), $this->resolutionNote ?: null);

        $this->resolutionNote = '';

        Notification::make()->title('Cererea a fost aprobata.')->success()->send();
    }

    public function openReject(int $requestId): void
    {
        abort_unless($this->isAdmin(), 403);

        $this->rejectingId = $requestId;
        $this->resolutionNote = '';
    }

    public function cancelReject(): void
    {
        $this->rejectingId = null;
        $this->resolutionNote = '';
    }

    public function reject(): void
    {
        abort_unless($this->isAdmin(), 403);
        abort_if($this->rejectingId === null, 422);

        $this->validate([
            'resolutionNote' => ['required', 'string', 'min:3', 'max:500'],
        ], [], ['resolutionNote' => 'motivul respingerii']);

        $request = WeekChangeRequest::query()->findOrFail($this->rejectingId);
        app(WeekChangeRequestService::class)->reject($request, auth()->user(), $this->resolutionNote);

        $this->cancelReject();

        Notification::make()->title('Cererea a fost respinsa.')->success()->send();
    }
}
