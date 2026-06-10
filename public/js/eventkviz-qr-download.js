/* EventKviz — QR download pre linky kvízov (admin).
 * Generuje QR z URL cez window.qrcode (qrcode-generator, MIT) a stiahne:
 *   - PNG (canvas, biely podklad + quiet zone)
 *   - PDF (veľký QR vycentrovaný na A4, vektorovo — žiadna ťažká knižnica)
 * Filename z data-qr-name (napr. "siemens-music").
 */
(function () {
    'use strict';

    function buildMatrix(url) {
        if (typeof window.qrcode !== 'function') { return null; }
        var qr = window.qrcode(0, 'M');     // 0 = auto type, EC level M
        qr.addData(url);
        qr.make();
        var n = qr.getModuleCount();
        var m = new Array(n);
        for (var r = 0; r < n; r++) {
            m[r] = new Array(n);
            for (var c = 0; c < n; c++) { m[r][c] = qr.isDark(r, c); }
        }
        return m;
    }

    function triggerDownload(blob, filename) {
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url; a.download = filename;
        document.body.appendChild(a); a.click();
        setTimeout(function () { URL.revokeObjectURL(url); a.remove(); }, 200);
    }

    function matrixToCanvas(matrix) {
        var n = matrix.length, quiet = 4;
        var scale = Math.max(6, Math.floor(1200 / (n + quiet * 2)));   // ~veľký kód
        var size = (n + quiet * 2) * scale;
        var cv = document.createElement('canvas');
        cv.width = cv.height = size;
        var ctx = cv.getContext('2d');
        ctx.fillStyle = '#ffffff'; ctx.fillRect(0, 0, size, size);
        ctx.fillStyle = '#000000';
        for (var r = 0; r < n; r++) {
            for (var c = 0; c < n; c++) {
                if (matrix[r][c]) { ctx.fillRect((c + quiet) * scale, (r + quiet) * scale, scale, scale); }
            }
        }
        return cv;
    }

    function downloadPNG(matrix, name) {
        matrixToCanvas(matrix).toBlob(function (blob) { triggerDownload(blob, name + '.png'); }, 'image/png');
    }

    function f2(x) { return (Math.round(x * 100) / 100).toString(); }
    function pdfEsc(s) { return String(s).replace(/[\\()]/g, function (c) { return '\\' + c; }); }

    function buildPdfString(matrix, name) {
        var n = matrix.length;
        var PW = 595.28, PH = 841.89;          // A4 v bodoch
        var qrSize = 440;                       // veľký vycentrovaný kód
        var x0 = (PW - qrSize) / 2;
        var y0 = (PH - qrSize) / 2 + 24;        // mierne hore, miesto na popis dole
        var m = qrSize / n;

        var rects = '';
        for (var r = 0; r < n; r++) {
            for (var c = 0; c < n; c++) {
                if (!matrix[r][c]) { continue; }
                var x = x0 + c * m;
                var y = y0 + (n - 1 - r) * m;   // PDF má y zdola; otoč riadky
                rects += f2(x) + ' ' + f2(y) + ' ' + f2(m + 0.3) + ' ' + f2(m + 0.3) + ' re\n';
            }
        }
        var cap = name;                         // ASCII (slug-typ) — Helvetica WinAnsi
        var capFs = 16;
        var capX = (PW - cap.length * capFs * 0.5) / 2;
        var capY = y0 - 34;
        var content = '0 0 0 rg\n' + rects + 'f\n' +
            'BT /F1 ' + capFs + ' Tf ' + f2(capX) + ' ' + f2(capY) + ' Td (' + pdfEsc(cap) + ') Tj ET\n';

        var objs = [
            '<</Type/Catalog/Pages 2 0 R>>',
            '<</Type/Pages/Kids[3 0 R]/Count 1>>',
            '<</Type/Page/Parent 2 0 R/MediaBox[0 0 595.28 841.89]/Resources<</Font<</F1 5 0 R>>>>/Contents 4 0 R>>',
            '<</Length ' + content.length + '>>\nstream\n' + content + '\nendstream',
            '<</Type/Font/Subtype/Type1/BaseFont/Helvetica/Encoding/WinAnsiEncoding>>'
        ];
        var pdf = '%PDF-1.4\n';
        var offsets = [];
        for (var i = 0; i < objs.length; i++) {
            offsets.push(pdf.length);
            pdf += (i + 1) + ' 0 obj\n' + objs[i] + '\nendobj\n';
        }
        var xref = pdf.length;
        pdf += 'xref\n0 ' + (objs.length + 1) + '\n0000000000 65535 f \n';
        for (var j = 0; j < offsets.length; j++) {
            pdf += ('0000000000' + offsets[j]).slice(-10) + ' 00000 n \n';
        }
        pdf += 'trailer<</Size ' + (objs.length + 1) + '/Root 1 0 R>>\nstartxref\n' + xref + '\n%%EOF';
        return pdf;
    }

    function downloadPDF(matrix, name) {
        triggerDownload(new Blob([buildPdfString(matrix, name)], { type: 'application/pdf' }), name + '.pdf');
    }

    // Verejná API (pre testy / re-use)
    window.EKQR = {
        buildMatrix: buildMatrix,
        matrixToCanvas: matrixToCanvas,
        buildPdfString: buildPdfString,
        downloadPNG: downloadPNG,
        downloadPDF: downloadPDF
    };

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.ek-qr-btn');
        if (!btn) { return; }
        e.preventDefault();
        var url = btn.getAttribute('data-qr-url');
        var name = btn.getAttribute('data-qr-name') || 'qr';
        var fmt = btn.getAttribute('data-qr-fmt');
        var matrix = buildMatrix(url);
        if (!matrix) { alert('QR knižnica sa nenačítala.'); return; }
        if (fmt === 'pdf') { downloadPDF(matrix, name); }
        else { downloadPNG(matrix, name); }
    });
})();
