<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SelectComponentTest extends TestCase
{
    public function test_native_fallback_preserves_submission_validation_and_option_metadata(): void
    {
        $html = Blade::render('<x-select label="Program" id="program" name="program_id" required aria-describedby="program-help"><option value="">Select program</option><option value="42" selected data-body="Synthetic template">Synthetic assistance</option><option value="other" disabled>Other</option></x-select>');
        $document = new DOMDocument;
        @$document->loadHTML($html);
        $xpath = new DOMXPath($document);
        $select = $xpath->query('//select')->item(0);

        $this->assertSame('program_id', $select->getAttribute('name'));
        $this->assertSame('program', $select->getAttribute('id'));
        $this->assertTrue($select->hasAttribute('required'));
        $this->assertFalse($select->hasAttribute('disabled'));
        $this->assertSame('program-help', $select->getAttribute('aria-describedby'));
        $this->assertSame('42', $xpath->query('//select/option[@selected]')->item(0)->getAttribute('value'));
        $this->assertSame('Synthetic template', $xpath->query('//select/option[@value="42"]')->item(0)->getAttribute('data-body'));
        $this->assertTrue($xpath->query('//select/option[@value="other"]')->item(0)->hasAttribute('disabled'));
    }

    public function test_live_and_deferred_bindings_remain_on_the_native_select(): void
    {
        foreach (['wire:model.live', 'wire:model'] as $binding) {
            $html = Blade::render('<x-select '.$binding.'="form.program_id"><option value="other">Other</option></x-select>');
            $document = new DOMDocument;
            @$document->loadHTML($html);
            $select = $document->getElementsByTagName('select')->item(0);

            $this->assertSame('form.program_id', $select->getAttribute($binding));
        }
    }

    public function test_alpine_required_binding_is_forwarded_without_php_evaluation(): void
    {
        $html = Blade::render('<x-select name="barangay_id" ::required="type === \'INTERNAL\'"><option value="">Select barangay</option></x-select>');
        $document = new DOMDocument;
        @$document->loadHTML($html);

        $this->assertSame("type === 'INTERNAL'", $document->getElementsByTagName('select')->item(0)->getAttribute(':required'));
    }

    public function test_server_validation_marks_the_underlying_field_invalid(): void
    {
        $this->withViewErrors(['program_id' => 'Choose a program.']);
        $html = Blade::render('<x-select name="program_id"><option value="">Select program</option></x-select>');
        $document = new DOMDocument;
        @$document->loadHTML($html);

        $this->assertSame('true', $document->getElementsByTagName('select')->item(0)->getAttribute('aria-invalid'));
    }
}
