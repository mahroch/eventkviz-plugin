/* EventKviz — export štatistiky (Výsledky) do CSV + PDF.
 * Dáta v window.ekStatsExport = { akcia, entityLabel, quizzes:[{key,label}], rows:[{rank,name,total,points:{key:pts}}] }.
 * Generuje sa klientsky (žiadny server round-trip).
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
    function ascii(s) { return String(s).normalize('NFD').replace(/[̀-ͯ]/g, ''); }

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

    /* ---------- PDF (vlastný, viacstranový, ASCII text) ---------- */
    function f2(x) { return (Math.round(x * 100) / 100).toString(); }
    function pesc(s) { return ascii(s).replace(/[\\()]/g, function (c) { return '\\' + c; }); }

    function buildPdf(lines) {
        var PW = 595.28, PH = 841.89, MX = 50, MTOP = 792, MBOT = 54;
        var pages = [], cur = '', y = MTOP;
        function flush() { if (cur !== '') { pages.push(cur); cur = ''; } y = MTOP; }
        lines.forEach(function (ln) {
            var size = ln.size || 11, indent = ln.indent || 0, font = ln.font || 'F1';
            y -= (ln.gapBefore || 0);
            if (y < MBOT) { flush(); }
            cur += 'BT /' + font + ' ' + size + ' Tf ' + f2(MX + indent) + ' ' + f2(y) + ' Td (' + pesc(ln.text) + ') Tj ET\n';
            y -= size * 1.5;
        });
        flush();
        if (pages.length === 0) pages.push('');

        var objs = [];
        objs.push('<</Type/Catalog/Pages 2 0 R>>');
        var kids = [];
        for (var p = 0; p < pages.length; p++) { kids.push((5 + 2 * p) + ' 0 R'); }
        objs.push('<</Type/Pages/Kids[' + kids.join(' ') + ']/Count ' + pages.length + '>>');
        objs.push('<</Type/Font/Subtype/Type1/BaseFont/Helvetica/Encoding/WinAnsiEncoding>>');
        objs.push('<</Type/Font/Subtype/Type1/BaseFont/Helvetica-Bold/Encoding/WinAnsiEncoding>>');
        for (var i = 0; i < pages.length; i++) {
            objs.push('<</Type/Page/Parent 2 0 R/MediaBox[0 0 595.28 841.89]/Resources<</Font<</F1 3 0 R/F2 4 0 R>>>>/Contents ' + (6 + 2 * i) + ' 0 R>>');
            objs.push('<</Length ' + pages[i].length + '>>\nstream\n' + pages[i] + '\nendstream');
        }
        var pdf = '%PDF-1.4\n', offs = [];
        for (var j = 0; j < objs.length; j++) {
            offs.push(pdf.length);
            pdf += (j + 1) + ' 0 obj\n' + objs[j] + '\nendobj\n';
        }
        var xref = pdf.length;
        pdf += 'xref\n0 ' + (objs.length + 1) + '\n0000000000 65535 f \n';
        for (var k = 0; k < offs.length; k++) { pdf += ('0000000000' + offs[k]).slice(-10) + ' 00000 n \n'; }
        pdf += 'trailer<</Size ' + (objs.length + 1) + '/Root 1 0 R>>\nstartxref\n' + xref + '\n%%EOF';
        return pdf;
    }

    function pdfStringFromData(d) {
        var lines = [];
        lines.push({ text: 'Vysledky', size: 22, font: 'F2' });
        lines.push({ text: 'Poradie podla bodov (' + d.entityLabel + ')   |   akcia: ' + d.akcia, size: 10, gapBefore: 6 });
        d.rows.forEach(function (r) {
            lines.push({ text: r.rank + '.  ' + r.name + '  -  ' + r.total + ' b', size: 14, font: 'F2', gapBefore: 16 });
            d.quizzes.forEach(function (q) {
                var p = r.points[q.key] || 0;
                if (p > 0) { lines.push({ text: q.label + ': ' + p + ' b', size: 11, indent: 22, gapBefore: 2 }); }
            });
        });
        return buildPdf(lines);
    }
    function exportPdf(d) {
        dl(new Blob([pdfStringFromData(d)], { type: 'application/pdf' }), 'vysledky-' + slug(d.akcia) + '.pdf');
    }

    // Verejná API (pre testy / re-use)
    window.EKStatsExport = { exportCsv: exportCsv, exportPdf: exportPdf, pdfStringFromData: pdfStringFromData };

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.ek-stats-export-btn');
        if (!btn) { return; }
        e.preventDefault();
        var d = window.ekStatsExport;
        if (!d) { alert('Dáta na export nie sú dostupné.'); return; }
        if (btn.getAttribute('data-fmt') === 'pdf') { exportPdf(d); }
        else { exportCsv(d); }
    });
})();
