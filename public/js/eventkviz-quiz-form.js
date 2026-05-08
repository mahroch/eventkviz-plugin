(function ($) {
    'use strict';

    $(function () {
        var $forms = $('form.ek-quiz-form[data-quiz-type]');
        if (!$forms.length) return;

        $forms.each(function () { initForm($(this)); });
    });

    function initForm($form) {
        var $answers = collectAnswerInputs($form);
        if (!$answers.length) return;

        addProgressIndicator($form, $answers);
        attachSubmitConfirmation($form, $answers);
        attachAutosave($form, $answers);
    }

    function collectAnswerInputs($form) {
        // Visible answer inputs/selects, excluding hidden meta inputs (set, set_sig, team, user, akcia, gc_*, *_key)
        return $form.find('input:not([type=hidden]):not([type=submit]):not([type=button]), select').filter(function () {
            var name = $(this).attr('name');
            if (!name) return false;
            return !/^(set|set_sig|team|user|akcia|gc_)/.test(name) && !/_key$/.test(name);
        });
    }

    function countFilled($answers) {
        var n = 0;
        $answers.each(function () {
            var v = $(this).val();
            if (v != null && String(v).trim() !== '') n++;
        });
        return n;
    }

    function addProgressIndicator($form, $answers) {
        var total = $answers.length;
        var $bar = $(
            '<div class="ek-progress" role="status" aria-live="polite">' +
                '<div class="ek-progress-track"><div class="ek-progress-fill" style="width:0%"></div></div>' +
                '<div class="ek-progress-text">Odpovedané <span class="ek-progress-current">0</span>/<span class="ek-progress-total">' + total + '</span></div>' +
            '</div>'
        );
        $form.find('.ek-quiz-content').first().prepend($bar).end();
        if (!$bar.parent().length) $form.prepend($bar);

        function update() {
            var filled = countFilled($answers);
            $bar.find('.ek-progress-current').text(filled);
            var pct = total > 0 ? Math.round((filled / total) * 100) : 0;
            $bar.find('.ek-progress-fill').css('width', pct + '%');
        }
        $answers.on('input change blur', update);
        update();
    }

    function attachSubmitConfirmation($form, $answers) {
        $form.on('submit', function (e) {
            var filled = countFilled($answers);
            var total = $answers.length;
            if (filled < total) {
                var msg = 'Vyplnené: ' + filled + ' z ' + total + ' odpovedí.\n\nNaozaj odoslať? Po odoslaní sa už nedá meniť.';
                if (!window.confirm(msg)) {
                    e.preventDefault();
                    return false;
                }
            }
            // user confirmed (or all filled) — clear autosave on the way out
            try { localStorage.removeItem(autosaveKey($form)); } catch (err) {}
        });
    }

    function autosaveKey($form) {
        var akcia = $form.find('input[name=akcia]').val() || '';
        var team  = $form.find('input[name=team]').val() || '';
        var user  = $form.find('input[name=user]').val() || '';
        var type  = $form.attr('data-quiz-type') || 'unknown';
        return 'ek_autosave:' + type + ':' + akcia + ':' + team + ':' + user;
    }

    function attachAutosave($form, $answers) {
        var key = autosaveKey($form);

        // Restore on load
        try {
            var raw = localStorage.getItem(key);
            if (raw) {
                var data = JSON.parse(raw);
                $answers.each(function () {
                    var name = $(this).attr('name');
                    if (name && Object.prototype.hasOwnProperty.call(data, name)) {
                        $(this).val(data[name]);
                    }
                });
                $answers.trigger('change');
                showRestoredHint($form);
            }
        } catch (err) {}

        // Save on edit (debounced)
        var saveTimer;
        $answers.on('input change', function () {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(function () { saveNow(key, $answers); }, 400);
        });
    }

    function saveNow(key, $answers) {
        try {
            var data = {};
            $answers.each(function () {
                var name = $(this).attr('name');
                if (name) data[name] = $(this).val();
            });
            localStorage.setItem(key, JSON.stringify(data));
        } catch (err) {}
    }

    function showRestoredHint($form) {
        if ($form.find('.ek-restored-hint').length) return;
        var $hint = $('<div class="ek-restored-hint" role="status">Obnovené z predchádzajúcej relácie. ' +
            '<button type="button" class="ek-restored-clear">Vymazať</button></div>');
        $form.find('.ek-quiz-content').first().prepend($hint);
        $hint.find('.ek-restored-clear').on('click', function () {
            $form.find('input:not([type=hidden]):not([type=submit])').val('');
            $form.find('select').prop('selectedIndex', 0);
            try { localStorage.removeItem(autosaveKey($form)); } catch (err) {}
            $hint.remove();
            $form.find('.ek-quiz-form input, .ek-quiz-form select').trigger('change');
        });
    }

})(jQuery);
