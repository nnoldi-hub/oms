<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class GhidUtilizare extends Page
{
    protected string $view = 'filament.pages.ghid-utilizare';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?string $navigationLabel = 'Ghid de utilizare';

    protected static string|\UnitEnum|null $navigationGroup = 'Ajutor';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Ghid de utilizare';
}
