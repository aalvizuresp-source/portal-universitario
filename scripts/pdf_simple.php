<?php
/**
 * pdf_simple.php — genera un PDF mínimo válido (una página, una línea de texto)
 * con PHP puro, para los documentos de ejemplo de la carga inicial.
 */

declare(strict_types=1);

function pdf_simple(string $texto): string
{
    // Texto en WinAnsi (Latin-1) con los caracteres especiales de PDF escapados.
    $t = iconv('UTF-8', 'Windows-1252//TRANSLIT', $texto) ?: $texto;
    $t = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $t);
    $stream = "BT /F1 14 Tf 60 760 Td ($t) Tj ET";

    $objs = [
        "<< /Type /Catalog /Pages 2 0 R >>",
        "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
        "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R "
            . "/Resources << /Font << /F1 5 0 R >> >> >>",
        "<< /Length " . strlen($stream) . " >>\nstream\n$stream\nendstream",
        "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>",
    ];

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objs as $i => $o) {
        $offsets[] = strlen($pdf);
        $pdf .= ($i + 1) . " 0 obj\n$o\nendobj\n";
    }
    $xref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
    foreach ($offsets as $off) {
        $pdf .= sprintf("%010d 00000 n \n", $off);
    }
    $pdf .= "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
    return $pdf;
}
