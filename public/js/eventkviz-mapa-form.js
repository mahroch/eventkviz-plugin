(function ($) {
    'use strict';

    var $container = $('#ek-mapa-container');
    if ($container.length === 0) return;

    // Two modes: form (interactive) and review (read-only, post-eval).
    // window.ekMapaTasks  → form
    // window.ekMapaReview → review (also sets data-review="1")
    var isReview = $container.data('review') === 1 || $container.data('review') === '1';
    var tasks = isReview ? (window.ekMapaReview || []) : (window.ekMapaTasks || []);
    if (!tasks || tasks.length === 0) return;

    var region = $container.data('region') || 'slovakia';
    var detail = $container.data('detail') || 'outline-only';
    var map = null;
    var taskMarkers = {};   // taskIdx → L.Marker (form mode: guess pin)
    var correctMarkers = {}; // review mode only: green correct pin
    var currentTaskIdx = 0;

    // Region presets — center + zoom + bounds for fitBounds
    // GeoJSON outlines are minimal placeholder polygons; replaced with real
    // shapes via fetch from /public/data/regions/<region>.geojson if available.
    var REGION_PRESETS = {
        slovakia: { center: [48.7, 19.5], zoom: 7,  bounds: [[47.7, 16.8], [49.6, 22.6]] },
        czechia:  { center: [49.8, 15.5], zoom: 7,  bounds: [[48.5, 12.0], [51.1, 19.0]] },
        europe:   { center: [50.0, 15.0], zoom: 4,  bounds: [[35.0, -10.0], [70.0, 40.0]] },
        world:    { center: [20.0,  0.0], zoom: 2,  bounds: [[-60.0, -180.0], [80.0, 180.0]] }
    };

    function initMap() {
        var preset = REGION_PRESETS[region] || REGION_PRESETS.slovakia;

        map = L.map('ek-mapa-map', {
            center: preset.center,
            zoom: preset.zoom,
            // For "blank" feel — disable zoom on dblclick (player would accidentally trigger)
            // but keep zoomControl + pinch for orientation.
            doubleClickZoom: false,
            zoomControl: true,
            attributionControl: false
        });

        // Limit panning to roughly the region bounds (with slack)
        var b = L.latLngBounds(preset.bounds);
        map.setMaxBounds(b.pad(0.3));
        map.fitBounds(b);

        // Try to load region GeoJSON (outline). Fall back to bounds rectangle if not present.
        var geoUrl = ekMapaCfg.geoJsonBase + region + '.geojson';
        fetch(geoUrl).then(function (r) {
            if (!r.ok) throw new Error('geojson missing');
            return r.json();
        }).then(function (data) {
            renderRegion(data);
            refitToRegion(b);
        }).catch(function () {
            // Placeholder rectangle for regions without bundled geojson
            renderPlaceholderRect(preset.bounds);
            refitToRegion(b);
        });

        if (!isReview) {
            map.on('click', onMapClick);
        }

        // Container might be 0x0 at init (Elementor widget, hidden tab, etc.).
        // invalidateSize + refit once the map's parent has its final dimensions.
        // ResizeObserver triggers immediately + on any later resize.
        if (typeof ResizeObserver !== 'undefined') {
            var ro = new ResizeObserver(function () {
                map.invalidateSize(false);
                map.fitBounds(b);
            });
            ro.observe(document.getElementById('ek-mapa-map'));
        } else {
            // Fallback: a few staggered retries
            setTimeout(function () { map.invalidateSize(false); map.fitBounds(b); }, 200);
            setTimeout(function () { map.invalidateSize(false); map.fitBounds(b); }, 600);
        }
    }

    function refitToRegion(b) {
        // Called after geojson loads — ensure view fits region bounds
        map.invalidateSize(false);
        map.fitBounds(b);
    }

    function renderRegion(geojson) {
        L.geoJSON(geojson, {
            style: {
                color: '#2271b1',
                weight: 2,
                fillColor: '#f6f7f7',
                fillOpacity: 1.0
            }
        }).addTo(map);
    }

    function renderPlaceholderRect(bounds) {
        L.rectangle(bounds, {
            color: '#2271b1',
            weight: 2,
            fillColor: '#f6f7f7',
            fillOpacity: 1.0
        }).addTo(map);
    }

    function onMapClick(e) {
        if (currentTaskIdx < 0 || currentTaskIdx >= tasks.length) return;
        placeMarker(currentTaskIdx, e.latlng.lat, e.latlng.lng);
        // Advance to next unanswered task (if any)
        var next = findNextUnanswered(currentTaskIdx);
        if (next >= 0) selectTask(next);
    }

    function placeMarker(taskIdx, lat, lon) {
        // Remove existing marker for this task
        if (taskMarkers[taskIdx]) {
            map.removeLayer(taskMarkers[taskIdx]);
            delete taskMarkers[taskIdx];
        }
        var hn = taskIdx + 1;
        var marker = L.marker([lat, lon], {
            draggable: true,
            title: tasks[taskIdx].name || ('Pin ' + hn),
            icon: makeNumberedIcon(hn, taskIdx === currentTaskIdx)
        }).addTo(map);
        marker.bindTooltip(tasks[taskIdx].name || ('Pin ' + hn), { permanent: false, direction: 'top' });
        marker.on('click', function () { selectTask(taskIdx); });
        marker.on('dragend', function () {
            var ll = marker.getLatLng();
            writeHidden(taskIdx, ll.lat, ll.lng);
        });
        taskMarkers[taskIdx] = marker;
        writeHidden(taskIdx, lat, lon);
        renderTaskList();
    }

    function writeHidden(taskIdx, lat, lon) {
        var hn = taskIdx + 1;
        $('#ek-mapa-lat-' + hn).val(lat.toFixed(6));
        $('#ek-mapa-lon-' + hn).val(lon.toFixed(6));
        // Persist coords to localStorage so refresh/close-tab doesn't lose progress.
        // Uses the same key format as eventkviz-quiz-form.js so restore-on-load
        // (which runs in quiz-form.js) reads the same data.
        saveCoordsToStorage();
    }

    // Tiny non-crypto hash matching shortHash() in eventkviz-quiz-form.js.
    function shortHash(s) {
        var h = 0, i;
        s = String(s || '');
        for (i = 0; i < s.length; i++) {
            h = ((h << 5) - h) + s.charCodeAt(i);
            h |= 0;
        }
        return Math.abs(h).toString(36);
    }

    function autosaveKey() {
        var $form = $('form.ek-quiz-form[data-quiz-type="mapa"]').first();
        if (!$form.length) return null;
        var akcia   = $form.find('input[name=akcia]').val() || '';
        var team    = $form.find('input[name=team]').val() || '';
        var user    = $form.find('input[name=user]').val() || '';
        var setVal  = $form.find('input[name=set]').val() || '';
        return 'ek_autosave:mapa:' + akcia + ':' + team + ':' + user + ':' + shortHash(setVal);
    }

    function saveCoordsToStorage() {
        var key = autosaveKey();
        if (!key) return;
        try {
            // Collect all hidden inputs that eventkviz-quiz-form.js's restore-on-load
            // would re-apply (its filter excludes set/set_sig/team/user/akcia/gc_).
            var data = {};
            var $form = $('form.ek-quiz-form[data-quiz-type="mapa"]').first();
            $form.find('input').each(function () {
                var name = $(this).attr('name');
                if (!name) return;
                if (/^(set|set_sig|team|user|akcia|gc_)/.test(name)) return;
                data[name] = $(this).val();
            });
            localStorage.setItem(key, JSON.stringify(data));
        } catch (err) {}
    }

    function selectTask(taskIdx) {
        currentTaskIdx = taskIdx;
        // Refresh icons (selected gets highlighted)
        Object.keys(taskMarkers).forEach(function (k) {
            var idx = parseInt(k, 10);
            var hn = idx + 1;
            taskMarkers[idx].setIcon(makeNumberedIcon(hn, idx === currentTaskIdx));
        });
        renderTaskList();
    }

    function findNextUnanswered(fromIdx) {
        for (var i = 0; i < tasks.length; i++) {
            var idx = (fromIdx + 1 + i) % tasks.length;
            if (!taskMarkers[idx]) return idx;
        }
        return -1;
    }

    function makeNumberedIcon(number, isActive, variant) {
        // variant: 'guess' (red, review), 'correct' (green, review), or default (blue/orange form)
        var color;
        if (variant === 'guess') color = '#e53935';
        else if (variant === 'correct') color = '#43a047';
        else color = isActive ? '#ff9800' : '#1976d2';
        var html = '<div class="ek-mapa-marker" style="background:' + color + '">' + number + '</div>';
        return L.divIcon({
            className: 'ek-mapa-marker-wrap',
            html: html,
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });
    }

    function renderTaskList() {
        var $list = $('#ek-mapa-tasks').empty();
        var headerTxt = isReview ? 'Výsledky' : 'Úlohy';
        $list.append($('<h3 class="ek-mapa-tasks-header"></h3>').text(headerTxt));
        tasks.forEach(function (t, idx) {
            var hn = idx + 1;
            var placed = !!taskMarkers[idx];
            var $row = $('<div class="ek-mapa-task"></div>')
                .toggleClass('is-active', idx === currentTaskIdx)
                .toggleClass('is-placed', placed)
                .attr('data-idx', idx);
            $row.append('<span class="ek-mapa-task-num">' + hn + '</span>');
            $row.append('<span class="ek-mapa-task-name">' + escapeHtml(t.name || ('Miesto ' + hn)) + '</span>');

            if (isReview) {
                if (t.distance_km !== null && typeof t.distance_km !== 'undefined') {
                    var dist = Number(t.distance_km).toFixed(2);
                    var ptsClass = t.points > 0 ? 'ek-mapa-task-result ek-mapa-task-result--ok' : 'ek-mapa-task-result ek-mapa-task-result--miss';
                    $row.append('<div class="' + ptsClass + '">' + dist + ' km · ' + (t.points || 0) + ' b</div>');
                } else {
                    $row.append('<div class="ek-mapa-task-result ek-mapa-task-result--miss">neoznačené · 0 b</div>');
                }
            } else {
                if (placed) $row.append('<span class="ek-mapa-task-check">✓</span>');
                else $row.append('<span class="ek-mapa-task-pending">…</span>');
                if (t.hint) $row.append('<div class="ek-mapa-task-hint">' + escapeHtml(t.hint) + '</div>');
                if (t.description) $row.append('<div class="ek-mapa-task-desc">' + escapeHtml(t.description) + '</div>');
                if (t.photo_url) {
                    $row.append('<img class="ek-mapa-task-photo" src="' + t.photo_url + '" alt="" />');
                }
            }

            $row.on('click', function () { focusTask(idx); });
            $list.append($row);
        });
    }

    // Review mode: pan map to selected task instead of changing active state
    function focusTask(idx) {
        if (isReview) {
            var t = tasks[idx];
            if (t.guess_lat !== null && typeof t.guess_lat !== 'undefined') {
                map.panTo([t.guess_lat, t.guess_lon]);
            } else if (t.correct_lat) {
                map.panTo([t.correct_lat, t.correct_lon]);
            }
        } else {
            selectTask(idx);
        }
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) { return ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[c]; });
    }

    function restoreFromStorage() {
        // localStorage → populate hidden inputs (writeHidden() will also
        // re-save them, which is fine — idempotent). Skipped if hidden
        // inputs already have a value (POST prev_review or autosave that
        // PHP echoed via form rendering takes precedence).
        var key = autosaveKey();
        if (!key) return false;
        try {
            var raw = localStorage.getItem(key);
            if (!raw) return false;
            var data = JSON.parse(raw);
            if (!data || typeof data !== 'object') return false;
            var didRestore = false;
            tasks.forEach(function (t, idx) {
                var hn = idx + 1;
                var latKey = 'mapa' + hn + '_lat';
                var lonKey = 'mapa' + hn + '_lon';
                if (data[latKey] && data[lonKey] && $('#ek-mapa-lat-' + hn).val() === '') {
                    $('#ek-mapa-lat-' + hn).val(data[latKey]);
                    $('#ek-mapa-lon-' + hn).val(data[lonKey]);
                    didRestore = true;
                }
            });
            return didRestore;
        } catch (err) { return false; }
    }

    function restorePrevReview() {
        // Restore order: POST hidden inputs (PHP-rendered prev_review) win,
        // localStorage fills gaps. Then place markers from whatever is in the DOM.
        var restored = restoreFromStorage();

        tasks.forEach(function (t, idx) {
            var hn = idx + 1;
            var lat = parseFloat($('#ek-mapa-lat-' + hn).val());
            var lon = parseFloat($('#ek-mapa-lon-' + hn).val());
            if (!isNaN(lat) && !isNaN(lon)) {
                placeMarker(idx, lat, lon);
            }
        });
        selectTask(findNextUnanswered(-1) >= 0 ? findNextUnanswered(-1) : 0);

        if (restored) showRestoredHint();
    }

    function showRestoredHint() {
        if ($('.ek-mapa-restored-hint').length) return;
        var $hint = $(
            '<div class="ek-mapa-restored-hint" role="status">' +
            '💾 Obnovené z predchádzajúcej relácie. ' +
            '<button type="button" class="ek-mapa-restored-clear">Vymazať a začať znova</button>' +
            '</div>'
        );
        $('#ek-mapa-container').before($hint);
        $hint.find('.ek-mapa-restored-clear').on('click', function () {
            // Remove all markers + clear hidden inputs + clear localStorage
            Object.keys(taskMarkers).forEach(function (k) {
                map.removeLayer(taskMarkers[k]);
            });
            taskMarkers = {};
            tasks.forEach(function (t, idx) {
                var hn = idx + 1;
                $('#ek-mapa-lat-' + hn).val('');
                $('#ek-mapa-lon-' + hn).val('');
            });
            try { localStorage.removeItem(autosaveKey()); } catch (err) {}
            selectTask(0);
            renderTaskList();
            $hint.remove();
        });
    }

    function renderReviewMarkers() {
        // For each task: green marker at correct location + red marker at guess (if any).
        // Markers are non-interactive (no drag, no click handler that changes state).
        tasks.forEach(function (t, idx) {
            var hn = idx + 1;
            if (t.correct_lat !== null && typeof t.correct_lat !== 'undefined') {
                var mc = L.marker([t.correct_lat, t.correct_lon], {
                    title: (t.name || ('Miesto ' + hn)) + ' (správne)',
                    icon: makeNumberedIcon(hn, false, 'correct')
                }).addTo(map);
                mc.bindTooltip('✅ ' + (t.name || ('Miesto ' + hn)), { permanent: false, direction: 'top' });
                correctMarkers[idx] = mc;
            }
            if (t.guess_lat !== null && typeof t.guess_lat !== 'undefined') {
                var mg = L.marker([t.guess_lat, t.guess_lon], {
                    title: 'Vaš odhad pre ' + (t.name || ('Miesto ' + hn)),
                    icon: makeNumberedIcon(hn, false, 'guess')
                }).addTo(map);
                var dist = (t.distance_km !== null && typeof t.distance_km !== 'undefined')
                    ? Number(t.distance_km).toFixed(2) + ' km · ' + (t.points || 0) + ' b'
                    : '';
                mg.bindTooltip('❌ Váš odhad' + (dist ? ' (' + dist + ')' : ''), { permanent: false, direction: 'top' });
                taskMarkers[idx] = mg;
            }
        });
    }

    $(function () {
        if (!document.getElementById('ek-mapa-map')) return;
        initMap();
        renderTaskList();
        if (isReview) {
            setTimeout(renderReviewMarkers, 100);
        } else {
            // Wait a tick for map to size, then restore
            setTimeout(restorePrevReview, 100);
        }
    });

})(jQuery);
