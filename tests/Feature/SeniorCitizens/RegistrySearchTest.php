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
    #[TestWith(['registration', 'SYN-A', [0]])]
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
        $first = SeniorCitizen::create([...$base, 'registration_number' => 'SYN-A', 'first_name' => 'Synthetic', 'middle_name' => 'Middle', 'last_name' => 'Citizen', 'osca_id_number' => '26-10004']);
        $second = SeniorCitizen::create([...$base, 'registration_number' => 'SYN-B', 'first_name' => 'Different', 'middle_name' => 'Other', 'last_name' => 'Record', 'osca_id_number' => '26-100040']);
        $ids = [$first->id, $second->id];
        $expected = array_map(fn ($index) => $ids[$index], $matches);

        $this->get(route('senior-citizens.index', ['search_by' => $field, 'search' => $term]))
            ->assertViewHas('seniorCitizens', fn ($records) => $records->modelKeys() === $expected);
        Livewire::test(Index::class)->set('searchBy', $field)->set('search', $term)
            ->assertViewHas('registrations', fn ($records) => $records->modelKeys() === $expected);
    }

    public function test_registry_rejects_an_unknown_search_field(): void
    {
        $this->actingAs(User::factory()->create())->getJson(route('senior-citizens.index', ['search_by' => 'address; drop table senior_citizens', 'search' => 'Synthetic']))
            ->assertUnprocessable()->assertJsonValidationErrors('search_by');
    }

    public function test_registrations_reject_an_unknown_search_field(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Index::class)->set('searchBy', 'address')->assertHasErrors('searchBy');
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
