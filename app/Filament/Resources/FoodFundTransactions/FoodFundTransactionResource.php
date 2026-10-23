<?php

namespace App\Filament\Resources\FoodFundTransactions;

use App\Models\FoodFundTransaction;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FoodFundTransactionResource extends Resource
{
    protected static ?string $model = FoodFundTransaction::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;
    protected static ?string $navigationLabel = 'Fonduri alimente';
    protected static string|\UnitEnum|null $navigationGroup = 'Aprovizionare';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'operatiune financiara';
    protected static ?string $pluralModelLabel = 'operatiuni financiare';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return ($user?->canManageCongregationFunds() ?? false) || ($user?->isProjectSupervisor() ?? false);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->canManageCongregationFunds() ?? false;
    }

    public static function canEdit($record): bool
    {
        return self::canManageRecord($record);
    }

    public static function canDelete($record): bool
    {
        return self::canManageRecord($record);
    }

    private static function canManageRecord(FoodFundTransaction $record): bool
    {
        $user = auth()->user();

        if (! ($user?->canManageCongregationFunds() ?? false)) {
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
            Select::make('type')->label('Tip operatiune')->options([
                'income' => 'Incasare fonduri',
                'expense' => 'Cheltuiala / suma oferita',
            ])->required(),
            Select::make('congregation_id')
                ->label('Congregatie')
                ->relationship(
                    'congregation',
                    'name',
                    fn (Builder $query): Builder => auth()->user()?->isScopedToCongregation()
                        ? $query->whereKey(auth()->user()->congregation_id)
                        : $query,
                )
                ->default(fn (): ?int => auth()->user()?->isScopedToCongregation() ? auth()->user()->congregation_id : null)
                ->disabled(fn (): bool => auth()->user()?->isScopedToCongregation() ?? false)
                ->dehydrated()
                ->helperText('Congregatia care a primit sau a cheltuit suma.')
                ->searchable()
                ->preload(),
            DatePicker::make('transaction_date')->label('Data')->default(today())->required(),
            TextInput::make('amount')->label('Suma')->numeric()->minValue(0.01)->prefix('RON')->required(),
            Select::make('category')->label('Categorie')->options([
                'donation' => 'Donatie / fonduri primite',
                'food' => 'Alimente',
                'water' => 'Apa',
                'snacks' => 'Gustari / deserturi',
                'transport' => 'Transport',
                'other' => 'Altele',
            ])->searchable(),
            TextInput::make('counterparty')->label('De la / catre')->maxLength(160)->helperText('Persoana, congregatia sau magazinul'),
            Select::make('payment_method')->label('Metoda de plata')->options([
                'cash' => 'Numerar',
                'transfer' => 'Transfer bancar',
                'card' => 'Card',
            ]),
            TextInput::make('reference')->label('Bon / referinta')->maxLength(120),
            Textarea::make('description')->label('Detalii')->required()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('transaction_date')->label('Data')->date()->sortable(),
            TextColumn::make('type')->label('Tip')->badge()->formatStateUsing(fn (string $state): string => $state === 'income' ? 'Incasare' : 'Cheltuiala')->color(fn (string $state): string => $state === 'income' ? 'success' : 'danger'),
            TextColumn::make('amount')->label('Suma')->money('RON')->sortable(),
            TextColumn::make('congregation.name')->label('Congregatie')->placeholder('-')->sortable(),
            TextColumn::make('category')->label('Categorie')->badge(),
            TextColumn::make('counterparty')->label('De la / catre')->searchable(),
            TextColumn::make('description')->label('Detalii')->limit(45)->searchable(),
            TextColumn::make('recordedBy.name')->label('Inregistrat de'),
        ])->defaultSort('transaction_date', 'desc')->recordActions([
            EditAction::make(),
            DeleteAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFoodFundTransactions::route('/'),
            'create' => Pages\CreateFoodFundTransaction::route('/create'),
            'edit' => Pages\EditFoodFundTransaction::route('/{record}/edit'),
        ];
    }
}
