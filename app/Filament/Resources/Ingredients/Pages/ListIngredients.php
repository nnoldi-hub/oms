<?php

namespace App\Filament\Resources\Ingredients\Pages;

use App\Filament\Resources\Ingredients\IngredientResource;
use App\Models\Ingredient;
use App\Services\IngredientMerger;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use InvalidArgumentException;

class ListIngredients extends ListRecords
{
    protected static string $resource = IngredientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('mergeIngredients')
                ->label('Unește ingrediente')
                ->icon('heroicon-o-arrows-pointing-in')
                ->color('gray')
                ->modalDescription('Toate rețetele de pe ingredientul duplicat trec pe cel păstrat. Duplicatul este dezactivat, nu șters.')
                ->schema([
                    Select::make('duplicate_id')
                        ->label('Ingredient duplicat (se dezactivează)')
                        ->options(fn (): array => self::ingredientOptions())
                        ->searchable()
                        ->required(),
                    Select::make('keep_id')
                        ->label('Ingredient păstrat')
                        ->options(fn (): array => self::ingredientOptions())
                        ->searchable()
                        ->required()
                        ->different('duplicate_id'),
                ])
                ->requiresConfirmation()
                ->action(function (array $data): void {
                    try {
                        $stats = app(IngredientMerger::class)->merge(
                            Ingredient::findOrFail($data['duplicate_id']),
                            Ingredient::findOrFail($data['keep_id']),
                        );
                    } catch (InvalidArgumentException $e) {
                        Notification::make()
                            ->title('Unirea nu s-a făcut')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Ingrediente unite')
                        ->body("Rețete modificate: {$stats['menus']}, linii mutate: {$stats['lines']}.")
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function ingredientOptions(): array
    {
        return Ingredient::query()
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Ingredient $ingredient): array => [
                $ingredient->id => "{$ingredient->name} ({$ingredient->unit})",
            ])
            ->all();
    }
}