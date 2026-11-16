<?php

namespace App\Filament\Pages;

use App\Models\Menu;
use App\Services\MenuReviewService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

class ReteteDeVerificat extends Page
{
    protected string $view = 'filament.pages.retete-de-verificat';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?string $navigationLabel = 'Retete de verificat';

    protected static string|\UnitEnum|null $navigationGroup = 'Bucatarie';

    protected static ?int $navigationSort = 5;

    public ?int $mergeSourceId = null;

    public ?int $mergeTargetId = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('review', Menu::class) ?? false;
    }

    /**
     * @return Collection<int, Menu>
     */
    public function getPendingMenusProperty(): Collection
    {
        return Menu::query()
            ->pendingReview()
            ->with('creatorCongregation')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @return array<int, string>
     */
    public function getMergeTargetOptionsProperty(): array
    {
        if ($this->mergeSourceId === null) {
            return [];
        }

        $source = Menu::query()->find($this->mergeSourceId);

        if ($source === null) {
            return [];
        }

        return Menu::query()
            ->whereKeyNot($source->id)
            ->where('type', $source->type)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Menu $menu): array => [$menu->id => $menu->name])
            ->all();
    }

    public function approve(int $menuId): void
    {
        abort_unless(auth()->user()?->can('review', Menu::class) ?? false, 403);

        $menu = Menu::query()->findOrFail($menuId);
        app(MenuReviewService::class)->approve($menu);

        Notification::make()->title('Reteta a fost verificata.')->success()->send();
    }

    public function openMerge(int $menuId): void
    {
        abort_unless(auth()->user()?->can('review', Menu::class) ?? false, 403);

        $this->mergeSourceId = $menuId;
        $this->mergeTargetId = null;
    }

    public function closeMerge(): void
    {
        $this->mergeSourceId = null;
        $this->mergeTargetId = null;
    }

    public function merge(): void
    {
        abort_unless(auth()->user()?->can('review', Menu::class) ?? false, 403);

        $this->validate([
            'mergeSourceId' => ['required', 'integer', 'exists:menus,id'],
            'mergeTargetId' => ['required', 'integer', 'exists:menus,id', 'different:mergeSourceId'],
        ]);

        $source = Menu::query()->findOrFail((int) $this->mergeSourceId);
        $target = Menu::query()->findOrFail((int) $this->mergeTargetId);

        app(MenuReviewService::class)->merge($source, $target);

        $this->closeMerge();

        Notification::make()->title('Retetele au fost unite.')->success()->send();
    }
}
