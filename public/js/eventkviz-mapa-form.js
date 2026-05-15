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
    var quizType = $container.data('quiz-type') || 'pin';   // 'pin' | 'river' | 'mountain'
    // data-overlays je JSON object {cities,regions,rivers} — jQuery vie auto-parse JSON,
    // ale pre istotu defenzívne handling.
    var overlaysCfg = $container.data('overlays');
    if (typeof overlaysCfg === 'string') {
        try { overlaysCfg = JSON.parse(overlaysCfg); } catch (e) { overlaysCfg = {}; }
    }
    overlaysCfg = overlaysCfg || {};
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

        // Base layer logic:
        //   - Ak admin povolil aspoň jednu MapTiler tile vrstvu → MapTiler tile + Leaflet
        //     control.layers prepinač. Žiadny outline polygon (tile už ukazuje hranice).
        //   - Inak fallback na blanket outline (zero MapTiler tile cost).
        var enabledTiles = buildTileLayers();
        if (enabledTiles.layers.length > 0) {
            // Pridaj prvý ako default
            enabledTiles.layers[0].layer.addTo(map);
            if (enabledTiles.layers.length > 1) {
                var baseControl = {};
                enabledTiles.layers.forEach(function (l) { baseControl[l.label] = l.layer; });
                L.control.layers(baseControl, null, { position: 'topright', collapsed: false }).addTo(map);
            }
            loadOverlays();
            loadFeatureLayer();
        } else {
            // Outline mode — fetch region geojson, render, then overlays
            var geoUrl = ekMapaCfg.geoJsonBase + region + '.geojson';
            fetch(geoUrl).then(function (r) {
                if (!r.ok) throw new Error('geojson missing');
                return r.json();
            }).then(function (data) {
                renderRegion(data);
                refitToRegion(b);
                loadOverlays();
                loadFeatureLayer();
            }).catch(function () {
                renderPlaceholderRect(preset.bounds);
                refitToRegion(b);
                loadOverlays();
                loadFeatureLayer();
            });
        }

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

    function buildTileLayers() {
        // Vyberie zoznam povolených MapTiler tile vrstiev podľa overlaysCfg
        // tile_streets / tile_satellite / tile_outdoor flagov. Ak žiadny key
        // nie je nastavený v plugin Settings, žiadne tiles sa nedajú renderovať.
        var key = (ekMapaCfg && ekMapaCfg.maptilerKey) || '';
        var out = { layers: [] };
        if (!key) return out;
        var TILE_SOURCES = [
            { flag: 'tile_streets',   label: 'Streets',  style: 'streets-v2' },
            { flag: 'tile_outdoor',   label: 'Outdoor',  style: 'outdoor-v2' },
            { flag: 'tile_satellite', label: 'Satelit',  style: 'satellite' }
        ];
        TILE_SOURCES.forEach(function (src) {
            if (!overlaysCfg[src.flag]) return;
            var url;
            if (src.style === 'satellite') {
                url = 'https://api.maptiler.com/tiles/satellite-v2/{z}/{x}/{y}.jpg?key=' + encodeURIComponent(key);
            } else {
                url = 'https://api.maptiler.com/maps/' + src.style + '/{z}/{x}/{y}.png?key=' + encodeURIComponent(key);
            }
            var layer = L.tileLayer(url, {
                tileSize: 256,
                maxZoom: 18,
                attribution: '&copy; <a href="https://www.maptiler.com/">MapTiler</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            });
            out.layers.push({ label: src.label, layer: layer });
        });
        return out;
    }

    function loadFeatureLayer() {
        // Pre quiz_type=river/mountain — načíta features GeoJSON, renderuje
        // všetky features ako interaktívne layers s click handlerom.
        if (quizType !== 'river' && quizType !== 'mountain') return;
        var fileName = quizType === 'river' ? 'sk-rivers.geojson' : 'sk-mountains.geojson';
        fetch(ekMapaCfg.geoJsonBase + fileName).then(function (r) {
            return r.ok ? r.json() : null;
        }).then(function (data) {
            if (!data) return;

            // Ak overlay flag feature_only_set, zobraz IBA features relevantné pre
            // tento pokus — eliminuje rozptyľovače. V review móde do "relevantných"
            // zaradíme aj nesprávny guess hráča, aby ho videl ako červenú.
            if (overlaysCfg.feature_only_set) {
                var visibleNames = {};
                tasks.forEach(function (t) {
                    if (t.id) visibleNames[t.id] = true;
                    // Review mode shape: { correct_feature, guess_feature, is_correct, ... }
                    if (t.correct_feature) visibleNames[t.correct_feature] = true;
                    if (t.guess_feature) visibleNames[t.guess_feature] = true;
                });
                data = {
                    type: 'FeatureCollection',
                    features: data.features.filter(function (f) {
                        return f.properties && visibleNames[f.properties.name];
                    })
                };
            }
            // Pomocné názvy pri hover sú anti-cheat issue — admin musí explicitne
            // povoliť cez overlay checkbox „feature_labels" (default OFF). Pre
            // súťažné kvízy ostáva map clean. V review móde tooltip dáva zmysel
            // (kvíz už skončil) — ale držíme rovnaký flag pre konzistentnosť.
            var showLabels = !!overlaysCfg.feature_labels;
            featureLayer = L.geoJSON(data, {
                style: featureBaseStyle,
                onEachFeature: function (feature, layer) {
                    var name = feature.properties && feature.properties.name;
                    if (!name) return;
                    layer.on('click', function () { onFeaturePick(name); });
                    layer.on('mouseover', function () {
                        if (!isSelected(name) && !isReview) layer.setStyle(featureHoverStyle());
                    });
                    layer.on('mouseout', function () {
                        applyFeatureStyle(layer);
                    });
                    if (showLabels) {
                        layer.bindTooltip(name, { permanent: false, direction: 'top', sticky: true });
                    }
                }
            }).addTo(map);
            // V review móde renderujeme overlay so správnym zvýraznením
            if (isReview) {
                featureLayer.eachLayer(function (l) { applyFeatureStyle(l); });
            }
        }).catch(function () {});
    }

    function isSelected(featureName) {
        // Existuje task ktorý má vybranú túto feature?
        for (var i = 0; i < tasks.length; i++) {
            var picked = taskMarkers[i];
            if (picked && picked.feature === featureName) return true;
        }
        return false;
    }

    function featureBaseStyle(feature) {
        if (quizType === 'river') {
            return { color: '#3aa6f0', weight: 4, opacity: 0.85 };
        }
        // Mountain polygons — vyplnená plocha aby bola dobre rozlíšiteľná
        // (predtým iba jemný obrys s 0.45 fill — slabo viditeľné nad tile vrstvou).
        return { color: '#33691e', weight: 2, fillColor: '#7cb342', fillOpacity: 0.7 };
    }

    function featureHoverStyle() {
        if (quizType === 'river') return { color: '#1976d2', weight: 6, opacity: 1.0 };
        // Mountain hover — jasná modrá vs base zelená pre maximálny kontrast.
        // (Predtým „tmavšia zelená" splývala s base zelenou, ťažko sa rozlišovalo.)
        return { color: '#0d47a1', weight: 3, fillColor: '#42a5f5', fillOpacity: 0.85 };
    }

    function featureSelectedStyle(isCorrect) {
        // V review móde: green = správne, red = nesprávne
        if (isReview) {
            if (isCorrect === true)  return { color: '#43a047', weight: 5, fillColor: '#66bb6a', fillOpacity: 0.55 };
            if (isCorrect === false) return { color: '#e53935', weight: 5, fillColor: '#ef5350', fillOpacity: 0.55 };
        }
        // Form mode: orange = vybraté
        return { color: '#ef6c00', weight: 5, fillColor: '#ff9800', fillOpacity: 0.55 };
    }

    function applyFeatureStyle(layer) {
        var name = layer.feature && layer.feature.properties && layer.feature.properties.name;
        if (!name) { layer.setStyle(featureBaseStyle()); return; }

        if (isReview) {
            // tasks v review móde majú correct_feature, guess_feature, is_correct
            for (var i = 0; i < tasks.length; i++) {
                var t = tasks[i];
                if (t.guess_feature === name) {
                    layer.setStyle(featureSelectedStyle(t.is_correct));
                    return;
                }
                if (t.correct_feature === name && !t.is_correct) {
                    // Ukáž správnu lokáciu trochu zvýraznenú aj keď ju hráč nevybral
                    layer.setStyle({ color: '#43a047', weight: 3, fillColor: '#a5d6a7', fillOpacity: 0.35, dashArray: '5,4' });
                    return;
                }
            }
            layer.setStyle(featureBaseStyle());
            return;
        }

        // Form mode
        if (isSelected(name)) layer.setStyle(featureSelectedStyle());
        else layer.setStyle(featureBaseStyle());
    }

    function loadOverlays() {
        // Pomocné vrstvy — v1 iba pre Slovensko (dáta bundleované v plugine).
        if (region !== 'slovakia') return;

        if (overlaysCfg.regions) {
            fetch(ekMapaCfg.geoJsonBase + 'sk-regions.geojson').then(function (r) {
                return r.ok ? r.json() : null;
            }).then(function (data) { if (data) renderRegionsOverlay(data); }).catch(function () {});
        }
        if (overlaysCfg.rivers) {
            fetch(ekMapaCfg.geoJsonBase + 'sk-rivers.geojson').then(function (r) {
                return r.ok ? r.json() : null;
            }).then(function (data) { if (data) renderRiversOverlay(data); }).catch(function () {});
        }
        if (overlaysCfg.cities_main || overlaysCfg.cities_regional) {
            fetch(ekMapaCfg.geoJsonBase + 'sk-cities.geojson').then(function (r) {
                return r.ok ? r.json() : null;
            }).then(function (data) {
                if (!data) return;
                // Filter podľa tier: tier 1 = krajské, tier 2 = okresné. Admin si vyberie.
                var filtered = {
                    type: 'FeatureCollection',
                    features: data.features.filter(function (f) {
                        var t = f.properties && f.properties.tier;
                        if (t === 1) return !!overlaysCfg.cities_main;
                        if (t === 2) return !!overlaysCfg.cities_regional;
                        return false;
                    })
                };
                renderCitiesOverlay(filtered);
            }).catch(function () {});
        }
    }

    function renderRegionsOverlay(geojson) {
        L.geoJSON(geojson, {
            style: {
                color: '#9aa5b1',
                weight: 1,
                opacity: 0.6,
                fillColor: 'transparent',
                fillOpacity: 0,
                dashArray: '4,3'
            },
            interactive: false
        }).addTo(map);
    }

    function renderRiversOverlay(geojson) {
        L.geoJSON(geojson, {
            style: {
                color: '#3aa6f0',
                weight: 1.4,
                opacity: 0.7
            },
            interactive: false,
            onEachFeature: function (feature, layer) {
                if (feature.properties && feature.properties.name) {
                    layer.bindTooltip(feature.properties.name, {
                        permanent: false,
                        direction: 'auto',
                        className: 'ek-mapa-river-tip'
                    });
                }
            }
        }).addTo(map);
    }

    function renderCitiesOverlay(geojson) {
        // V river/mountain móde nech mestá nezachytávajú klik (aby šiel na features pod nimi)
        var interactive = (quizType === 'pin');
        L.geoJSON(geojson, {
            pointToLayer: function (feature, latlng) {
                var tier = feature.properties.tier || 2;
                var size = tier === 1 ? 8 : 5;
                return L.circleMarker(latlng, {
                    radius: size,
                    fillColor: tier === 1 ? '#444' : '#888',
                    color: '#fff',
                    weight: 1.5,
                    fillOpacity: 0.95,
                    interactive: interactive
                });
            },
            onEachFeature: function (feature, layer) {
                var name = feature.properties.name || '';
                var tier = feature.properties.tier || 2;
                if (name) {
                    layer.bindTooltip(name, {
                        permanent: tier === 1,  // krajské mestá majú visible label
                        direction: 'top',
                        offset: [0, -6],
                        className: 'ek-mapa-city-tip ek-mapa-city-tip--tier' + tier
                    });
                }
            }
        }).addTo(map);
    }

    function onMapClick(e) {
        // Pin mode only — feature modes have per-feature click handlers
        if (quizType !== 'pin') return;
        if (currentTaskIdx < 0 || currentTaskIdx >= tasks.length) return;
        placeMarker(currentTaskIdx, e.latlng.lat, e.latlng.lng);
        // Advance to next unanswered task (if any)
        var next = findNextUnanswered(currentTaskIdx);
        if (next >= 0) selectTask(next);
    }

    // Feature-pick mode: keď hráč klikne na rieku/pohorie, zaregistrujeme to
    // pre aktívnu úlohu. Auto-advance na ďalšiu unanswered.
    function onFeaturePick(featureName) {
        if (quizType === 'pin') return;
        if (currentTaskIdx < 0 || currentTaskIdx >= tasks.length) return;

        // Existuje úloha ktorá už má túto feature vybranú?
        var existingIdx = -1;
        for (var i = 0; i < tasks.length; i++) {
            if (taskMarkers[i] && taskMarkers[i].feature === featureName) {
                existingIdx = i;
                break;
            }
        }

        // Klik na feature ktorá patrí aktívnej úlohe = odznač (toggle off)
        if (existingIdx === currentTaskIdx) {
            unpickFeature(currentTaskIdx);
            return;
        }

        // Klik na feature ktorá je už priradená INEJ úlohe — nedovoľ duplicitné
        // priradenie. Namiesto toho zafokusuj tú úlohu (hráč si vidí kde to má).
        if (existingIdx >= 0) {
            selectTask(existingIdx);
            return;
        }

        // Nový pick pre aktívnu úlohu
        var hn = currentTaskIdx + 1;
        $('#ek-mapa-feature-' + hn).val(featureName);
        taskMarkers[currentTaskIdx] = { feature: featureName };
        saveCoordsToStorage();
        if (featureLayer) {
            featureLayer.eachLayer(function (layer) {
                applyFeatureStyle(layer);
            });
        }
        renderTaskList();
        var next = findNextUnanswered(currentTaskIdx);
        if (next >= 0) selectTask(next);
    }

    function unpickFeature(idx) {
        if (idx < 0 || idx >= tasks.length) return;
        var hn = idx + 1;
        $('#ek-mapa-feature-' + hn).val('');
        delete taskMarkers[idx];
        saveCoordsToStorage();
        if (featureLayer) {
            featureLayer.eachLayer(function (layer) {
                applyFeatureStyle(layer);
            });
        }
        // Po unpick si zachovať aktívny task na práve unpicked (aby hráč mohol klik znova)
        currentTaskIdx = idx;
        renderTaskList();
    }

    var featureLayer = null;

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
        // Refresh marker icons IBA v pin móde (feature mode má { feature: 'X' } objekty,
        // nie L.Marker — .setIcon by hodil TypeError a zhodil by celý selectTask).
        if (quizType === 'pin') {
            Object.keys(taskMarkers).forEach(function (k) {
                var idx = parseInt(k, 10);
                var hn = idx + 1;
                if (taskMarkers[idx] && typeof taskMarkers[idx].setIcon === 'function') {
                    taskMarkers[idx].setIcon(makeNumberedIcon(hn, idx === currentTaskIdx));
                }
            });
        }
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
        var prefix = (quizType === 'river') ? 'Nájdi rieku: ' : (quizType === 'mountain' ? 'Nájdi pohorie: ' : '');
        tasks.forEach(function (t, idx) {
            var hn = idx + 1;
            var placed = !!taskMarkers[idx];
            var $row = $('<div class="ek-mapa-task"></div>')
                .toggleClass('is-active', idx === currentTaskIdx)
                .toggleClass('is-placed', placed)
                .attr('data-idx', idx);
            $row.append('<span class="ek-mapa-task-num">' + hn + '</span>');
            var displayName = prefix + (t.name || ('Miesto ' + hn));
            $row.append('<span class="ek-mapa-task-name">' + escapeHtml(displayName) + '</span>');

            if (isReview) {
                if (quizType === 'pin') {
                    // Pin mode: km + body (alebo neoznačené)
                    if (t.distance_km !== null && typeof t.distance_km !== 'undefined') {
                        var dist = Number(t.distance_km).toFixed(2);
                        var ptsClass = t.points > 0 ? 'ek-mapa-task-result ek-mapa-task-result--ok' : 'ek-mapa-task-result ek-mapa-task-result--miss';
                        $row.append('<div class="' + ptsClass + '">' + dist + ' km · ' + (t.points || 0) + ' b</div>');
                    } else {
                        $row.append('<div class="ek-mapa-task-result ek-mapa-task-result--miss">neoznačené · 0 b</div>');
                    }
                } else {
                    // Feature mode (river/mountain): binárne výsledky bez km
                    var pts = t.points || 0;
                    if (typeof t.guess_feature === 'undefined' || t.guess_feature === null || t.guess_feature === '') {
                        $row.append('<div class="ek-mapa-task-result ek-mapa-task-result--miss">neoznačené · 0 b</div>');
                    } else if (t.is_correct) {
                        $row.append('<div class="ek-mapa-task-result ek-mapa-task-result--ok">✓ správne · ' + pts + ' b</div>');
                    } else {
                        $row.append('<div class="ek-mapa-task-result ek-mapa-task-result--miss">✗ ' + escapeHtml(t.guess_feature) + ' · 0 b</div>');
                    }
                }
            } else {
                if (placed) {
                    // Wrap do jedného containera aby ✓ + × obsadili 1 grid cell (col 3),
                    // nie aby × spadla do nového riadku (4. neexistuje).
                    var $status = $('<span class="ek-mapa-task-status"></span>');
                    $status.append('<span class="ek-mapa-task-check">✓</span>');
                    if (quizType === 'river' || quizType === 'mountain') {
                        var $x = $('<button type="button" class="ek-mapa-task-unpick" title="Odznačiť">×</button>');
                        $x.on('click', function (e) { e.stopPropagation(); unpickFeature(idx); });
                        $status.append($x);
                    }
                    $row.append($status);
                } else {
                    $row.append('<span class="ek-mapa-task-pending">…</span>');
                }
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
        var restored = restoreFromStorage();

        if (quizType === 'pin') {
            tasks.forEach(function (t, idx) {
                var hn = idx + 1;
                var lat = parseFloat($('#ek-mapa-lat-' + hn).val());
                var lon = parseFloat($('#ek-mapa-lon-' + hn).val());
                if (!isNaN(lat) && !isNaN(lon)) placeMarker(idx, lat, lon);
            });
        } else {
            // Feature mode — fill taskMarkers map from prev feature inputs
            tasks.forEach(function (t, idx) {
                var hn = idx + 1;
                var feat = $('#ek-mapa-feature-' + hn).val();
                if (feat) taskMarkers[idx] = { feature: feat };
            });
            // Re-style features to reflect picks
            if (featureLayer) featureLayer.eachLayer(applyFeatureStyle);
            renderTaskList();
        }

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
        // Pin mode only — feature mode has its own visual via featureLayer styles
        // (zelený correct / červený wrong fill in applyFeatureStyle).
        if (quizType !== 'pin') return;

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
