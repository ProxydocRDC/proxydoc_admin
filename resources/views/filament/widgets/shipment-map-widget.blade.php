<x-filament-widgets::widget>
<div class="space-y-6">
        {{ $this->form??"" }}

        <x-filament::section>
            <x-slot name="heading">Suivi sur carte</x-slot>

            @php
                $points = $this->events;
                $mapId  = 'shipment-map-' . (int) ($this->shipmentId ?? 0);
            @endphp

            <div
                wire:key="map-wrapper-{{ (int) ($this->shipmentId ?? 0) }}"
                x-data="{ points: @js($points), mapId: '{{ $mapId }}' }"
                x-init="window.renderShipmentMap(mapId, points)"
            >
                <div id="{{ $mapId }}" style="height:380px" class="rounded-lg overflow-hidden" wire:ignore></div>
            </div>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>

@once
    @push('styles')
        <link rel="stylesheet"
              href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @endpush

    @push('scripts')
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            window.renderShipmentMap = function (mapId, points) {
                const el = document.getElementById(mapId);
                if (!el) return;

                const map = L.map(mapId);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                if (!points || !points.length) {
                    // centrage par défaut (Kinshasa)
                    map.setView([-4.3250, 15.3222], 12);
                    return;
                }

                const layer = L.layerGroup().addTo(map);
                const latlngs = [];

                points.forEach((p) => {
                    const ll = [p.lat, p.lng];
                    latlngs.push(ll);

                    const colors = {
                        created: '#6b7280',
                        picked_up: '#06b6d4',
                        in_transit: '#06b6d4',
                        arrived_hub: '#06b6d4',
                        out_for_delivery: '#06b6d4',
                        delivered: '#10b981',
                        delivery_failed: '#ef4444',
                        exception: '#ef4444',
                        returned_to_sender: '#f59e0b',
                        returned: '#f59e0b',
                        canceled: '#f59e0b',
                    };
                    const color = colors[p.type] ?? '#9ca3af';

                    const marker = L.circleMarker(ll, { radius: 6, color, fillColor: color, fillOpacity: 0.9 }).addTo(layer);
                    marker.bindPopup(
                        `<div class='text-sm'>
                            <b>${(p.type || '').replaceAll('_',' ')}</b><br>
                            ${p.time ?? ''}<br>
                            ${p.remarks ?? ''}
                         </div>`
                    );
                });

                L.polyline(latlngs, { weight: 3 }).addTo(layer);
                map.fitBounds(L.latLngBounds(latlngs).pad(0.2));
            };
        </script>
    @endpush
@endonce
