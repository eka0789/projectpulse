<?php

namespace App\Support;

final class SimplePdf
{
    /**
     * @param  list<string>  $lines
     */
    public static function fromLines(array $lines): string
    {
        $pages = array_chunk($lines, 48) ?: [[]];
        $objectCount = 3 + (count($pages) * 2);
        $objects = array_fill(0, $objectCount + 1, '');
        $pageObjectIds = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        foreach ($pages as $index => $pageLines) {
            $pageId = 4 + ($index * 2);
            $contentId = $pageId + 1;
            $pageObjectIds[] = "{$pageId} 0 R";
            $content = self::contentStream($pageLines);
            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R >> >> /Contents {$contentId} 0 R >>";
            $objects[$contentId] = '<< /Length '.strlen($content)." >>\nstream\n{$content}\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids ['.implode(' ', $pageObjectIds).'] /Count '.count($pages).' >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        for ($id = 1; $id <= $objectCount; $id++) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$objects[$id]}\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".($objectCount + 1)."\n0000000000 65535 f \n";
        for ($id = 1; $id <= $objectCount; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }

        return $pdf."trailer\n<< /Size ".($objectCount + 1)." /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    /**
     * @param  list<string>  $lines
     */
    private static function contentStream(array $lines): string
    {
        $commands = ['BT', '/F1 10 Tf', '42 750 Td', '14 TL'];
        foreach ($lines as $line) {
            $safe = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], mb_strimwidth($line, 0, 100, '...'));
            $commands[] = "({$safe}) Tj";
            $commands[] = 'T*';
        }
        $commands[] = 'ET';

        return implode("\n", $commands);
    }
}
