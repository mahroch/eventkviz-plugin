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
        }).catch(function () {
            // Placeholder rectangle for regions without bundled geojson
            renderPlaceholderRect(preset.bounds);
        });

        if (!isReview) {
            map.on('click', onMapClick);
        }
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

    function restorePrevReview() {
        // Pre-fill markers from POST hidden inputs (autosave or prev_review)
        tasks.forEach(function (t, idx) {
            var hn = idx + 1;
            var lat = parseFloat($('#ek-mapa-lat-' + hn).val());
            var lon = parseFloat($('#ek-mapa-lon-' + hn).val());
            if (!isNaN(lat) && !isNaN(lon)) {
                placeMarker(idx, lat, lon);
            }
        });
        selectTask(findNextUnanswered(-1) >= 0 ? findNextUnanswered(-1) : 0);
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
