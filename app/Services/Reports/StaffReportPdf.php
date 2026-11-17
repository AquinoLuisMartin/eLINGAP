<?php

namespace App\Services\Reports;

use Dompdf\Dompdf;
use Dompdf\Options;

class StaffReportPdf
{
    public const MAX_RECORDS = 2000;

    public function render(array $data): string
    {
        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setChroot(storage_path('app'));
        $options->setTempDir(storage_path('framework/cache'));
        $options->setFontCache(storage_path('framework/cache'));
        $pdf = new Dompdf($options);
        $pdf->setPaper('a4', count($data['headings']) > 6 ? 'landscape' : 'portrait');
        $pdf->loadHtml(view('reports.pdf', $data)->render());
        $pdf->render();
        $canvas = $pdf->getCanvas();
        $font = $pdf->getFontMetrics()->getFont('DejaVu Sans');
        $canvas->page_text(22.5, 22, 'eLINGAP', $pdf->getFontMetrics()->getFont('DejaVu Sans', 'bold'), 16, [0, 0.196, 0.49]);
        $canvas->page_text(22.5, 45, 'Office of the Senior Citizen Affairs', $font, 7, [0.32, 0.38, 0.45]);
        $canvas->page_text(30, $canvas->get_height() - 25, 'eLINGAP | Page {PAGE_NUM} of {PAGE_COUNT}', $font, 8, [0.35, 0.4, 0.45]);

        return $pdf->output();
    }
}
