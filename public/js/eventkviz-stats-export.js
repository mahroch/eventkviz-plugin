/* EventKviz — export štatistiky (Výsledky).
 *  - CSV: dáta z window.ekStatsExport, klientsky download (Excel-friendly).
 *  - PDF: window.print() s print-štýlom (@media print) — vytlačí kartu výsledkov
 *         so zachovaným vizuálom (fialové pozadie, karty, emoji). Užívateľ zvolí
 *         „Uložiť ako PDF". Tým PDF zodpovedá vzhľadu stránky.
 */
(function () {
    'use strict';

    function dl(blob, filename) {
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url; a.download = filename;
        document.body.appendChild(a); a.click();
        setTimeout(function () { URL.revokeObjectURL(url); a.remove(); }, 200);
    }

    function slug(s) {
        return String(s).normalize('NFD').replace(/[̀-ͯ]/g, '')
            .replace(/[^a-zA-Z0-9]+/g, '-').replace(/^-+|-+$/g, '').toLowerCase() || 'vysledky';
    }

    /* ---------- CSV ---------- */
    function csvCell(v) {
        v = String(v);
        return /[",\n;]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v;
    }
    function exportCsv(d) {
        var sep = ';';                                   // ; → Excel SK/CZ friendly
        var head = ['Poradie', d.entityLabel, 'Body spolu'].concat(d.quizzes.map(function (q) { return q.label; }));
        var lines = [head.map(csvCell).join(sep)];
        d.rows.forEach(function (r) {
            var row = [r.rank, r.name, r.total].concat(d.quizzes.map(function (q) {
                var p = r.points[q.key] || 0; return p > 0 ? p : '';
            }));
            lines.push(row.map(csvCell).join(sep));
        });
        var csv = '﻿' + lines.join('\r\n');         // BOM pre UTF-8 v Exceli
        dl(new Blob([csv], { type: 'text/csv;charset=utf-8' }), 'vysledky-' + slug(d.akcia) + '.csv');
    }

    /* ---------- PDF cez natívnu tlač (vizuál stránky) ---------- */
    function exportPdf() {
        window.print();
    }

    window.EKStatsExport = { exportCsv: exportCsv, exportPdf: exportPdf };

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.ek-stats-export-btn');
        if (!btn) { return; }
        e.preventDefault();
        if (btn.getAttribute('data-fmt') === 'pdf') { exportPdf(); return; }
        var d = window.ekStatsExport;
        if (!d) { alert('Dáta na export nie sú dostupné.'); return; }
        exportCsv(d);
    });
})();
