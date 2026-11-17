<?php

namespace Tests\Feature;

use DOMDocument;
use Illuminate\Support\Facades\Blade;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AdminIconTest extends TestCase
{
    public function test_existing_icon_names_render_without_missing_assets(): void
    {
        $names = explode(' ', 'info map-pin layout-dashboard users heart-pulse message-square user-cog clipboard-list settings circle-help chevron-down chevron-right chevron-left menu search calendar-days bell moon sun check x plus pencil download sliders-horizontal more-horizontal arrow-up-right arrow-down-right activity bar-chart-3 send alert-triangle shield-check sparkles gauge file-text file-check-2 archive log-out clock-3');

        foreach ($names as $name) {
            $html = Blade::render('<x-admin.icon :name="$name" />', ['name' => $name]);

            $this->assertStringContainsString('<svg', $html, $name);
        }
    }

    public function test_size_classes_and_interaction_attributes_reach_the_svg(): void
    {
        $html = Blade::render('<x-admin.icon name="shield-check" size="24" class="size-6 text-blue-600" id="security-icon" data-icon="security" wire:loading />');
        $document = new DOMDocument;
        @$document->loadHTML($html);
        $icon = $document->getElementsByTagName('svg')->item(0);

        $this->assertSame('24', $icon->getAttribute('width'));
        $this->assertSame('24', $icon->getAttribute('height'));
        $this->assertSame('size-6 text-blue-600', $icon->getAttribute('class'));
        $this->assertSame('security-icon', $icon->getAttribute('id'));
        $this->assertSame('security', $icon->getAttribute('data-icon'));
        $this->assertSame('true', $icon->getAttribute('aria-hidden'));
        $this->assertTrue($icon->hasAttribute('wire:loading'));
    }

    #[TestWith(['dashboard', 'layout-dashboard'])]
    #[TestWith(['help', 'circle-help'])]
    #[TestWith(['unknown-icon', 'circle'])]
    public function test_legacy_aliases_and_unknown_names_keep_their_fallback(string $name, string $expected): void
    {
        $html = Blade::render('<x-admin.icon :name="$name" />', ['name' => $name]);
        $canonical = Blade::render('<x-icon :name="$name" width="18" height="18" aria-hidden="true" />', ['name' => 'lucide-'.$expected]);

        $this->assertSame(trim($canonical), trim($html));
    }
}
