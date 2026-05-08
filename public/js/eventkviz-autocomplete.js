(function ($) {
    'use strict';

    if (typeof eventkvizAutocomplete === 'undefined') {
        return;
    }

    // Mapping: input class → dataset key in localized data
    var DATASETS = [
        { selector: '.autocomplete1', dataKey: 'artists' },
        { selector: '.autocomplete2', dataKey: 'songs' },
        { selector: '.autocomplete3', dataKey: 'movies' }
    ];

    function normalize(s) {
        if (s == null) return '';
        return String(s)
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function buildIndex(dataMap) {
        // dataMap: { "Pulp Fiction": 42, ... }
        // Returns: { keys: ["Pulp Fiction", ...], normMap: { "pulp fiction": "Pulp Fiction" } }
        var keys = Object.keys(dataMap || {});
        var normMap = {};
        for (var i = 0; i < keys.length; i++) {
            normMap[normalize(keys[i])] = keys[i];
        }
        return { keys: keys, normMap: normMap };
    }

    function makeSource(index) {
        // Substring + diacritic-insensitive matcher (replaces jQuery UI default prefix-only)
        return function (request, response) {
            var q = normalize(request.term);
            if (!q) {
                response([]);
                return;
            }
            var matches = [];
            for (var i = 0; i < index.keys.length; i++) {
                var key = index.keys[i];
                if (normalize(key).indexOf(q) !== -1) {
                    matches.push(key);
                    if (matches.length >= 50) break;
                }
            }
            response(matches);
        };
    }

    function setHiddenKey($input, dataMap, label) {
        // Stores the ID into the adjacent hidden input
        var id = (label != null && Object.prototype.hasOwnProperty.call(dataMap, label))
            ? dataMap[label]
            : '';
        $input.next("input[type='hidden']").val(id);
    }

    function attachAutoResolve($input, dataMap, index) {
        // On blur/change: if user typed a name that matches a known key
        // (case + diacritic insensitive), auto-fill the hidden _key input.
        // Fixes the bug where users typing the exact name without clicking
        // the dropdown got 0 points.
        function resolve() {
            var typed = $input.val();
            var canonical = index.normMap[normalize(typed)];
            if (canonical) {
                // Snap visible value to canonical form so evaluation matches
                if (typed !== canonical) $input.val(canonical);
                setHiddenKey($input, dataMap, canonical);
            } else {
                setHiddenKey($input, dataMap, null);
            }
        }
        $input.on('change blur', resolve);
    }

    $(function () {
        DATASETS.forEach(function (cfg) {
            var dataMap = eventkvizAutocomplete[cfg.dataKey];
            if (!dataMap) return; // dataset not provided for this quiz type

            var index = buildIndex(dataMap);
            var $inputs = $(cfg.selector);
            if (!$inputs.length) return;

            $inputs.each(function () {
                var $input = $(this);
                $input.autocomplete({
                    source: makeSource(index),
                    minLength: 1,
                    select: function (event, ui) {
                        setHiddenKey($input, dataMap, ui.item.value);
                    }
                });
                attachAutoResolve($input, dataMap, index);
            });
        });
    });

})(jQuery);
