<x-filament-panels::page>
    <div style="display:grid;gap:16px;">
        <div style="background:#fff;border:1px solid #d8e0dc;border-radius:8px;padding:16px;display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
            <label style="display:grid;gap:4px;color:#66756e;font-size:12px;font-weight:700;text-transform:uppercase;">
                Congregatie
                <select wire:model.live="congregationId" style="border:1px solid #b9c8bf;border-radius:6px;min-height:42px;padding:8px 10px;">
                    <option value="">Toate</option>
                    @foreach ($this->congregationOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </label>
            <label style="display:grid;gap:4px;color:#66756e;font-size:12px;font-weight:700;text-transform:uppercase;">
                Tip
                <select wire:model.live="eventType" style="border:1px solid #b9c8bf;border-radius:6px;min-height:42px;padding:8px 10px;">
                    <option value="">Toate</option>
                    @foreach ($this->eventTypeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label style="display:grid;gap:4px;color:#66756e;font-size:12px;font-weight:700;text-transform:uppercase;">
                De la
                <input type="date" wire:model.live="dateFrom" style="border:1px solid #b9c8bf;border-radius:6px;min-height:42px;padding:8px 10px;">
            </label>
            <label style="display:grid;gap:4px;color:#66756e;font-size:12px;font-weight:700;text-transform:uppercase;">
                Pana la
                <input type="date" wire:model.live="dateTo" style="border:1px solid #b9c8bf;border-radius:6px;min-height:42px;padding:8px 10px;">
            </label>
            <div style="align-self:end;">
                <button type="button" wire:click="resetFilters" style="background:#fff;border:1px solid #d8e0dc;border-radius:6px;cursor:pointer;font-weight:700;min-height:42px;padding:8px 14px;">Reseteaza filtrele</button>
            </div>
        </div>

        @if ($this->logs->isEmpty())
            <div style="border:1px dashed #aebcb5;border-radius:8px;color:#66756e;padding:32px;text-align:center;">
                Nu exista inregistrari pentru filtrele alese.
            </div>
        @else
            <div style="background:#fff;border:1px solid #d8e0dc;border-radius:8px;overflow-x:auto;">
                <table style="border-collapse:collapse;min-width:760px;width:100%;">
                    <thead>
                        <tr style="background:#f4f7f5;text-align:left;">
                            <th style="font-size:12px;letter-spacing:.04em;padding:10px 12px;text-transform:uppercase;">Data</th>
                            <th style="font-size:12px;letter-spacing:.04em;padding:10px 12px;text-transform:uppercase;">Congregatie</th>
                            <th style="font-size:12px;letter-spacing:.04em;padding:10px 12px;text-transform:uppercase;">Eveniment</th>
                            <th style="font-size:12px;letter-spacing:.04em;padding:10px 12px;text-transform:uppercase;">Detalii</th>
                            <th style="font-size:12px;letter-spacing:.04em;padding:10px 12px;text-transform:uppercase;">Utilizator</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->logs as $log)
                            <tr wire:key="activity-{{ $log->id }}" style="border-top:1px solid #eef2f0;">
                                <td style="font-size:13px;padding:10px 12px;white-space:nowrap;">{{ $log->created_at?->translatedFormat('d M Y H:i') }}</td>
                                <td style="font-size:13px;padding:10px 12px;">{{ $log->congregation?->name ?? '-' }}</td>
                                <td style="font-size:13px;padding:10px 12px;">{{ $log->eventLabel() }}</td>
                                <td style="font-size:13px;padding:10px 12px;">
                                    {{ $log->summary() }}
                                    @if ($log->meal_date)
                                        <span style="color:#8a988f;">· ziua {{ $log->meal_date->translatedFormat('d M Y') }}</span>
                                    @endif
                                    @if (!empty($log->warnings))
                                        <div style="color:#b45309;font-size:12px;">Avertismente: {{ implode(' ', $log->warnings) }}</div>
                                    @endif
                                </td>
                                <td style="font-size:13px;padding:10px 12px;">{{ $log->user?->name ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</x-filament-panels::page>
