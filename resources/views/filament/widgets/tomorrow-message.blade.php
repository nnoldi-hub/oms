<div>
    @if ($this->message !== null)
        <x-filament-widgets::widget>
            <x-filament::section>
                <x-slot name="heading">Mesajul de maine</x-slot>
                <x-slot name="description">Copiaza mesajul pentru grupul de WhatsApp.</x-slot>

                <style>
                    .oms-tomorrow-actions { display: flex; flex-wrap: wrap; gap: 8px; }
                    .oms-tomorrow-actions .fi-btn { min-height: 44px; }
                    @media (max-width: 640px) {
                        .oms-tomorrow-actions { display: grid; grid-template-columns: 1fr; }
                        .oms-tomorrow-actions > * { justify-content: center; width: 100%; }
                    }
                </style>

                <div style="display:grid;gap:12px;">
                    <pre style="background:rgba(0,0,0,.04);border-radius:8px;font-family:inherit;font-size:14px;line-height:1.55;margin:0;padding:12px;white-space:pre-wrap;">{{ $this->message }}</pre>
                    <div class="oms-tomorrow-actions">
                        <x-filament::button
                            color="gray"
                            icon="heroicon-o-clipboard-document"
                            x-data
                            x-on:click="navigator.clipboard.writeText(@js($this->message))"
                        >Copiaza mesajul</x-filament::button>
                        <x-filament::button
                            tag="a"
                            color="success"
                            icon="heroicon-o-chat-bubble-left-right"
                            href="https://wa.me/?text={{ rawurlencode($this->message) }}"
                            target="_blank"
                            rel="noopener"
                        >Trimite pe WhatsApp</x-filament::button>
                    </div>
                </div>
            </x-filament::section>
        </x-filament-widgets::widget>
    @endif
</div>
