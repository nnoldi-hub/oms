<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Schimbari recente</x-slot>
        <x-slot name="description">Ultimele modificari de plan facute de coordonatori.</x-slot>

        @if ($this->changes->isEmpty())
            <p style="color:#66756e;font-size:14px;margin:0;">Nu exista schimbari inregistrate.</p>
        @else
            <div style="display:grid;gap:10px;">
                @foreach ($this->changes as $change)
                    <div wire:key="meal-change-{{ $change->id }}" style="border-bottom:1px solid #eef2f0;padding-bottom:10px;">
                        <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:space-between;align-items:baseline;">
                            <strong style="font-size:14px;">{{ $change->eventLabel() }}</strong>
                            <span style="color:#8a988f;font-size:12px;">{{ $change->created_at?->translatedFormat('d M Y H:i') }}</span>
                        </div>
                        <p style="font-size:13px;line-height:1.5;margin:2px 0 0;">
                            {{ $change->summary() }}
                            @if ($change->congregation)
                                · {{ $change->congregation->name }}
                            @endif
                            @if ($change->user)
                                · {{ $change->user->name }}
                            @endif
                        </p>
                        @if (!empty($change->warnings))
                            <p style="color:#b45309;font-size:12px;margin:4px 0 0;">Avertismente: {{ implode(' ', $change->warnings) }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
