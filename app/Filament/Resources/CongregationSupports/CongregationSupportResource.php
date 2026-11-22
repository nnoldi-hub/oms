<?php

namespace App\Filament\Resources\CongregationSupports;

use App\Filament\Resources\CongregationSupports\Pages\ListCongregationSupports;
use App\Models\Congregation;
use App\Models\Donation;
use App\Services\CongregationSupportReportService;
use App\Support\CongregationRole;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CongregationSupportResource extends Resource
{
    protected static ?string $model = Congregation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?string $navigationLabel = 'Sprijin pe congregatii';

    protected static string|\UnitEnum|null $navigationGroup = 'Aprovizionare';

    protected static ?int $navigationSort = 6;

    protected static ?string $modelLabel = 'sprijin';

    protected static ?string $pluralModelLabel = 'sprijin pe congregatii';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return self::canAccess();
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('support_notes')
                ->label('Note despre sprijin')
                ->columnSpanFull()
                ->rows(4),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Congregatie')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === CongregationRole::COOKS ? 'Gateste' : 'Contribuie')
                    ->sortable(),
                TextColumn::make('contribution_days')
                    ->label('Zile contributie')
                    ->getStateUsing(fn (Congregation $record): int => app(CongregationSupportReportService::class)->summary($record)['contribution_days'])
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('cooking_days')
                    ->label('Zile gatit')
                    ->getStateUsing(fn (Congregation $record): int => app(CongregationSupportReportService::class)->summary($record)['cooking_days'])
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('donations_count')
                    ->label('Donatii')
                    ->getStateUsing(fn (Congregation $record): int => app(CongregationSupportReportService::class)->summary($record)['donations_count'])
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('donated_amount')
                    ->label('Valoare donatii')
                    ->getStateUsing(fn (Congregation $record): float => app(CongregationSupportReportService::class)->summary($record)['donated_amount'])
                    ->money('RON')
                    ->alignEnd(),
                TextColumn::make('supply_count')
                    ->label('Consumabile')
                    ->getStateUsing(fn (Congregation $record): int => app(CongregationSupportReportService::class)->summary($record)['supply_count'])
                    ->numeric()
                    ->alignEnd(),
                TextColumn::make('support_notes')
                    ->label('Note')
                    ->limit(60)
                    ->placeholder('-'),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Rol')
                    ->options([
                        CongregationRole::COOKS => 'Gateste',
                        CongregationRole::CONTRIBUTES => 'Contribuie',
                    ]),
                Filter::make('only_with_support')
                    ->label('Doar cu sprijin inregistrat')
                    ->checkbox()
                    ->query(function (Builder $query, array $data): Builder {
                        if (! ($data['isActive'] ?? false)) {
                            return $query;
                        }

                        return $query->where(function (Builder $query): void {
                            $query->whereHas('contributingDailyMeals')
                                ->orWhereHas('dailyMeals')
                                ->orWhereHas('donations')
                                ->orWhereHas('donorDonations')
                                ->orWhereHas('supplyContributions');
                        });
                    }),
            ])
            ->recordActions([
                Action::make('quickAdd')
                    ->label('Adauga sprijin')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Select::make('kind')
                            ->label('Felul donației')
                            ->options([
                                Donation::KIND_FOOD => 'Alimente',
                                Donation::KIND_MONEY => 'Bani',
                                Donation::KIND_OTHER => 'Altele',
                            ])
                            ->default(Donation::KIND_FOOD)
                            ->required(),
                        Select::make('status')
                            ->label('Stare')
                            ->options([
                                Donation::STATUS_PROMISED => 'Promisă',
                                Donation::STATUS_RECEIVED => 'Primită',
                                Donation::STATUS_USED => 'Folosită',
                                Donation::STATUS_CANCELLED => 'Anulată',
                            ])
                            ->default(Donation::STATUS_RECEIVED)
                            ->required(),
                        DatePicker::make('date')
                            ->label('Data')
                            ->default(today())
                            ->required(),
                        TextInput::make('description')
                            ->label('Descriere / ingredient')
                            ->maxLength(160),
                        TextInput::make('quantity')
                            ->label('Cantitate')
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('unit')
                            ->label('Unitate')
                            ->maxLength(20),
                        TextInput::make('amount_ron')
                            ->label('Valoare (RON)')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(),
                    ])
                    ->action(function (Congregation $record, array $data): void {
                        Donation::create([
                            ...$data,
                            'congregation_id' => $record->id,
                            'donor_congregation_id' => $record->id,
                        ]);
                    })
                    ->successNotificationTitle('Sprijinul a fost inregistrat.'),
                EditAction::make()->label('Note'),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCongregationSupports::route('/'),
        ];
    }
}
