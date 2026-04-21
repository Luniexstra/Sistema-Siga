<?php

namespace App\Support;

class SimplePdf
{
    public static function fromLines(array $lines): string
    {
        $content = self::buildContentStream($lines);

        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    protected static function buildContentStream(array $lines): string
    {
        $stream = "BT\n/F1 12 Tf\n50 790 Td\n14 TL\n";

        foreach ($lines as $index => $line) {
            $escapedLine = self::escapeText($line);

            if ($index === 0) {
                $stream .= "({$escapedLine}) Tj\n";
                continue;
            }

            $stream .= "T*\n({$escapedLine}) Tj\n";
        }

        $stream .= "ET";

        return $stream;
    }

    protected static function escapeText(string $text): string
    {
        $sanitized = iconv('UTF-8', 'windows-1252//TRANSLIT//IGNORE', $text);
        $sanitized = $sanitized === false ? $text : $sanitized;

        return str_replace(
            ['\\', '(', ')', "\r", "\n"],
            ['\\\\', '\(', '\)', '', ' '],
            $sanitized
        );
    }
}
