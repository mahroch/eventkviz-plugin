(function ($) {
    'use strict';

    if (typeof ekMapquizCfg === 'undefined') return;

    const cfg = ekMapquizCfg;
    let map = null;
    let tileLayer = null;
    let pins = [];                  // array of pin objects (see backend schema)
    let pinMarkers = {};            // pin.id -> L.Marker
    let currentPinId = null;        // selected pin id (or null)

    const $pinsJson = $('#ekm-pins-json');
    const $tiersJson = $('#ekm-tiers-json');
    const $region = $('#ekm-region');
    const $playerDetail = $('#ekm-player-detail');

    function uuid() {
        if (typeof crypto !== 'undefined' && crypto.randomUUID) return crypto.randomUUID();
        // RFC4122-ish fallback
        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
            const r = Math.random() * 16 | 0;
            return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
    }

    function loadPins() {
        try {
            pins = JSON.parse($pinsJson.val() || '[]');
            if (!Array.isArray(pins)) pins = [];
        } catch (e) { pins = []; }
    }

    function savePins() {
        $pinsJson.val(JSON.stringify(pins));
    }

    function loadTiers() {
        try { return JSON.parse($tiersJson.val() || '[]') || []; }
        catch (e) { return []; }
    }

    function saveTiers(tiers) {
        $tiersJson.val(JSON.stringify(tiers));
    }

    function initMap() {
        const regionKey = $region.val() || 'slovakia';
        const region = cfg.regions[regionKey] || cfg.regions.slovakia;

        map = L.map('ekm-map', { worldCopyJump: true }).setView(region.center, region.zoom);

        applyTileLayer();

        map.on('click', function (e) {
            // Add new pin at click location
            const p = {
                id: uuid(),
                name: '',
                hint: '',
                description: '',
                photo_id: 0,
                photo_url: '',
                lat: e.latlng.lat,
                lon: e.latlng.lng,
            };
            pins.push(p);
            savePins();
            renderPinMarker(p);
            renderPinList();
            selectPin(p.id);
        });

        // Initial markers for existing pins
        pins.forEach(renderPinMarker);
        renderPinList();
        bindPinEditor();
        renderTiers();
        bindTierEditor();

        $region.on('change', function () {
            const r = cfg.regions[$(this).val()] || cfg.regions.slovakia;
            map.setView(r.center, r.zoom);
        });
    }

    function applyTileLayer() {
        if (tileLayer) {
            map.removeLayer(tileLayer);
            tileLayer = null;
        }
        if (!cfg.maptilerKey) {
            // No key → just a blank background so admin still sees the map area
            map.getContainer().style.background = '#f5f5f5';
            return;
        }
        tileLayer = L.tileLayer(
            'https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=' + encodeURIComponent(cfg.maptilerKey),
            {
                attribution: '&copy; MapTiler &copy; OpenStreetMap contributors',
                maxZoom: 18,
                crossOrigin: true,
            }
        ).addTo(map);
    }

    function renderPinMarker(pin) {
        const marker = L.marker([pin.lat, pin.lon], { draggable: true, title: pin.name || '(bez názvu)' })
            .addTo(map);

        marker.bindTooltip(pin.name || '(bez názvu)', { permanent: false, direction: 'top' });

        marker.on('click', function () { selectPin(pin.id); });
        marker.on('dragend', function () {
            const ll = marker.getLatLng();
            const p = findPin(pin.id);
            if (p) {
                p.lat = ll.lat;
                p.lon = ll.lng;
                savePins();
                if (currentPinId === pin.id) refreshPinEditor();
            }
        });

        pinMarkers[pin.id] = marker;
    }

    function findPin(id) {
        return pins.find(p => p.id === id);
    }

    function renderPinList() {
        const $list = $('#ekm-pin-list').empty();
        if (pins.length === 0) {
            $list.append('<li class="ekm-pin-empty">' + cfg.i18n.noPins + '</li>');
            return;
        }
        pins.forEach((p, idx) => {
            const $li = $('<li class="ekm-pin-row"></li>')
                .toggleClass('is-selected', p.id === currentPinId)
                .attr('data-pin-id', p.id);
            $li.append('<span class="ekm-pin-num">#' + (idx + 1) + '</span>');
            $li.append('<span class="ekm-pin-name">' + escapeHtml(p.name || '(bez názvu)') + '</span>');
            $li.append('<span class="ekm-pin-coords">' + p.lat.toFixed(4) + ', ' + p.lon.toFixed(4) + '</span>');
            $li.on('click', () => selectPin(p.id));
            $list.append($li);
        });
    }

    function selectPin(id) {
        currentPinId = id;
        const pin = findPin(id);
        if (!pin) return;

        $('.ekm-no-pin-hint').hide();
        $('.ekm-pin-fields').show();

        $('#ekm-pin-name').val(pin.name);
        $('#ekm-pin-hint').val(pin.hint);
        $('#ekm-pin-description').val(pin.description);
        refreshPinEditor();
        renderPhotoPreview(pin);
        renderPinList(); // update is-selected
    }

    function refreshPinEditor() {
        const pin = findPin(currentPinId);
        if (!pin) return;
        $('#ekm-pin-lat-display').text(pin.lat.toFixed(5));
        $('#ekm-pin-lon-display').text(pin.lon.toFixed(5));
    }

    function renderPhotoPreview(pin) {
        const $preview = $('#ekm-pin-photo-preview').empty();
        const $remove  = $('#ekm-pin-photo-remove');
        if (pin.photo_id && pin.photo_url) {
            $preview.append('<img src="' + pin.photo_url + '" alt="" style="max-height:60px;border-radius:4px;vertical-align:middle;margin-right:8px" />');
            $('#ekm-pin-photo-pick').text(cfg.i18n.photoChange);
            $remove.show();
        } else if (pin.photo_id) {
            // We have ID but no URL (loaded from saved data) — fetch URL
            wp.media.attachment(pin.photo_id).fetch().then(function (att) {
                pin.photo_url = att.url;
                savePins();
                renderPhotoPreview(pin);
            });
        } else {
            $('#ekm-pin-photo-pick').text(cfg.i18n.photoSelect);
            $remove.hide();
        }
    }

    function bindPinEditor() {
        $('#ekm-pin-name').on('input', function () {
            const pin = findPin(currentPinId);
            if (!pin) return;
            pin.name = $(this).val();
            savePins();
            renderPinList();
            // Update marker tooltip
            const m = pinMarkers[pin.id];
            if (m) {
                m.options.title = pin.name;
                if (m.getTooltip()) m.setTooltipContent(pin.name || '(bez názvu)');
            }
        });
        $('#ekm-pin-hint').on('input', function () {
            const pin = findPin(currentPinId);
            if (pin) { pin.hint = $(this).val(); savePins(); }
        });
        $('#ekm-pin-description').on('input', function () {
            const pin = findPin(currentPinId);
            if (pin) { pin.description = $(this).val(); savePins(); }
        });

        $('#ekm-pin-photo-pick').on('click', function (e) {
            e.preventDefault();
            const pin = findPin(currentPinId);
            if (!pin) return;
            const frame = wp.media({
                title: cfg.i18n.photoModalTitle,
                button: { text: cfg.i18n.photoModalBtn },
                multiple: false,
                library: { type: 'image' },
            });
            frame.on('select', function () {
                const att = frame.state().get('selection').first().toJSON();
                pin.photo_id = att.id;
                pin.photo_url = att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url;
                savePins();
                renderPhotoPreview(pin);
            });
            frame.open();
        });

        $('#ekm-pin-photo-remove').on('click', function (e) {
            e.preventDefault();
            const pin = findPin(currentPinId);
            if (!pin) return;
            pin.photo_id = 0;
            pin.photo_url = '';
            savePins();
            renderPhotoPreview(pin);
        });

        $('#ekm-pin-delete').on('click', function (e) {
            e.preventDefault();
            const pin = findPin(currentPinId);
            if (!pin) return;
            if (!confirm('Naozaj odstrániť pin „' + (pin.name || '(bez názvu)') + '"?')) return;
            // Remove from map + array
            const m = pinMarkers[pin.id];
            if (m) {
                map.removeLayer(m);
                delete pinMarkers[pin.id];
            }
            pins = pins.filter(p => p.id !== pin.id);
            savePins();
            currentPinId = null;
            $('.ekm-pin-fields').hide();
            $('.ekm-no-pin-hint').show();
            renderPinList();
        });
    }

    function renderTiers() {
        const tiers = loadTiers();
        const $tbody = $('#ekm-tiers-tbody').empty();
        if (tiers.length === 0) {
            $tbody.append('<tr><td colspan="3"><em>Žiadne stupne — pridaj prvý.</em></td></tr>');
            return;
        }
        tiers.forEach((t, idx) => {
            const $tr = $('<tr></tr>').attr('data-idx', idx);
            $tr.append('<td>do <input type="number" class="ekm-tier-km small-text" value="' + t.maxKm + '" min="0" step="0.5" /> km</td>');
            $tr.append('<td><input type="number" class="ekm-tier-percent small-text" value="' + t.percent + '" min="0" max="100" step="1" /> %</td>');
            $tr.append('<td><button type="button" class="button-link-delete ekm-tier-del">✕</button></td>');
            $tbody.append($tr);
        });
    }

    function persistTiersFromUI() {
        const tiers = [];
        $('#ekm-tiers-tbody tr').each(function () {
            const km = parseFloat($(this).find('.ekm-tier-km').val());
            const pct = parseFloat($(this).find('.ekm-tier-percent').val());
            if (!isNaN(km) && !isNaN(pct)) tiers.push({ maxKm: km, percent: pct });
        });
        // sort by km ascending
        tiers.sort((a, b) => a.maxKm - b.maxKm);
        saveTiers(tiers);
    }

    function bindTierEditor() {
        $('#ekm-tier-add').on('click', function () {
            const tiers = loadTiers();
            const last = tiers.length ? tiers[tiers.length - 1] : { maxKm: 0, percent: 100 };
            tiers.push({ maxKm: last.maxKm + 10, percent: Math.max(0, last.percent - 25) });
            saveTiers(tiers);
            renderTiers();
        });
        $('#ekm-tiers-tbody').on('input', '.ekm-tier-km, .ekm-tier-percent', persistTiersFromUI);
        $('#ekm-tiers-tbody').on('click', '.ekm-tier-del', function () {
            const idx = $(this).closest('tr').data('idx');
            const tiers = loadTiers();
            tiers.splice(idx, 1);
            saveTiers(tiers);
            renderTiers();
        });
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    // ============================================================
    // DRAW EDITOR — pre area/line custom features (Leaflet.draw)
    // ============================================================

    const $customFeaturesJson = $('#ekm-custom-features-json');
    // Per-mode state (admin môže zmeniť quiz_type medzi area/line bez reloadu,
    // ale vykreslené features sú per template — zdieľame ich medzi modes).
    const drawMaps = {}; // 'area' | 'line' → { map, drawnItems, drawControl }

    function loadCustomFeatures() {
        try {
            const v = $customFeaturesJson.val() || '';
            if (!v) return { type: 'FeatureCollection', features: [] };
            const d = JSON.parse(v);
            if (!d || d.type !== 'FeatureCollection' || !Array.isArray(d.features)) {
                return { type: 'FeatureCollection', features: [] };
            }
            return d;
        } catch (e) {
            return { type: 'FeatureCollection', features: [] };
        }
    }

    function saveCustomFeatures(fc) {
        $customFeaturesJson.val(JSON.stringify(fc));
    }

    function initDrawMap(mode) {
        const elId = 'ekm-draw-map-' + mode;
        const el = document.getElementById(elId);
        if (!el || drawMaps[mode]) return;

        const regionKey = $region.val() || 'slovakia';
        const region = cfg.regions[regionKey] || cfg.regions.slovakia;

        const dmap = L.map(elId, { worldCopyJump: true }).setView(region.center, region.zoom);

        // Tile layer (rovnako ako hlavná mapa)
        if (cfg.maptilerKey) {
            L.tileLayer(
                'https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=' + encodeURIComponent(cfg.maptilerKey),
                { attribution: '&copy; MapTiler &copy; OpenStreetMap contributors', maxZoom: 18, crossOrigin: true }
            ).addTo(dmap);
        } else {
            el.style.background = '#f5f5f5';
        }

        // FeatureGroup pre nakreslené tvary
        const drawnItems = new L.FeatureGroup().addTo(dmap);

        // Leaflet.draw control — povolíme len mode-relevantný shape
        const drawCtl = new L.Control.Draw({
            position: 'topleft',
            draw: {
                polygon:    mode === 'area' ? { showArea: false, shapeOptions: { color: '#7cb342', weight: 2, fillOpacity: 0.5 } } : false,
                polyline:   mode === 'line' ? { shapeOptions: { color: '#3aa6f0', weight: 4 } } : false,
                rectangle:  false,
                circle:     false,
                marker:     false,
                circlemarker: false,
            },
            edit: { featureGroup: drawnItems, remove: true }
        });
        dmap.addControl(drawCtl);

        drawMaps[mode] = { map: dmap, drawnItems: drawnItems, mode: mode };

        // Load existujúce custom features (filtruj len kompatibilné s mode)
        renderCustomFeaturesOnDrawMap(mode);

        // Handle CREATE
        dmap.on(L.Draw.Event.CREATED, function (e) {
            const layer = e.layer;
            const name = window.prompt('Názov tejto ' + (mode === 'area' ? 'oblasti' : 'línie') + ':', '');
            if (!name || !name.trim()) {
                // Bez názvu — neuložíme.
                return;
            }
            layer.feature = {
                type: 'Feature',
                properties: { name: name.trim() },
                geometry: layer.toGeoJSON().geometry,
            };
            drawnItems.addLayer(layer);
            bindFeatureTooltip(layer);
            persistDrawnFeatures(mode);
            renderCustomFeaturesList(mode);
        });

        // Handle EDIT (drag vertexov)
        dmap.on(L.Draw.Event.EDITED, function (e) {
            e.layers.eachLayer(function (l) {
                if (l.feature) l.feature.geometry = l.toGeoJSON().geometry;
            });
            persistDrawnFeatures(mode);
        });

        // Handle DELETE
        dmap.on(L.Draw.Event.DELETED, function () {
            persistDrawnFeatures(mode);
            renderCustomFeaturesList(mode);
        });

        // Resize observer — kontajner môže byť 0×0 keď nie je active mode
        if (typeof ResizeObserver !== 'undefined') {
            const ro = new ResizeObserver(function () { dmap.invalidateSize(false); });
            ro.observe(el);
        }
    }

    function bindFeatureTooltip(layer) {
        if (!layer.feature || !layer.feature.properties || !layer.feature.properties.name) return;
        layer.bindTooltip(layer.feature.properties.name, { permanent: false, direction: 'top', sticky: true });
    }

    function renderCustomFeaturesOnDrawMap(mode) {
        const dm = drawMaps[mode];
        if (!dm) return;
        const fc = loadCustomFeatures();
        const expectedTypes = mode === 'area' ? ['Polygon', 'MultiPolygon'] : ['LineString', 'MultiLineString'];
        fc.features.forEach(function (feat) {
            if (!feat.geometry || expectedTypes.indexOf(feat.geometry.type) === -1) return;
            const layer = L.geoJSON(feat, {
                style: mode === 'area'
                    ? { color: '#7cb342', weight: 2, fillOpacity: 0.5 }
                    : { color: '#3aa6f0', weight: 4 }
            });
            // L.geoJSON vracia featureGroup; jeho deti pridáme do drawnItems
            layer.eachLayer(function (l) {
                l.feature = feat;
                dm.drawnItems.addLayer(l);
                bindFeatureTooltip(l);
            });
        });
        renderCustomFeaturesList(mode);
    }

    function persistDrawnFeatures(mode) {
        // Existing features iného modu treba zachovať (admin môže mať aj area aj
        // line nakreslené, ale aktuálne quiz_type rozhoduje ktorý sa použije).
        const fc = loadCustomFeatures();
        const otherTypes = mode === 'area' ? ['LineString', 'MultiLineString'] : ['Polygon', 'MultiPolygon'];
        const preserved = fc.features.filter(function (f) {
            return f.geometry && otherTypes.indexOf(f.geometry.type) !== -1;
        });

        // Aktuálne mode features z drawnItems
        const dm = drawMaps[mode];
        const current = [];
        if (dm) {
            dm.drawnItems.eachLayer(function (l) {
                if (!l.feature) return;
                current.push({
                    type: 'Feature',
                    properties: l.feature.properties,
                    geometry: l.toGeoJSON().geometry,
                });
            });
        }

        saveCustomFeatures({ type: 'FeatureCollection', features: preserved.concat(current) });
    }

    function renderCustomFeaturesList(mode) {
        const $list = $('.ekm-draw-list[data-mode="' + mode + '"]').empty();
        const $count = $('.ekm-draw-count[data-mode="' + mode + '"]');
        const dm = drawMaps[mode];
        if (!dm) { $count.text(0); return; }
        let i = 0;
        dm.drawnItems.eachLayer(function (layer) {
            i++;
            const name = (layer.feature && layer.feature.properties && layer.feature.properties.name) || '(bez názvu)';
            const $li = $('<li></li>').css({
                padding: '6px 10px', borderBottom: '1px solid #eee',
                display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px'
            });
            $li.append($('<span></span>').text(i + '. ' + name).css({ cursor: 'pointer', flex: '1' }).on('click', function () {
                // Fokus na feature
                if (layer.getBounds) dm.map.fitBounds(layer.getBounds(), { padding: [20, 20] });
                else if (layer.getLatLng) dm.map.panTo(layer.getLatLng());
            }));
            const $rename = $('<button type="button" class="button button-small" title="Premenovať">✎</button>').on('click', function () {
                const newName = window.prompt('Nový názov:', name === '(bez názvu)' ? '' : name);
                if (newName && newName.trim()) {
                    layer.feature.properties.name = newName.trim();
                    layer.unbindTooltip();
                    bindFeatureTooltip(layer);
                    persistDrawnFeatures(mode);
                    renderCustomFeaturesList(mode);
                }
            });
            const $del = $('<button type="button" class="button button-small" title="Vymazať">✕</button>').css({ color: '#a00' }).on('click', function () {
                if (window.confirm('Vymazať feature „' + name + '"?')) {
                    dm.drawnItems.removeLayer(layer);
                    persistDrawnFeatures(mode);
                    renderCustomFeaturesList(mode);
                }
            });
            $li.append($rename).append($del);
            $list.append($li);
        });
        $count.text(i);
    }

    function bindSourceRadios() {
        $('.ekm-source-radio').on('change', function () {
            const source = $(this).val();
            const mode = $(this).data('mode');
            // Pre quiz_type mode toggle visibility bundle vs custom section
            $('.ekm-source-bundle').toggle(source === 'bundle');
            $('.ekm-source-custom').toggle(source === 'custom');

            if (source === 'custom') {
                // Lazy init draw map keď admin prvý raz prepol na custom
                const activeMode = $('#ekm-quiz-type').val();
                if (activeMode === 'area' || activeMode === 'line') {
                    initDrawMap(activeMode);
                    // Trigger resize aby Leaflet rozpoznal kontajner
                    setTimeout(function () {
                        if (drawMaps[activeMode]) drawMaps[activeMode].map.invalidateSize();
                    }, 100);
                }
            }
        });

        // Pri zmene quiz_type prepoj draw mapu na nový mode
        $('#ekm-quiz-type').on('change', function () {
            const qt = $(this).val();
            if ((qt === 'area' || qt === 'line') && $('.ekm-source-radio:checked').val() === 'custom') {
                initDrawMap(qt);
                setTimeout(function () {
                    if (drawMaps[qt]) drawMaps[qt].map.invalidateSize();
                }, 100);
            }
        });
    }

    $(function () {
        if (document.getElementById('ekm-map')) {
            loadPins();
            initMap();
        }
        if (document.getElementById('ekm-custom-features-json')) {
            bindSourceRadios();
            // Ak admin uložil v custom mode, init draw mapy pre aktuálny quiz_type
            const activeMode = $('#ekm-quiz-type').val();
            const activeSource = $('input.ekm-source-radio:checked').val() || 'bundle';
            if (activeSource === 'custom' && (activeMode === 'area' || activeMode === 'line')) {
                initDrawMap(activeMode);
                setTimeout(function () {
                    if (drawMaps[activeMode]) drawMaps[activeMode].map.invalidateSize();
                }, 200);
            }
        }
    });

})(jQuery);
