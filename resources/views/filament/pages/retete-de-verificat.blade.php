<x-filament-panels::page>
    <div style="display:grid;gap:16px;">
        <p style="color:#66756e;font-size:14px;margin:0;">
            Retetele create de coordonatori apar aici pana cand sunt verificate. Le poti aproba sau uni cu o reteta existenta.
        </p>

        @if ($this->pendingMenus->isEmpty())
            <div style="border:1px dashed #aebcb5;border-radius:8px;color:#66756e;padding:32px;text-align:center;">
                Nu exista retete in asteptare.
            </div>
        @else
            <div style="display:grid;gap:12px;">
                @foreach ($this->pendingMenus as $menu)
                    <article wire:key="pending-menu-{{ $menu->id }}" style="background:#fff;border:1px solid #d8e0dc;border-radius:8px;padding:16px;">
                        <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:space-between;align-items:center;">
                            <div>
                                <h2 style="font-size:18px;font-weight:750;margin:0;">{{ $menu->name }}</h2>
                                <p style="color:#66756e;font-size:13px;margin:4px 0 0;">
                                    {{ ['main' => 'Fel principal', 'soup' => 'Ciorba', 'dessert' => 'Desert / gustare'][$menu->type] ?? $menu->type }}
                                    · {{ $menu->creatorCongregation?->name ?? 'congregatie necunoscuta' }}
                                    · creat la {{ $menu->created_at?->translatedFormat('d M Y H:i') }}
                                </p>
                            </div>
                            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                <button class="fi-btn" type="button" wire:click="approve({{ $menu->id }})" style="background:#047857;color:#fff;border:0;border-radius:6px;cursor:pointer;font-weight:700;min-height:40px;padding:8px 14px;">Aproba</button>
                                <button class="fi-btn" type="button" wire:click="openMerge({{ $menu->id }})" style="background:#fff;border:1px solid #91b4a2;color:#166534;border-radius:6px;cursor:pointer;font-weight:700;min-height:40px;padding:8px 14px;">Uneste</button>
                            </div>
                        </div>

                        @if (filled($menu->instructions))
                            <p style="font-size:14px;line-height:1.5;margin:10px 0 0;">{{ $menu->instructions }}</p>
                        @endif

                        @if (!empty($menu->ingredients))
                            <ul style="margin:10px 0 0 18px;padding:0;font-size:14px;">
                                @foreach ($menu->ingredients as $ingredient)
                                    <li>{{ $ingredient['name'] ?? '-' }} — {{ $ingredient['quantity_per_person'] ?? '-' }} {{ $ingredient['unit'] ?? '' }}/persoana</li>
                                @endforeach
                            </ul>
                        @elseif ($menu->cost_per_portion !== null)
                            <p style="font-size:14px;margin:10px 0 0;">Cost estimat per portie: {{ number_format((float) $menu->cost_per_portion, 2) }} RON.</p>
                        @endif

                        @if (!empty($menu->allergens))
                            <p style="font-size:13px;color:#b45309;margin:10px 0 0;">Alergeni: {{ implode(', ', $menu->allergens) }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
        @endif

        @if ($mergeSourceId !== null)
            <div style="background:rgba(22,35,30,.55);display:flex;inset:0;justify-content:center;overflow-y:auto;padding:24px 16px;position:fixed;z-index:60;">
                <div style="background:#fff;border-radius:10px;display:flex;flex-direction:column;gap:14px;max-width:460px;padding:22px;width:100%;">
                    <h2 style="font-size:19px;font-weight:750;margin:0;">Uneste reteta</h2>
                    <p style="color:#66756e;font-size:13px;line-height:1.5;margin:0;">Alege reteta existenta cu care vrei sa unesti. Zilele care folosesc reteta propusa vor folosi reteta aleasa, iar reteta propusa se sterge.</p>
                    <label style="color:#66756e;font-size:12px;font-weight:700;text-transform:uppercase;">Reteta existenta</label>
                    <select wire:model="mergeTargetId" style="border:1px solid #b9c8bf;border-radius:6px;min-height:44px;padding:8px 11px;">
                        <option value="">- alege -</option>
                        @foreach ($this->mergeTargetOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('mergeTargetId')<p style="color:#be123c;font-size:13px;margin:0;">{{ $message }}</p>@enderror
                    <div style="display:flex;gap:10px;justify-content:flex-end;">
                        <button type="button" wire:click="closeMerge" style="background:#fff;border:1px solid #d8e0dc;border-radius:6px;cursor:pointer;font-weight:700;min-height:44px;padding:9px 15px;">Renunta</button>
                        <button type="button" wire:click="merge" style="background:#047857;border:0;border-radius:6px;color:#fff;cursor:pointer;font-weight:700;min-height:44px;padding:9px 15px;">Uneste</button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
