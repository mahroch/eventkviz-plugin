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

        var $saveable = collectSaveableInputs($form);

        addProgressIndicator($form, $answers);
        attachSubmitConfirmation($form, $answers);
        attachAutosave($form, $answers, $saveable);
    }

    function collectAnswerInputs($form) {
        // Visible answer inputs/selects (used for progress + submit-confirm + per-edit save trigger)
        return $form.find('input:not([type=hidden]):not([type=submit]):not([type=button]), select').filter(function () {
            var name = $(this).attr('name');
            if (!name) return false;
            return !/^(set|set_sig|team|user|akcia|gc_)/.test(name) && !/_key$/.test(name);
        });
    }

    function collectSaveableInputs($form) {
        // Same as answers, plus the paired hidden *_key inputs that store the resolved CCT id
        return $form.find('input, select').filter(function () {
            var name = $(this).attr('name');
            if (!name) return false;
            return !/^(set|set_sig|team|user|akcia|gc_)/.test(name);
        });
    }

    function questionKey($input) {
        // Group inputs that belong to the same question (e.g. artist1 + song1 → "1")
        var name = $input.attr('name') || '';
        var m = name.match(/(\d+)(_key)?$/);
        return m ? m[1] : name;
    }

    function countFilledQuestions($answers) {
        // A question counts as "answered" if at least one of its fields is non-empty
        var byQ = {};
        $answers.each(function () {
            var v = $(this).val();
            if (v != null && String(v).trim() !== '') {
                byQ[questionKey($(this))] = true;
            }
        });
        return Object.keys(byQ).length;
    }

    function totalQuestions($answers) {
        var byQ = {};
        $answers.each(function () { byQ[questionKey($(this))] = true; });
        return Object.keys(byQ).length;
    }

    function addProgressIndicator($form, $answers) {
        var total = totalQuestions($answers);
        var $bar = $(
            '<div class="ek-progress" role="status" aria-live="polite">' +
                '<div class="ek-progress-track"><div class="ek-progress-fill" style="width:0%"></div></div>' +
                '<div class="ek-progress-text">Odpovedané <span class="ek-progress-current">0</span>/<span class="ek-progress-total">' + total + '</span></div>' +
            '</div>'
        );
        $form.find('.ek-quiz-content').first().prepend($bar).end();
        if (!$bar.parent().length) $form.prepend($bar);

        function update() {
            var filled = countFilledQuestions($answers);
            $bar.find('.ek-progress-current').text(filled);
            var pct = total > 0 ? Math.round((filled / total) * 100) : 0;
            $bar.find('.ek-progress-fill').css('width', pct + '%');
        }
        $answers.on('input change blur', update);
        update();
    }

    function attachSubmitConfirmation($form, $answers) {
        $form.on('submit', function (e) {
            var filled = countFilledQuestions($answers);
            var total = totalQuestions($answers);
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

    function attachAutosave($form, $answers, $saveable) {
        var key = autosaveKey($form);

        // Restore on load
        try {
            var raw = localStorage.getItem(key);
            if (raw) {
                var data = JSON.parse(raw);
                $saveable.each(function () {
                    var name = $(this).attr('name');
                    if (name && Object.prototype.hasOwnProperty.call(data, name)) {
                        $(this).val(data[name]);
                    }
                });
                // Re-apply matched flag to autocomplete inputs whose _key was restored
                $form.find("input.autocomplete1, input.autocomplete2, input.autocomplete3").each(function () {
                    var $i = $(this);
                    var $hidden = $i.next("input[type='hidden']");
                    if ($i.val() && $hidden.val()) {
                        $i.addClass('ek-matched');
                    }
                });
                showRestoredHint($form);
            }
        } catch (err) {}

        // Save on edit (debounced) — listen on visible answers but persist the full saveable set
        var saveTimer;
        $answers.on('input change', function () {
            clearTimeout(saveTimer);
            saveTimer = setTimeout(function () { saveNow(key, $saveable); }, 400);
        });
    }

    function saveNow(key, $saveable) {
        try {
            var data = {};
            $saveable.each(function () {
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
