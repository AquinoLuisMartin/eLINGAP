<?php

namespace Tests\Feature\SeniorCitizens;

use App\Livewire\SeniorCitizens\Index;
use App\Models\Barangay;
use App\Models\Beneficiary;
use App\Models\Program;
use App\Models\SeniorCitizen;
use App\Models\User;
use Database\Seeders\BenefitProgramSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RegistryFiltersTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['barangayId'])]
    #[TestWith(['age'])]
    #[TestWith(['status'])]
    #[TestWith(['programId'])]
    #[TestWith(['benefit'])]
    #[TestWith(['search'])]
    public function test_changing_a_filter_refreshes_records_and_totals_and_resets_the_page(string $property): void
    {
        $this->actingAs(User::factory()->create());
        $barangay = Barangay::create(['name' => 'Synthetic North', 'code' => 'SYN-N']);
        $other = Barangay::create(['name' => 'Synthetic South', 'code' => 'SYN-S']);
        $matching = $this->senior($barangay, ['first_name' => 'Matching', 'birth_date' => today()->subYears(65)]);
        $matching->forceFill(['has_national_pension' => true])->save();
        $this->senior($other, ['first_name' => 'Excluded', 'status' => 'ARCHIVED', 'birth_date' => today()->subYears(85)]);
        $this->seed(BenefitProgramSeeder::class);
        $program = Program::where('benefit_code', 'december-assistance')->sole();
        $program->update(['status' => 'ACTIVE', 'budget' => 1000]);
        Beneficiary::create(['senior_citizen_id' => $matching->id, 'program_id' => $program->id, 'status' => 'ACTIVE', 'assigned_by' => auth()->id(), 'enrolled_on' => today()]);
        $value = match ($property) {
            'barangayId' => (string) $barangay->id, 'age' => '60-69', 'status' => 'VERIFIED',
            'programId' => (string) $program->id, 'benefit' => 'NATIONAL_PENSION', 'search' => 'Matching',
        };

        Livewire::test(Index::class)->call('gotoPage', 2)->set($property, $value)
            ->assertSet('paginators.page', 1)
            ->assertViewHas('seniorCitizens', fn ($records) => $records->modelKeys() === [$matching->id])
            ->assertViewHas('counts', fn ($counts) => $counts['Total registry'] === 1);
    }

    public function test_order_changes_immediately_and_reset_restores_the_unfiltered_registry(): void
    {
        $this->actingAs(User::factory()->create());
        $barangay = Barangay::create(['name' => 'Synthetic North', 'code' => 'SYN-N']);
        $first = $this->senior($barangay);
        $second = $this->senior($barangay, ['status' => 'ARCHIVED']);

        $component = Livewire::test(Index::class)->set('direction', 'asc')
            ->assertViewHas('seniorCitizens', fn ($records) => $records->modelKeys() === [$first->id, $second->id]);
        $component->set('status', 'ARCHIVED')->call('resetFilters')
            ->assertSet('status', '')->assertSet('direction', 'desc')->assertSet('paginators.page', 1)
            ->assertViewHas('seniorCitizens', fn ($records) => $records->modelKeys() === [$second->id, $first->id]);
    }

    public function test_current_filters_survive_navigation_to_a_record(): void
    {
        $this->actingAs(User::factory()->create());
        $barangay = Barangay::create(['name' => 'Synthetic North', 'code' => 'SYN-N']);
        $senior = $this->senior($barangay);

        $component = Livewire::test(Index::class)->set('search', 'Synthetic')->set('status', 'VERIFIED');

        $document = new \DOMDocument;
        @$document->loadHTML($component->html());
        $link = (new \DOMXPath($document))->query('//a[@aria-label="View Synthetic Citizen"]')->item(0);
        $this->assertNotNull($link);
        $this->assertSame(parse_url(route('senior-citizens.show', $senior), PHP_URL_PATH), parse_url($link->getAttribute('href'), PHP_URL_PATH));
        parse_str(parse_url($link->getAttribute('href'), PHP_URL_QUERY), $query);
        $this->assertSame('SYNTHETIC', $query['search']);
        $this->assertSame('VERIFIED', $query['status']);
    }

    public function test_livewire_registry_requires_registry_permission(): void
    {
        $this->actingAs(User::factory()->create());
        Gate::before(fn () => false);

        Livewire::test(Index::class)->assertForbidden();
    }

    #[TestWith(['direction', 'sideways', 'direction'])]
    #[TestWith(['sort', 'id; drop table senior_citizens', 'sort'])]
    #[TestWith(['barangayId', '999999', 'barangay_id'])]
    #[TestWith(['age', 'invalid', 'age'])]
    #[TestWith(['status', 'invalid', 'status'])]
    #[TestWith(['programId', '999999', 'program_id'])]
    #[TestWith(['benefit', 'invalid', 'benefit'])]
    public function test_live_updates_reject_invalid_filter_values(string $property, string $value, string $error): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Index::class)->set($property, $value)->assertHasErrors($error);
    }

    #[TestWith(['sort'])]
    #[TestWith(['direction'])]
    public function test_blank_sort_options_use_the_default_registry_order(string $property): void
    {
        $this->actingAs(User::factory()->create());
        $barangay = Barangay::create(['name' => 'Synthetic North', 'code' => 'SYN-N']);
        $first = $this->senior($barangay);
        $second = $this->senior($barangay);

        Livewire::test(Index::class)->set($property, '')
            ->assertHasNoErrors()
            ->assertViewHas('seniorCitizens', fn ($records) => $records->modelKeys() === [$second->id, $first->id]);
        $this->get(route('senior-citizens.index', [$property => '']))
            ->assertViewHas('seniorCitizens', fn ($records) => $records->modelKeys() === [$second->id, $first->id]);
    }

    public function test_pagination_preserves_filtered_records_and_moves_between_pages(): void
    {
        $this->actingAs(User::factory()->create());
        $barangay = Barangay::create(['name' => 'Synthetic North', 'code' => 'SYN-N']);
        $records = [];
        foreach (range(1, 31) as $number) {
            $records[] = $this->senior($barangay, ['first_name' => 'Synthetic '.$number]);
        }
        $this->senior($barangay, ['status' => 'ARCHIVED']);

        Livewire::test(Index::class)->set('status', 'VERIFIED')->set('direction', 'asc')
            ->assertSeeHtml('aria-label="Senior masterlist pagination"')
            ->assertSeeHtml('aria-current="page" aria-label="Page 1"')
            ->assertViewHas('seniorCitizens', fn ($rows) => $rows->count() === 15 && $rows->total() === 31 && $rows->first()->id === $records[0]->id)
            ->call('nextPage')->assertSet('paginators.page', 2)
            ->assertViewHas('seniorCitizens', fn ($rows) => $rows->count() === 15 && $rows->first()->id === $records[15]->id)
            ->call('gotoPage', 3)->assertSet('status', 'VERIFIED')
            ->assertSeeHtml('aria-current="page" aria-label="Page 3"')
            ->assertViewHas('seniorCitizens', fn ($rows) => $rows->modelKeys() === [$records[30]->id])
            ->call('previousPage')->assertSet('paginators.page', 2)
            ->assertViewHas('seniorCitizens', fn ($rows) => $rows->count() === 15 && $rows->total() === 31 && $rows->first()->id === $records[15]->id);
    }

    public function test_search_normalizes_typed_and_url_values_to_uppercase_and_matches_names(): void
    {
        $this->actingAs(User::factory()->create());
        $barangay = Barangay::create(['name' => 'Synthetic North', 'code' => 'SYN-N']);
        $senior = $this->senior($barangay, ['first_name' => 'Synthetic Ñame']);

        Livewire::withQueryParams(['search' => 'synthetic ñame'])->test(Index::class)
            ->assertSet('search', 'SYNTHETIC ÑAME')
            ->assertViewHas('seniorCitizens', fn ($rows) => $rows->modelKeys() === [$senior->id])
            ->call('gotoPage', 2)->set('search', 'synthetic')
            ->assertSet('search', 'SYNTHETIC')->assertSet('paginators.page', 1)
            ->assertViewHas('seniorCitizens', fn ($rows) => $rows->modelKeys() === [$senior->id]);
    }

    #[TestWith(['asc', [1, 2, 0]])]
    #[TestWith(['desc', [0, 2, 1]])]
    public function test_surname_sorting_preserves_filters_and_resets_pagination(string $direction, array $order): void
    {
        $this->actingAs(User::factory()->create());
        $barangay = Barangay::create(['name' => 'Synthetic North', 'code' => 'SYN-N']);
        $records = [
            $this->senior($barangay, ['last_name' => 'Zulu']),
            $this->senior($barangay, ['last_name' => 'Alpha']),
            $this->senior($barangay, ['last_name' => 'Mike']),
        ];
        $this->senior($barangay, ['status' => 'ARCHIVED']);
        $expected = array_map(fn ($index) => $records[$index]->id, $order);

        Livewire::test(Index::class)->set('status', 'VERIFIED')->call('gotoPage', 2)
            ->call('sortByName', $direction)->assertSet('sort', 'last_name')->assertSet('direction', $direction)
            ->assertSet('status', 'VERIFIED')->assertSet('paginators.page', 1)
            ->assertViewHas('seniorCitizens', fn ($rows) => $rows->modelKeys() === $expected)
            ->assertViewHas('navigationFilters', fn ($filters) => $filters['sort'] === 'last_name' && $filters['direction'] === $direction)
            ->call('sortByName', '')->assertSet('sort', 'id')->assertSet('direction', 'desc')
            ->assertViewHas('seniorCitizens', fn ($rows) => $rows->modelKeys() === [$records[2]->id, $records[1]->id, $records[0]->id]);
    }

    public function test_surname_sorting_rejects_invalid_directions_without_changing_order(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Index::class)->call('sortByName', 'asc')
            ->call('sortByName', 'invalid')->assertHasErrors('direction')
            ->assertSet('sort', 'last_name')->assertSet('direction', 'asc')
            ->call('sortByName', 'desc')->assertHasNoErrors('direction')->assertSet('direction', 'desc');
    }

    private function senior(Barangay $barangay, array $attributes = []): SeniorCitizen
    {
        return SeniorCitizen::create([
            'barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => 'Citizen',
            'birth_date' => today()->subYears(65), 'sex' => 'FEMALE', 'address' => 'Synthetic Street',
            'status' => 'VERIFIED', ...$attributes,
        ]);
    }
}
