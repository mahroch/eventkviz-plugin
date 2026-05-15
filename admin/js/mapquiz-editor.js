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

    $(function () {
        if (!document.getElementById('ekm-map')) return;
        loadPins();
        initMap();
    });

})(jQuery);
