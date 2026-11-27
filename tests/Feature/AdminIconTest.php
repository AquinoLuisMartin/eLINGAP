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
    #[TestWith(['arrow-right', 'arrow-right'])]
    #[TestWith(['key-round', 'key-round'])]
    #[TestWith(['log-in', 'log-in'])]
    #[TestWith(['shield-alert', 'shield-alert'])]
    #[TestWith(['user-round-check', 'user-round-check'])]
    #[TestWith(['user-round-minus', 'user-round-minus'])]
    #[TestWith(['user-round-plus', 'user-round-plus'])]
    #[TestWith(['user-round-x', 'user-round-x'])]
    #[TestWith(['id-card', 'id-card'])]
    #[TestWith(['printer', 'printer'])]
    #[TestWith(['printer-check', 'printer-check'])]
    #[TestWith(['printer-x', 'printer-x'])]
    #[TestWith(['unknown-icon', 'circle'])]
    public function test_icon_names_aliases_and_fallback_resolve_to_the_expected_asset(string $name, string $expected): void
    {
        $html = Blade::render('<x-admin.icon :name="$name" />', ['name' => $name]);
        $canonical = Blade::render('<x-icon :name="$name" width="18" height="18" aria-hidden="true" />', ['name' => 'lucide-'.$expected]);

        $this->assertSame(trim($canonical), trim($html));
    }
}
