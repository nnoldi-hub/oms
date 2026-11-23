<x-filament-widgets::widget>
    @include('filament.partials.weekly-schedule-summary', [
        'weeks' => $this->weeks,
        'selectedWeekId' => null,
        'hint' => 'Toate săptămânile · zilele se calculează din program',
    ])
</x-filament-widgets::widget>
