<?php

namespace Tests\Feature\SeniorCitizens;

use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Services\SeniorCitizens\IdCardAddress;
use Dompdf\Dompdf;
use Dompdf\Options;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class BatchIdLayoutTest extends TestCase
{
    #[TestWith([215.9, 279.4, 4, 1])]
    #[TestWith([215.9, 330.2, 4, 1])]
    #[TestWith([210.0, 297.0, 4, 1])]
    #[TestWith([210.0, 220.0, 4, 2, 3])]
    #[TestWith([210.0, 190.0, 4, 2, 2])]
    #[TestWith([210.0, 297.0, 1, 1])]
    #[TestWith([210.0, 297.0, 2, 1])]
    #[TestWith([210.0, 297.0, 3, 1])]
    public function test_batch_pairs_stay_horizontal_centered_and_actual_size_across_pages(float $paperWidth, float $paperHeight, int $count, int $pages, int $rowsPerPage = 4): void
    {
        $cards = array_map(fn ($id) => $this->card($id), range(1, $count));
        $options = new Options;
        $options->setTempDir(storage_path('framework/cache'));
        $options->setFontCache(storage_path('framework/cache'));
        $pdf = new Dompdf($options);
        $pdf->setPaper([0, 0, $paperWidth * 72 / 25.4, $paperHeight * 72 / 25.4]);
        $pdf->loadHtml(view('senior-citizens.ids.pdf', ['cards' => $cards, 'printLayout' => 'batch', 'paperHeight' => $paperHeight])->render());
        $boxes = [];
        $pdf->setCallbacks([['event' => 'end_frame', 'f' => function ($frame) use (&$boxes, $pdf): void {
            $node = $frame->get_node();
            if (! $node instanceof \DOMElement || $node->getAttribute('class') !== 'id-card') {
                return;
            }
            for ($row = $node->parentNode; $row instanceof \DOMElement; $row = $row->parentNode) {
                if ($row->hasAttribute('data-senior-id')) {
                    $boxes[(int) $row->getAttribute('data-senior-id')][$node->getAttribute('aria-label')] = [
                        ...$frame->get_border_box(), 'page' => $pdf->getCanvas()->get_page_number(),
                    ];
                    break;
                }
            }
        }]]);

        $pdf->render();

        $this->assertSame($pages, $pdf->getCanvas()->get_page_count());
        $this->assertCount($count, $boxes);
        $previous = null;
        foreach (range(1, $count) as $id) {
            $this->assertCount(2, $boxes[$id]);
            $front = $boxes[$id]['Front of senior citizen ID'];
            $back = $boxes[$id]['Back of senior citizen ID'];
            $this->assertSame((int) ceil($id / $rowsPerPage), $front['page']);
            $this->assertSame($front['page'], $back['page']);
            $this->assertEqualsWithDelta($front['y'], $back['y'], 0.02);
            $this->assertEqualsWithDelta(4 * 72 / 25.4, $back['x'] - $front['x'] - $front['w'], 0.02);
            $this->assertEqualsWithDelta(($paperWidth - 175.2) / 2 * 72 / 25.4, $front['x'], 0.02);
            foreach ([$front, $back] as $side) {
                $this->assertEqualsWithDelta(85.6 * 72 / 25.4, $side['w'], 0.02);
                $this->assertEqualsWithDelta(53.98 * 72 / 25.4, $side['h'], 0.02);
                $this->assertGreaterThanOrEqual(12 * 72 / 25.4, $side['y']);
                $this->assertLessThanOrEqual(($paperHeight - 12) * 72 / 25.4, $side['y'] + $side['h']);
            }
            if ($previous && $previous['page'] === $front['page']) {
                $this->assertEqualsWithDelta(4 * 72 / 25.4, $front['y'] - $previous['y'] - $previous['h'], 0.02);
            }
            $previous = $front;
        }
    }

    private function card(int $id): array
    {
        $senior = new SeniorCitizen;
        $senior->forceFill(['id' => $id, 'first_name' => 'Synthetic', 'last_name' => 'Applicant '.$id,
            'birth_date' => '1950-04-18', 'sex' => 'FEMALE', 'contact_number' => '+639170000000',
            'osca_id_number' => '26-000'.$id, 'id_generated_at' => '2026-10-15']);
        $senior->setRelation('barangay', new Barangay(['name' => 'Synthetic Barangay']));
        $image = 'data:image/jpeg;base64,'.base64_encode(file_get_contents(base_path('tests/Fixtures/id-photo.jpg')));

        return ['senior' => $senior, 'draft' => false, 'age' => 76, 'logo' => $image, 'qr' => $image,
            'address' => app(IdCardAddress::class)->format('Synthetic street', 'Synthetic Barangay', ['municipality' => 'Santa Maria', 'province' => 'Bulacan']),
            'office' => ['office_name' => 'Synthetic office', 'organization_name' => 'Municipality of Santa Maria'],
            'headSignature' => null, 'mayorSignature' => null,
            'officials' => ['osca_head_name' => 'Synthetic Head', 'municipal_mayor_name' => 'Synthetic Mayor']];
    }
}
