<?php

namespace App\Services\Reports;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

class StaffReportPdf
{
    public const MAX_RECORDS = 2000;

    public function render(array $data): string
    {
        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setChroot([storage_path('app'), resource_path('fonts')]);
        $options->setTempDir(storage_path('framework/cache'));
        $options->setFontDir(storage_path('framework/cache'));
        $options->setFontCache(storage_path('framework/cache'));
        $pdf = new Dompdf($options);
        foreach (['normal' => 'regular', 'bold' => 'bold'] as $weight => $file) {
            if (! $pdf->getFontMetrics()->registerFont(['family' => 'Inter', 'style' => 'normal', 'weight' => $weight], resource_path('fonts/inter/inter-'.$file.'.ttf'))) {
                throw new RuntimeException('The report font could not be loaded.');
            }
        }
        $pdf->setPaper('a4', count($data['headings']) > 6 ? 'landscape' : 'portrait');
        $pdf->loadHtml(view('reports.pdf', $data)->render());
        $pdf->render();
        $canvas = $pdf->getCanvas();
        $canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics): void {
            $font = $fontMetrics->getFont('Inter');
            $canvas->save();
            $canvas->image(public_path('images/eLINGAP.png'), 22.5, 16, 44, 44);
            $canvas->text(72, 22, 'eLINGAP', $fontMetrics->getFont('Inter', 'bold'), 16, [0, 0, 0]);
            $canvas->text(72, 45, 'Office of the Senior Citizen Affairs', $font, 7, [0, 0, 0]);
            $canvas->text(30, $canvas->get_height() - 25, 'eLINGAP | Page '.$pageNumber.' of '.$pageCount, $font, 8, [0, 0, 0]);
            $canvas->restore();
        });

        return $pdf->output();
    }
}
