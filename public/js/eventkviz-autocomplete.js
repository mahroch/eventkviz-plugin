(function ($) {
    'use strict';

    if (typeof eventkvizCfg === 'undefined' || !eventkvizCfg.apiUrl) {
        return;
    }

    var SELECTOR_TO_TYPE = {
        '.autocomplete1': 'artists',
        '.autocomplete2': 'songs',
        '.autocomplete3': 'movies'
    };

    function normalize(s) {
        if (s == null) return '';
        return String(s)
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .trim();
    }

    function escapeHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function highlightMatch(label, query) {
        var nLabel = normalize(label);
        var nQuery = normalize(query);
        if (!nQuery) return escapeHtml(label);
        var idx = nLabel.indexOf(nQuery);
        if (idx === -1) return escapeHtml(label);
        var before = label.substr(0, idx);
        var match  = label.substr(idx, nQuery.length);
        var after  = label.substr(idx + nQuery.length);
        return escapeHtml(before) + '<strong>' + escapeHtml(match) + '</strong>' + escapeHtml(after);
    }

    function setHiddenKey($input, id) {
        $input.next("input[type='hidden']").val(id || '');
    }

    function setMatchedFlag($input, matched) {
        $input.toggleClass('ek-matched', !!matched);
    }

    function attachAutocomplete($input, type) {
        var pending = null;

        $input.autocomplete({
            minLength: 1,
            delay: 200,
            source: function (request, response) {
                if (pending) pending.abort();
                pending = $.ajax({
                    url: eventkvizCfg.apiUrl,
                    method: 'GET',
                    dataType: 'json',
                    data: { type: type, q: request.term, limit: 15 },
                    success: function (items) {
                        var q = request.term;
                        response((items || []).map(function (it) {
                            return { label: it.label, value: it.label, id: it.id, _q: q };
                        }));
                    },
                    error: function (xhr, status) {
                        if (status !== 'abort') response([]);
                    }
                });
            },
            select: function (event, ui) {
                setHiddenKey($input, ui.item.id);
                setMatchedFlag($input, true);
            },
            change: function (event, ui) {
                if (!ui.item) {
                    setHiddenKey($input, '');
                    setMatchedFlag($input, false);
                }
            }
        });

        // Match highlighting in dropdown
        $input.autocomplete('instance')._renderItem = function (ul, item) {
            return $('<li>')
                .append($('<div>').html(highlightMatch(item.label, item._q || '')))
                .appendTo(ul);
        };

        $input.on('input', function () {
            setMatchedFlag($input, false);
            setHiddenKey($input, '');
        });
    }

    $(function () {
        var datasets = eventkvizCfg.datasets || [];
        Object.keys(SELECTOR_TO_TYPE).forEach(function (sel) {
            var type = SELECTOR_TO_TYPE[sel];
            if (datasets.indexOf(type) === -1) return;
            $(sel).each(function () { attachAutocomplete($(this), type); });
        });
    });

})(jQuery);
