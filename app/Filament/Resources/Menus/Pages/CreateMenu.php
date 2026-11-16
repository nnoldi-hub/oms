<?php

namespace App\Filament\Resources\Menus\Pages;

use App\Filament\Resources\Menus\MenuResource;
use App\Services\MealChangeNotifier;
use Filament\Resources\Pages\CreateRecord;

class CreateMenu extends CreateRecord
{
    protected static string $resource = MenuResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        if ($user?->isCoordinator()) {
            $data['created_by_congregation_id'] = $user->congregation_id;
            $data['is_reviewed'] = false;
        } else {
            $data['is_reviewed'] = true;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if (auth()->user()?->isCoordinator()) {
            app(MealChangeNotifier::class)->recipeCreated($this->getRecord());
        }
    }
}
