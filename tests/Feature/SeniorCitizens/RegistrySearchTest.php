<?php

namespace Tests\Feature\SeniorCitizens;

use App\Livewire\Registrations\IdApplication;
use App\Livewire\Registrations\Index;
use App\Models\Barangay;
use App\Models\SeniorCitizen;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RegistrySearchTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['osca_id', '26-10004', [0]])]
    #[TestWith(['osca_id', '26-1000', []])]
    #[TestWith(['name', 'Citizen, Synthetic Middle', [0]])]
    #[TestWith(['name', 'Synthetic Other', []])]
    #[TestWith(['name', '%', []])]
    #[TestWith(['name', '_', []])]
    #[TestWith(['all', '26-10004', [1, 0]])]
    #[TestWith(['all', 'Synthetic Citizen', [0]])]
    public function test_registry_and_registrations_distinguish_identifiers_from_names(string $field, string $term, array $matches): void
    {
        $this->actingAs(User::factory()->create());
        $barangay = Barangay::create(['name' => 'Synthetic Barangay', 'code' => 'SYN']);
        $base = ['barangay_id' => $barangay->id, 'birth_date' => '1950-01-01', 'sex' => 'FEMALE', 'address' => 'Synthetic Street', 'status' => 'VERIFIED'];
        $first = SeniorCitizen::create([...$base, 'first_name' => 'Synthetic', 'middle_name' => 'Middle', 'last_name' => 'Citizen', 'osca_id_number' => '26-10004']);
        $second = SeniorCitizen::create([...$base, 'first_name' => 'Different', 'middle_name' => 'Other', 'last_name' => 'Record', 'osca_id_number' => '26-100040']);
        $ids = [$first->id, $second->id];
        $expected = array_map(fn ($index) => $ids[$index], $matches);

        $this->get(route('senior-citizens.index', ['search_by' => $field, 'search' => $term]))
            ->assertSee('OSCA ID No.')->assertDontSee('Registration No.')
            ->assertViewHas('seniorCitizens', fn ($records) => $records->modelKeys() === $expected);
        Livewire::test(Index::class)->set('searchBy', $field)->set('search', $term)
            ->assertDontSee('Registration No.')
            ->assertViewHas('registrations', fn ($records) => $records->modelKeys() === $expected);
    }

    #[TestWith(['26-10004', '26-10004'])]
    #[TestWith([null, 'Not issued'])]
    public function test_registry_and_profile_display_the_osca_number_or_issuance_status(?string $oscaId, string $expected): void
    {
        $this->actingAs(User::factory()->create());
        $barangay = Barangay::create(['name' => 'Synthetic Barangay', 'code' => 'SYN']);
        $senior = SeniorCitizen::create([
            'barangay_id' => $barangay->id, 'first_name' => 'Synthetic', 'last_name' => 'Applicant',
            'birth_date' => '1950-01-01', 'sex' => 'FEMALE', 'address' => 'Synthetic Street',
            'status' => 'VERIFIED', 'osca_id_number' => $oscaId,
        ]);

        $this->get(route('senior-citizens.index'))->assertSee($expected)->assertDontSee('Registration No.');
        $this->get(route('senior-citizens.show', $senior))->assertSee($expected)->assertDontSee('Registration number');
        Livewire::test(Index::class)->assertSee($expected)->assertDontSee('Registration No.');
    }

    #[TestWith(['address; drop table senior_citizens'])]
    #[TestWith(['registration'])]
    public function test_registry_rejects_an_unknown_search_field(string $field): void
    {
        $this->actingAs(User::factory()->create())->getJson(route('senior-citizens.index', ['search_by' => $field, 'search' => 'Synthetic']))
            ->assertUnprocessable()->assertJsonValidationErrors('search_by');
    }

    #[TestWith(['address'])]
    #[TestWith(['registration'])]
    public function test_registrations_reject_an_unknown_search_field(string $field): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Index::class)->set('searchBy', $field)->assertHasErrors('searchBy');
    }

    public function test_registration_search_mode_resets_pagination_and_survives_record_navigation(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::withQueryParams(['page' => 3])->test(Index::class)->set('searchBy', 'osca_id')->assertSet('paginators.page', 1);
        $this->get(route('senior-citizens.create', ['search_by' => 'osca_id', 'search' => '26-10004']))->assertSee('New ID Application');
        Livewire::withQueryParams(['search_by' => 'osca_id', 'search' => '26-10004'])->test(IdApplication::class)
            ->call('cancel')->assertRedirect(route('senior-citizens.index', ['search' => '26-10004', 'search_by' => 'osca_id']));
    }
}
