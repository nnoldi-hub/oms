<x-filament-widgets::widget>
    @include('filament.partials.weekly-schedule-summary', [
        'weeks' => $this->weeks,
        'selectedWeekId' => null,
    ])
</x-filament-widgets::widget>
