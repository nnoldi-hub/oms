<?php

namespace App\Filament\Resources\Donations;

use App\Filament\Resources\Donations\Pages\CreateDonation;
use App\Filament\Resources\Donations\Pages\EditDonation;
use App\Filament\Resources\Donations\Pages\ListDonations;
use App\Models\Congregation;
use App\Models\DailyMeal;
use App\Models\Donation;
use App\Support\CongregationAccess;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DonationResource extends Resource
{
    protected static ?string $model = Donation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static ?string $navigationLabel = 'Donații';

    protected static string|\UnitEnum|null $navigationGroup = 'Aprovizionare';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'donație';

    protected static ?string $pluralModelLabel = 'donații';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->canManageContributions() ?? false) || ($user?->isProjectSupervisor() ?? false);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canManageContributions() ?? false;
    }

    public static function canEdit($record): bool
    {
        return self::canManageRecord($record);
    }

    public static function canDelete($record): bool
    {
        return self::canManageRecord($record);
    }

    private static function canManageRecord(Donation $record): bool
    {
        $user = auth()->user();

        if (! ($user?->canManageContributions() ?? false)) {
            return false;
        }

        return ! $user->isScopedToCongregation() || (int) $record->congregation_id === (int) $user->congregation_id;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->visibleTo(auth()->user());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')->label('Stare')->options([
                Donation::STATUS_PROMISED => 'Promisă',
                Donation::STATUS_RECEIVED => 'Primită',
            ])->default(Donation::STATUS_PROMISED)->required(),
            DatePicker::make('donation_date')->label('Data')->default(today())->required(),
            Select::make('congregation_id')
                ->label('Congregatie')
                ->options(fn (): array => Congregation::query()->orderBy('name')->pluck('name', 'id')->all())
                ->default(fn (): ?int => auth()->user()?->isScopedToCongregation() ? auth()->user()->congregation_id : null)
                ->disabled(fn (): bool => auth()->user()?->isScopedToCongregation() ?? false)
                ->dehydrated()
                ->searchable()
                ->preload(),
            Select::make('daily_meal_id')
                ->label('Ziua de masa (optional)')
                ->options(function (): array {
                    $user = auth()->user();
                    $query = DailyMeal::query();

                    if ($user?->isScopedToCongregation()) {
                        CongregationAccess::scopeMeals($query, $user);
                    }

                    return $query->orderBy('meal_date')->get()
                        ->mapWithKeys(fn (DailyMeal $meal): array => [$meal->id => $meal->meal_date->translatedFormat('d M Y')])
                        ->all();
                })
                ->searchable()
                ->preload()
                ->nullable(),
            Select::make('food_type')->label('Tip aliment')->options([
                'legume' => 'Legume',
                'fructe' => 'Fructe',
                'carne' => 'Carne',
                'gustari_dulci' => 'Gustări dulci',
                'gustari_sarate' => 'Gustări sărate',
                'altele' => 'Altele',
            ])->nullable()->searchable(),
            TextInput::make('description')->label('Descriere')->maxLength(160),
            TextInput::make('quantity')->label('Cantitate')->numeric()->minValue(0),
            TextInput::make('unit')->label('Unitate')->maxLength(20)->placeholder('kg, buc, cutie'),
            TextInput::make('estimated_value')->label('Valoare estimată')->numeric()->minValue(0)->prefix('RON')->default(0)->required(),
            TextInput::make('donor_name')->label('Donator')->maxLength(160),
            TextInput::make('donor_phone')->label('Telefon donator')->tel()->maxLength(40),
            Textarea::make('notes')->label('Observații')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('donation_date')->label('Data')->date()->sortable(),
            TextColumn::make('status')->label('Stare')->badge()->formatStateUsing(fn (Donation $record): string => $record->statusLabel()),
            TextColumn::make('estimated_value')->label('Valoare')->money('RON')->sortable(),
            TextColumn::make('food_type')->label('Tip')->placeholder('-')->sortable(),
            TextColumn::make('description')->label('Descriere')->limit(40)->searchable(),
            TextColumn::make('quantity')->label('Cantitate')->placeholder('-'),
            TextColumn::make('unit')->label('Unitate')->placeholder('-'),
            TextColumn::make('congregation.name')->label('Congregatie')->placeholder('-')->sortable(),
            TextColumn::make('donor_name')->label('Donator')->placeholder('-')->searchable(),
        ])->defaultSort('donation_date', 'desc')->recordActions([
            EditAction::make(),
            DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDonations::route('/'),
            'create' => CreateDonation::route('/create'),
            'edit' => EditDonation::route('/{record}/edit'),
        ];
    }
}
