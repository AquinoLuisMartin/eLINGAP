<?php

namespace Tests\Feature\SeniorCitizens;

use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Services\SeniorCitizens\IdCardAddress;
use Dompdf\Dompdf;
use Dompdf\Options;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class IdCardLayoutTest extends TestCase
{
    public function test_printed_address_keeps_the_complete_street_without_repeating_the_locality(): void
    {
        $address = app(IdCardAddress::class)->format('Unit 20, Sample Street, sample barangay, Santa Maria, Bulacan', 'Sample Barangay');

        $this->assertTrue($address['fits']);
        $this->assertSame('Unit 20, Sample Street, Sample Barangay, Santa Maria, Bulacan', implode(' ', $address['lines']));
        $this->assertGreaterThanOrEqual(4.5, $address['fontSize']);
    }

    #[TestWith(['Sample Middle Citizen', '123 Sample Street', false])]
    #[TestWith(['Sample Additional Middle Family Citizen', '123 Sample Street', false])]
    #[TestWith(['Sample Given Names With Several Middle Names And An Extended Family Name', 'Unit 20, Block 10, Sample Residences, Sample Subdivision, Sample Road', true])]
    #[TestWith(['Sample Given Names With Several Additional Middle Names And An Extended Family Name With Additional Family Names', 'Unit 20, Block 10, Sample Residences, Sample Subdivision, Sample Road, Near the Sample Community Center', true])]
    #[TestWith(['Sample Middle Citizen', 'Unit 20 Block 10, Sample Residences, Sample Subdivision, Sample Road, Near the Sample Community Center and Municipal Health Center', false])]
    public function test_printed_details_do_not_overlap_or_escape_the_card(string $name, string $address, bool $signatures): void
    {
        $senior = new SeniorCitizen;
        $senior->forceFill(['first_name' => $name, 'last_name' => '', 'address' => $address,
            'birth_date' => '1950-04-18', 'sex' => 'FEMALE', 'contact_number' => '+639170000000',
            'osca_id_number' => '26-0001', 'id_generated_at' => '2026-10-15']);
        $senior->setRelation('barangay', new Barangay(['name' => 'Sample Barangay']));
        $image = 'data:image/jpeg;base64,'.base64_encode(file_get_contents(base_path('tests/Fixtures/id-photo.jpg')));
        $card = ['senior' => $senior, 'draft' => false, 'age' => 76, 'seal' => $image, 'qr' => $image,
            'address' => app(IdCardAddress::class)->format($address, 'Sample Barangay'),
            'headSignature' => $signatures ? $image : null, 'mayorSignature' => $signatures ? $image : null,
            'officials' => ['osca_head_name' => 'Sample OSCA Head', 'municipal_mayor_name' => 'Sample Municipal Mayor']];
        $this->assertTrue($card['address']['fits']);
        $this->assertSame($card['address']['text'], implode(' ', $card['address']['lines']));
        $options = new Options;
        $options->setTempDir(storage_path('framework/cache'));
        $options->setFontCache(storage_path('framework/cache'));
        $pdf = new Dompdf($options);
        $pdf->setPaper('a4');
        $pdf->loadHtml(view('senior-citizens.ids.pdf', ['cards' => [$card]])->render());
        $boxes = [];
        $pdf->setCallbacks([['event' => 'end_frame', 'f' => function ($frame) use (&$boxes): void {
            $node = $frame->get_node();
            if ($node instanceof \DOMElement) {
                $key = explode(' ', $node->getAttribute('class'))[0];
                if ($key === 'id-card') {
                    $key = $node->getAttribute('aria-label');
                }
                $boxes[$key] = $frame->get_border_box();
            }
        }]]);

        $pdf->render();

        foreach ([['id-number', 'id-photo'], ['id-photo', 'id-personal'], ['id-heading', 'id-personal'], ['id-personal', 'id-address'],
            ['id-seal', 'id-contact'], ['id-heading', 'id-contact'], ['id-contact', 'id-personal'], ['id-address', 'id-dates'], ['address', 'id-dates'], ['id-dates', 'id-validity'], ['id-validity', 'id-thumbprint'],
            ['id-birth', 'id-validity'], ['id-age', 'id-validity'], ['id-sex', 'id-validity'], ['id-issuance', 'id-validity'], ['id-holder-name', 'id-thumbprint-label'],
            ['id-back-heading', 'id-benefits'], ['id-benefits', 'id-warning'], ['id-benefits', 'id-qr'],
            ['id-warning', 'id-officials'], ['id-qr', 'id-qr-label']] as [$above, $below]) {
            $this->assertLessThanOrEqual($boxes[$below]['y'], $boxes[$above]['y'] + $boxes[$above]['h'], $above.' overlaps '.$below);
        }
        foreach ([['id-seal', 'id-heading'], ['id-heading', 'id-photo'], ['id-contact', 'id-photo'], ['id-contact-label', 'id-contact-value'], ['id-birth', 'id-age'], ['id-age', 'id-sex'], ['id-sex', 'id-issuance'], ['id-officials', 'id-qr']] as [$left, $right]) {
            $this->assertLessThanOrEqual($boxes[$right]['x'], $boxes[$left]['x'] + $boxes[$left]['w'], $left.' overlaps '.$right);
        }
        foreach (['Front of senior citizen ID' => ['id-seal', 'id-heading', 'id-photo', 'id-personal', 'id-address', 'id-contact', 'id-contact-label', 'id-contact-value', 'id-birth', 'id-age', 'id-sex', 'id-issuance', 'id-number', 'id-thumbprint', 'id-holder-name', 'id-thumbprint-label', 'id-validity'],
            'Back of senior citizen ID' => ['id-back-heading', 'id-benefits', 'id-warning', 'id-officials', 'id-qr', 'id-qr-label']] as $side => $elements) {
            $outer = $boxes[$side];
            foreach ($elements as $element) {
                $box = $boxes[$element];
                $this->assertGreaterThanOrEqual($outer['x'], $box['x'], $element.' exceeds the left edge');
                $this->assertGreaterThanOrEqual($outer['y'], $box['y'], $element.' exceeds the top edge');
                $this->assertLessThanOrEqual($outer['x'] + $outer['w'], $box['x'] + $box['w'], $element.' exceeds the right edge');
                $this->assertLessThanOrEqual($outer['y'] + $outer['h'], $box['y'] + $box['h'], $element.' exceeds the bottom edge');
            }
        }
    }
}
