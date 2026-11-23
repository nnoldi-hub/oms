<x-filament-panels::page>
    <div style="display:grid;gap:16px;">
        <div style="background:#fff;border:1px solid #d8e0dc;border-radius:8px;padding:16px;display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));align-items:end;">
            <label style="display:grid;gap:4px;color:#66756e;font-size:12px;font-weight:700;text-transform:uppercase;">
                Săptămâna
                <select wire:model.live="weekFilter" style="border:1px solid #b9c8bf;border-radius:6px;min-height:42px;padding:8px 10px;background:#fff;">
                    @foreach ($this->weekOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div style="color:#66756e;font-size:13px;">
                <strong>{{ count($this->plan['days']) }}</strong> zile ·
                <strong>{{ $this->plan['contributions'] }}</strong> contribuții ·
                <strong>{{ $this->plan['uncontacted'] }}</strong> congregații necontactate
            </div>
        </div>

        @if ($this->plan['warnings'] !== [])
            <div class="oms-support-warnings" style="background:#fff;border:1px solid #f1d9a7;border-left:4px solid #d97706;border-radius:8px;padding:16px;">
                <h2 style="font-size:14px;font-weight:800;margin:0 0 10px;color:#92400e;">Avertismente — doar informare, nu blochează nimic</h2>
                <ul style="display:grid;gap:6px;margin:0;padding-left:18px;">
                    @foreach ($this->plan['warnings'] as $warning)
                        <li wire:key="support-warning-{{ $loop->index }}" style="color:#78350f;font-size:13px;">
                            {{ $warning['message'] }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section style="background:#fff;border:1px solid #d8e0dc;border-radius:8px;padding:16px;display:grid;gap:10px;">
            <header style="display:flex;flex-wrap:wrap;gap:8px;align-items:baseline;justify-content:space-between;">
                <h2 style="font-size:14px;font-weight:800;margin:0;">Donații nealocate (fără zi)</h2>
                <span style="color:#66756e;font-size:12px;">{{ count($this->plan['unallocated']) }} înregistrări</span>
            </header>

            @forelse ($this->plan['unallocated'] as $entry)
                @php($donation = $entry['donation'])
                <div wire:key="support-unallocated-{{ $donation->id }}" style="display:grid;gap:8px;border:1px solid #e3eae6;border-radius:6px;padding:10px;background:#f8faf9;font-size:13px;">
                    <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                        <strong>{{ $entry['congregation']?->name ?? '-' }}</strong>
                        <span>{{ $donation->kindLabel() }}</span>
                        @if ($entry['description'] !== '') <span>· {{ $entry['description'] }}</span>@endif
                        @if ($entry['quantity'] !== '') <span>· {{ $entry['quantity'] }}{{ $entry['unit'] }}</span>@endif
                        @if ($entry['date'] !== '') <span style="color:#66756e;">· {{ $entry['date'] }}</span>@endif
                        @if ($entry['storage'] !== null) <span style="color:#66756e;">· Păstrare: {{ $entry['storage'] }}</span>@endif
                        @if ($entry['delivery_until'] !== null) <span style="color:#66756e;">· Livrare până: {{ $entry['delivery_until'] }}</span>@endif
                    </div>

                    @if ($this->allocationDonationId === $donation->id)
                        <div style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
                            @if ($this->plan['allocation_days'] === [])
                                <span style="color:#92400e;font-size:12px;">Nu există zile în filtrul ales.</span>
                            @else
                                <select wire:model="allocationMealId" style="border:1px solid #b9c8bf;border-radius:6px;min-height:36px;padding:4px 8px;font-size:13px;">
                                    @foreach ($this->plan['allocation_days'] as $dayOption)
                                        <option value="{{ $dayOption['id'] }}">{{ $dayOption['label'] }}</option>
                                    @endforeach
                                </select>
                                <button type="button" wire:click="allocateDonation" style="background:#047857;border:1px solid #047857;border-radius:6px;cursor:pointer;color:#fff;font-size:12px;font-weight:700;min-height:36px;padding:4px 12px;">Salvează ziua</button>
                            @endif
                            <button type="button" wire:click="closeAllocate" style="background:#fff;border:1px solid #d8e0dc;border-radius:6px;cursor:pointer;font-size:12px;font-weight:700;min-height:36px;padding:4px 12px;">Anulează</button>
                        </div>
                    @else
                        <button type="button" wire:click="openAllocate({{ $donation->id }})" style="background:#fff;border:1px solid #a7f3d0;border-radius:6px;cursor:pointer;font-size:12px;font-weight:700;min-height:34px;padding:4px 10px;color:#065f46;justify-self:start;">Alocă unei zile</button>
                    @endif
                </div>
            @empty
                <p style="color:#9aa69f;font-size:13px;margin:0;">Nu există donații fără zi.</p>
            @endforelse
        </section>

        <div class="table-wrap" style="background:#fff;border:1px solid #d8e0dc;border-radius:8px;overflow-x:auto;">
            <table style="border-collapse:collapse;min-width:640px;width:100%;">
                <thead>
                    <tr style="background:#f4f7f5;text-align:left;">
                        <th style="font-size:12px;letter-spacing:.04em;padding:10px 12px;text-transform:uppercase;">Categorie</th>
                        <th style="font-size:12px;letter-spacing:.04em;padding:10px 12px;text-transform:uppercase;">Cine aduce / cât</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->plan['categories'] as $category)
                        <tr wire:key="support-category-{{ $category['key'] }}" style="border-top:1px solid #eef2f0;">
                            <td style="font-size:13px;font-weight:700;padding:10px 12px;vertical-align:top;white-space:nowrap;">{{ $category['label'] }}</td>
                            <td style="font-size:13px;padding:10px 12px;">
                                @if ($category['entries'] === [])
                                    <span style="color:#9aa69f;">—</span>
                                @else
                                    <div style="display:flex;flex-wrap:wrap;gap:6px;">
                                        @foreach ($category['entries'] as $entry)
                                            <span wire:key="support-entry-{{ $entry['donation']->id }}" style="background:#f0f7f3;border:1px solid #d3e5da;border-radius:999px;padding:4px 9px;">
                                                {{ $entry['congregation']?->name ?? '-' }}
                                                @if ($entry['description'] !== '')
                                                    · {{ $entry['description'] }}
                                                @endif
                                                @if ($entry['quantity'] !== '')
                                                    · {{ $entry['quantity'] }}{{ $entry['unit'] }}
                                                @endif
                                                @if (! $entry['promised'])
                                                    · primit
                                                @endif
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($this->plan['days'] === [])
            <div style="border:1px dashed #aebcb5;border-radius:8px;color:#66756e;padding:32px;text-align:center;">
                Nu există zile programate pentru filtrul ales.
            </div>
        @endif

        @foreach ($this->plan['days'] as $day)
            <section wire:key="support-day-{{ $day['meal']->id }}" style="background:#fff;border:1px solid #d8e0dc;border-radius:8px;padding:16px;display:grid;gap:12px;">
                <header style="display:flex;flex-wrap:wrap;gap:8px;align-items:baseline;justify-content:space-between;border-bottom:1px solid #eef2f0;padding-bottom:10px;">
                    <h2 style="font-size:15px;font-weight:800;margin:0;">{{ $day['date_label'] }}</h2>
                    <span style="font-size:13px;color:#047857;">Gătește: <strong>{{ $day['cooking']?->name ?? '—' }}</strong></span>
                </header>

                <div class="oms-support-grid" style="display:grid;gap:12px;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));">
                    @foreach ($day['contributors'] as $contributor)
                        @php($congregation = $contributor['congregation'])
                        @php($noteKey = $day['meal']->id.'_'.$congregation->id)
                        <article wire:key="support-contributor-{{ $day['meal']->id }}-{{ $congregation->id }}" style="border:1px solid #e3eae6;border-radius:8px;padding:12px;display:grid;gap:10px;">
                            <div style="display:grid;gap:2px;">
                                <strong style="font-size:14px;">{{ $congregation->name }}</strong>
                                <span style="font-size:12px;color:#66756e;">
                                    {{ $congregation->assistant_name ?: 'Fără persoană de contact' }}
                                    @if ($congregation->assistant_phone)
                                        · <a href="tel:{{ preg_replace('/[^0-9+]/', '', $congregation->assistant_phone) }}" style="color:#047857;text-decoration:none;">{{ $congregation->assistant_phone }}</a>
                                    @endif
                                    @if ($congregation->assistant_email)
                                        · {{ $congregation->assistant_email }}
                                    @endif
                                </span>
                            </div>

                            <div style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;">
                                <label style="color:#66756e;font-size:12px;font-weight:700;">Contact</label>
                                <select wire:change="setContactStatus({{ $day['meal']->id }}, {{ $congregation->id }}, $event.target.value)" style="border:1px solid #b9c8bf;border-radius:6px;min-height:36px;padding:4px 8px;font-size:13px;">
                                    @foreach ($this->contactStatusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($contributor['contact_status'] === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @if ($contributor['contact_status'] !== 'contactat')
                                    <button type="button" wire:click="markContacted({{ $day['meal']->id }}, {{ $congregation->id }})" style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:6px;cursor:pointer;font-size:12px;font-weight:700;min-height:36px;padding:4px 10px;color:#065f46;">Marchează contactată</button>
                                @endif
                            </div>

                            <div style="display:grid;gap:4px;">
                                <label style="color:#66756e;font-size:12px;font-weight:700;" for="support-note-{{ $noteKey }}">Notițe</label>
                                <textarea id="support-note-{{ $noteKey }}" wire:model.blur="contactNotes.{{ $noteKey }}" rows="2" maxlength="500" style="border:1px solid #b9c8bf;border-radius:6px;font-size:13px;padding:8px;resize:vertical;">{{ $contributor['contact_notes'] ?? '' }}</textarea>
                            </div>

                            <div style="display:grid;gap:6px;">
                                <span style="color:#66756e;font-size:12px;font-weight:700;">Ce a promis / adus</span>
                                @forelse ($contributor['donations'] as $donation)
                                    <div wire:key="support-donation-{{ $donation->id }}" style="display:flex;flex-wrap:wrap;gap:6px;align-items:center;background:#f8faf9;border-radius:6px;padding:6px 8px;font-size:12px;">
                                        <span style="font-weight:700;">{{ $donation->kindLabel() }}</span>
                                        <span>{{ $donation->description }}</span>
                                        @if ($donation->quantity !== null) <span>{{ rtrim(rtrim(number_format((float) $donation->quantity, 2, '.', ''), '0'), '.') }} {{ $donation->unit }}</span>@endif
                                        <span style="color:#66756e;">{{ $donation->statusLabel() }}</span>
                                        @if ($donation->storageLabel() !== null) <span style="color:#66756e;">Păstrare: {{ $donation->storageLabel() }}</span>@endif
                                        @if ($donation->deliveryUntilLabel() !== null) <span style="color:#66756e;">Livrare până: {{ $donation->deliveryUntilLabel() }}</span>@endif
                                        @if ($donation->status === \App\Models\Donation::STATUS_PROMISED)
                                            <button type="button" wire:click="markReceived({{ $donation->id }})" style="background:#fff;border:1px solid #a7f3d0;border-radius:999px;cursor:pointer;font-size:11px;font-weight:700;padding:2px 8px;color:#065f46;">Marchează primit</button>
                                        @endif
                                    </div>
                                @empty
                                    <span style="color:#9aa69f;font-size:12px;">Încă nimic trecut.</span>
                                @endforelse
                            </div>

                            @if ($addingMealId === $day['meal']->id && $addingCongregationId === $congregation->id)
                                <div style="border-top:1px solid #e3eae6;padding-top:10px;display:grid;gap:8px;">
                                    <label style="display:grid;gap:4px;color:#66756e;font-size:12px;font-weight:700;">
                                        Tip
                                        <select wire:model="newKind" style="border:1px solid #b9c8bf;border-radius:6px;min-height:38px;padding:6px 8px;font-size:13px;">
                                            @foreach ($this->kindOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    @if ($this->newKind === \App\Models\Donation::KIND_FOOD)
                                        <label style="display:grid;gap:4px;color:#66756e;font-size:12px;font-weight:700;">
                                            Ingredient din catalog (opțional)
                                            <select wire:model.live="newIngredientId" style="border:1px solid #b9c8bf;border-radius:6px;min-height:38px;padding:6px 8px;font-size:13px;">
                                                <option value="">— fără ingredient din catalog —</option>
                                                @foreach ($this->ingredientOptions as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        @if ($this->ingredientCoverage !== null)
                                            <div style="background:#f0f7f3;border:1px solid #d3e5da;border-radius:6px;color:#065f46;font-size:12px;font-weight:600;padding:7px 9px;">
                                                {{ $this->ingredientCoverage['label'] }}
                                            </div>
                                        @endif
                                    @endif
                                    <label style="display:grid;gap:4px;color:#66756e;font-size:12px;font-weight:700;">
                                        Descriere
                                        <input type="text" wire:model="newDescription" maxlength="255" style="border:1px solid #b9c8bf;border-radius:6px;min-height:38px;padding:6px 8px;font-size:13px;" placeholder="ex. banane">
                                    </label>
                                    <div style="display:grid;gap:8px;grid-template-columns:repeat(2,minmax(0,1fr));">
                                        <label style="display:grid;gap:4px;color:#66756e;font-size:12px;font-weight:700;">
                                            Cantitate
                                            <input type="number" step="0.01" min="0" wire:model.live="newQuantity" style="border:1px solid #b9c8bf;border-radius:6px;min-height:38px;padding:6px 8px;font-size:13px;">
                                        </label>
                                        <label style="display:grid;gap:4px;color:#66756e;font-size:12px;font-weight:700;">
                                            Unitate
                                            <input type="text" wire:model="newUnit" maxlength="20" style="border:1px solid #b9c8bf;border-radius:6px;min-height:38px;padding:6px 8px;font-size:13px;" placeholder="kg, bucăți…">
                                        </label>
                                    </div>
                                    <div style="display:grid;gap:8px;grid-template-columns:repeat(2,minmax(0,1fr));">
                                        <label style="display:grid;gap:4px;color:#66756e;font-size:12px;font-weight:700;">
                                            Păstrare
                                            <select wire:model="newStorage" style="border:1px solid #b9c8bf;border-radius:6px;min-height:38px;padding:6px 8px;font-size:13px;">
                                                <option value="">—</option>
                                                @foreach ($this->storageOptions as $value => $label)
                                                    <option value="{{ $value }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label style="display:grid;gap:4px;color:#66756e;font-size:12px;font-weight:700;">
                                            Livrare până la
                                            <input type="datetime-local" wire:model.live="newDeliveryUntil" style="border:1px solid #b9c8bf;border-radius:6px;min-height:38px;padding:6px 8px;font-size:13px;">
                                        </label>
                                    </div>
                                    @if ($this->offerWarning !== null)
                                        <div style="background:#fffbeb;border:1px solid #f1d9a7;border-radius:6px;color:#92400e;font-size:12px;font-weight:600;padding:7px 9px;">
                                            {{ $this->offerWarning }}
                                        </div>
                                    @endif
                                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                        <button type="button" wire:click="saveContribution" style="background:#047857;border:1px solid #047857;border-radius:6px;cursor:pointer;color:#fff;font-size:12px;font-weight:700;min-height:38px;padding:6px 12px;">Salvează ca promisă</button>
                                        <button type="button" wire:click="closeAddContribution" style="background:#fff;border:1px solid #d8e0dc;border-radius:6px;cursor:pointer;font-size:12px;font-weight:700;min-height:38px;padding:6px 12px;">Anulează</button>
                                    </div>
                                </div>
                            @else
                                <button type="button" wire:click="openAddContribution({{ $day['meal']->id }}, {{ $congregation->id }})" style="background:#fff;border:1px solid #a7f3d0;border-radius:6px;cursor:pointer;font-size:12px;font-weight:700;min-height:38px;padding:6px 12px;color:#065f46;">+ Adaugă ce aduc</button>
                            @endif
                        </article>
                    @endforeach

                    @if ($day['contributors'] === [])
                        <div style="border:1px dashed #aebcb5;border-radius:8px;color:#66756e;font-size:13px;padding:16px;grid-column:1/-1;">
                            Această zi nu are congregații contribuitoare înregistrate.
                        </div>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    @push('styles')
        <style>
            @media (max-width: 640px) {
                .oms-support-grid { grid-template-columns: 1fr !important; }
                .oms-support-warnings ul { padding-left: 16px !important; }
            }
        </style>
    @endpush
</x-filament-panels::page>
