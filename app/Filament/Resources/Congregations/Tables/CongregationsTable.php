<?php

namespace App\Filament\Resources\Congregations\Tables;

use App\Models\Congregation;
use App\Services\ContactImportService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CongregationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Denumire')
                    ->searchable(),
                TextColumn::make('assistant_name')
                    ->label('Persoană de contact')
                    ->visible(fn (): bool => self::isAdmin())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('assistant_phone')
                    ->label('Telefon')
                    ->visible(fn (): bool => self::isAdmin()),
                TextColumn::make('assistant_email')
                    ->label('Email')
                    ->visible(fn (): bool => self::isAdmin())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Creat la')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizat la')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('editPhone')
                    ->label('Editează telefonul')
                    ->icon('heroicon-o-phone')
                    ->visible(fn (): bool => self::isAdmin())
                    ->form([
                        TextInput::make('assistant_phone')
                            ->label('Telefon')
                            ->maxLength(50)
                            ->required(),
                    ])
                    ->action(function (Congregation $record, array $data): void {
                        $record->assistant_phone = (string) $data['assistant_phone'];
                        $record->save();

                        Notification::make()->title('Telefonul a fost salvat.')->success()->send();
                    }),
            ])
            ->toolbarActions([
                Action::make('importContacts')
                    ->label('Importă contacte')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(fn (): bool => self::isAdmin())
                    ->steps([
                        Step::make('Încarcă CSV')
                            ->schema([
                                FileUpload::make('csv')
                                    ->label('Fișier CSV')
                                    ->disk('local')
                                    ->directory('contact-imports')
                                    ->acceptedFileTypes(['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])
                                    ->maxSize(2048)
                                    ->required()
                                    ->helperText('Coloane: nume congregație, persoană, telefon, email.'),
                            ]),
                        Step::make('Verifică')
                            ->schema([
                                Checkbox::make('confirm')
                                    ->label(fn (Get $get): string => 'Confirm salvarea: '.app(ContactImportService::class)->summary(
                                        app(ContactImportService::class)->parse(self::readCsv($get('csv'))),
                                    ))
                                    ->helperText('Fără bifă, importul rămâne doar o previzualizare (dry-run).'),
                            ]),
                    ])
                    ->action(function (array $data): void {
                        $service = app(ContactImportService::class);
                        $preview = $service->parse(self::readCsv($data['csv'] ?? null));

                        if (! ($data['confirm'] ?? false)) {
                            Notification::make()
                                ->title('Dry-run: nimic nu a fost salvat.')
                                ->body($service->summary($preview))
                                ->warning()
                                ->send();

                            return;
                        }

                        $saved = $service->apply($preview);

                        Notification::make()
                            ->title($saved.' contacte salvate.')
                            ->success()
                            ->send();
                    }),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function isAdmin(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    private static function readCsv(mixed $state): string
    {
        if (is_array($state)) {
            $state = reset($state);
        }

        if (! is_string($state) || $state === '') {
            return '';
        }

        try {
            return (string) (Storage::disk('local')->get($state) ?? '');
        } catch (Throwable) {
            return '';
        }
    }
}
