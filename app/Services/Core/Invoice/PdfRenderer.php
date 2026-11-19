<?php

namespace App\Services\Core\Invoice;

use Mpdf\Mpdf;
use Mpdf\Output\Destination;

/**
 * Turns the HTML of a fiscal document into PDF bytes with mPDF.
 */
class PdfRenderer
{
    public function render(string $html, string $title, string $creator): string
    {
        $tempDir = storage_path('app/mpdf');
        is_dir($tempDir) || mkdir($tempDir, 0775, true);

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 14,
            'margin_right' => 14,
            'margin_top' => 14,
            'margin_bottom' => 24,
            'tempDir' => $tempDir,
            'default_font' => 'dejavusans',
        ]);
        $mpdf->SetTitle($title);
        $mpdf->SetCreator($creator);
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}
