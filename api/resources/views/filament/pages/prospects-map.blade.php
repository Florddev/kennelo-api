<x-filament-panels::page>
    <link href="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.css" rel="stylesheet" />
    <script src="https://unpkg.com/maplibre-gl@4.7.1/dist/maplibre-gl.js"></script>

    <x-filament::section>
        {{ $this->form }}
    </x-filament::section>

    <div
        wire:ignore
        x-data="prospectsMap()"
        @prospects-updated.window="updateData($event.detail.geojson ?? $event.detail[0]?.geojson)"
        class="mt-4 overflow-hidden rounded-xl border border-gray-200 dark:border-white/10"
    >
        <div x-ref="map" style="height: 70vh; width: 100%;"></div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('prospectsMap', () => ({
                map: null,

                init() {
                    const geojson = @js($this->getGeoJson());

                    if (typeof maplibregl === 'undefined') {
                        console.error('MapLibre GL not loaded');
                        return;
                    }

                    this.map = new maplibregl.Map({
                        container: this.$refs.map,
                        style: 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json',
                        center: [2.4, 46.6],
                        zoom: 5,
                    });

                    this.map.addControl(new maplibregl.NavigationControl(), 'top-right');

                    this.map.on('load', () => {
                        this.map.addSource('prospects', {
                            type: 'geojson',
                            data: geojson,
                            cluster: true,
                            clusterMaxZoom: 12,
                            clusterRadius: 50,
                        });

                        this.map.addLayer({
                            id: 'clusters',
                            type: 'circle',
                            source: 'prospects',
                            filter: ['has', 'point_count'],
                            paint: {
                                'circle-color': '#059669',
                                'circle-radius': ['step', ['get', 'point_count'], 16, 10, 22, 50, 30],
                                'circle-opacity': 0.85,
                            },
                        });

                        this.map.addLayer({
                            id: 'cluster-count',
                            type: 'symbol',
                            source: 'prospects',
                            filter: ['has', 'point_count'],
                            layout: {
                                'text-field': '{point_count_abbreviated}',
                                'text-size': 12,
                            },
                            paint: { 'text-color': '#ffffff' },
                        });

                        this.map.addLayer({
                            id: 'unclustered-point',
                            type: 'circle',
                            source: 'prospects',
                            filter: ['!', ['has', 'point_count']],
                            paint: {
                                'circle-color': ['case', ['get', 'is_registered'], '#0284c7', '#059669'],
                                'circle-radius': 7,
                                'circle-stroke-width': 2,
                                'circle-stroke-color': '#ffffff',
                            },
                        });

                        this.map.on('click', 'clusters', (e) => {
                            const features = this.map.queryRenderedFeatures(e.point, { layers: ['clusters'] });
                            const clusterId = features[0].properties.cluster_id;
                            this.map.getSource('prospects').getClusterExpansionZoom(clusterId).then((zoom) => {
                                this.map.easeTo({ center: features[0].geometry.coordinates, zoom });
                            });
                        });

                        this.map.on('click', 'unclustered-point', (e) => {
                            const esc = (value) => String(value ?? '').replace(/[&<>"']/g, (c) => ({
                                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
                            }[c]));
                            const p = e.features[0].properties;
                            const coords = e.features[0].geometry.coordinates.slice();
                            const html =
                                '<strong>' + esc(p.name) + '</strong><br>' +
                                esc(p.address) + ' ' + esc(p.city) + '<br>' +
                                (p.phone ? 'Tél : ' + esc(p.phone) + '<br>' : '') +
                                (p.google_rating ? '★ ' + esc(p.google_rating) + '<br>' : '') +
                                (p.is_registered ? '<span style="color:#0284c7">Inscrit sur Kennelo</span>' : 'Non inscrit');
                            new maplibregl.Popup().setLngLat(coords).setHTML(html).addTo(this.map);
                        });

                        ['clusters', 'unclustered-point'].forEach((layer) => {
                            this.map.on('mouseenter', layer, () => { this.map.getCanvas().style.cursor = 'pointer'; });
                            this.map.on('mouseleave', layer, () => { this.map.getCanvas().style.cursor = ''; });
                        });
                    });
                },

                updateData(geojson) {
                    const source = this.map && this.map.getSource('prospects');
                    if (source) {
                        source.setData(geojson);
                    }
                },
            }));
        });
    </script>
</x-filament-panels::page>
