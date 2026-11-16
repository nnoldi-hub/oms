<x-filament-panels::page>
    <div style="display:grid;gap:14px;">
        @if ($this->requests->isEmpty())
            <div style="border:1px dashed #aebcb5;border-radius:8px;color:#66756e;padding:32px;text-align:center;">
                Nu exista cereri de schimbare.
            </div>
        @else
            @foreach ($this->requests as $request)
                <article wire:key="week-change-request-{{ $request->id }}" style="background:#fff;border:1px solid #d8e0dc;border-radius:8px;padding:16px;display:grid;gap:8px;">
                    <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:space-between;align-items:baseline;">
                        <strong style="font-size:15px;">
                            Saptamana {{ $request->week?->week_number ?? '-' }} · {{ $request->week?->periodLabel() ?? '-' }}
                        </strong>
                        <span style="font-size:12px;font-weight:700;text-transform:uppercase;color:{{ $request->status === 'pending' ? '#b45309' : ($request->status === 'approved' ? '#047857' : '#be123c') }};">
                            {{ $request->statusLabel() }}
                        </span>
                    </div>
                    <p style="font-size:13px;margin:0;">
                        Congregatie: <strong>{{ $request->congregation?->name ?? '-' }}</strong>
                        · cerut de {{ $request->user?->name ?? '-' }}
                        · {{ $request->created_at?->translatedFormat('d M Y H:i') }}
                    </p>
                    @if ($request->preferredCongregation || $request->preferredWeek)
                        <p style="font-size:13px;margin:0;color:#166534;">
                            Propunere:
                            @if ($request->preferredCongregation) mutare la {{ $request->preferredCongregation->name }} @endif
                            @if ($request->preferredWeek) schimb cu saptamana {{ $request->preferredWeek->week_number }} @endif
                        </p>
                    @endif
                    <p style="font-size:14px;line-height:1.5;margin:0;"><strong>Motiv:</strong> {{ $request->reason }}</p>
                    @if ($request->resolution_note)
                        <p style="font-size:13px;margin:0;color:#66756e;">Nota: {{ $request->resolution_note }}@if ($request->resolvedBy) · {{ $request->resolvedBy->name }}@endif</p>
                    @endif

                    @if ($this->isAdmin() && $request->status === 'pending')
                        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:4px;">
                            <input type="text" wire:model="resolutionNote" placeholder="Nota (obligatorie la respingere)" style="border:1px solid #b9c8bf;border-radius:6px;flex:1 1 260px;min-height:42px;padding:8px 11px;">
                            <button type="button" wire:click="approve({{ $request->id }})" style="background:#047857;border:0;border-radius:6px;color:#fff;cursor:pointer;font-weight:700;min-height:42px;padding:9px 16px;">Aproba</button>
                            <button type="button" wire:click="openReject({{ $request->id }})" style="background:#fff;border:1px solid #f0a6b4;border-radius:6px;color:#be123c;cursor:pointer;font-weight:700;min-height:42px;padding:9px 16px;">Respinge</button>
                        </div>
                    @endif
                </article>
            @endforeach
        @endif

        @if ($rejectingId !== null)
            <div style="background:rgba(22,35,30,.55);display:flex;inset:0;justify-content:center;overflow-y:auto;padding:24px 16px;position:fixed;z-index:60;">
                <div style="background:#fff;border-radius:10px;display:grid;gap:14px;max-width:460px;padding:22px;width:100%;">
                    <h2 style="font-size:19px;font-weight:750;margin:0;">Respinge cererea</h2>
                    <label style="color:#66756e;font-size:12px;font-weight:700;text-transform:uppercase;">Motivul respingerii</label>
                    <textarea wire:model="resolutionNote" rows="4" style="border:1px solid #b9c8bf;border-radius:6px;padding:9px 11px;"></textarea>
                    @error('resolutionNote')<p style="color:#be123c;font-size:13px;margin:0;">{{ $message }}</p>@enderror
                    <div style="display:flex;gap:10px;justify-content:flex-end;">
                        <button type="button" wire:click="cancelReject" style="background:#fff;border:1px solid #d8e0dc;border-radius:6px;cursor:pointer;font-weight:700;min-height:44px;padding:9px 15px;">Renunta</button>
                        <button type="button" wire:click="reject" style="background:#be123c;border:0;border-radius:6px;color:#fff;cursor:pointer;font-weight:700;min-height:44px;padding:9px 15px;">Respinge</button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
