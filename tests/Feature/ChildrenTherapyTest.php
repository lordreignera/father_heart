<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\TherapyChild;
use App\Models\TherapyService;
use App\Models\TherapyVisit;
use App\Support\TherapyCatalogues;
use App\Models\User;
use App\Support\TherapyOldInput;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TherapyDisabilityOptionsSeeder;
use Database\Seeders\TherapyMusicOptionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChildrenTherapyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User { $this->seed(RoleSeeder::class); return User::factory()->create()->assignRole('Super Administrator'); }

    public function test_child_can_be_added_by_first_and_last_name_and_linked_to_the_registry(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.therapy.children.create'))->assertOk()->assertSee('Search for the child by name or phone')->assertSee('New child’s first name')->assertSee('New child’s last name')->assertDontSee('name="person_mode"', false)->assertDontSee('name="confirm_same_name"', false);
        $this->post(route('admin.therapy.children.store'), ['first_name' => 'Jane', 'last_name' => 'Doe', 'registered_on' => '2026-11-17', 'registration_data' => ['gender' => 'female']])->assertRedirect()->assertSessionHasNoErrors();
        $person = Person::where('full_name', 'Jane Doe')->firstOrFail();
        $this->assertDatabaseHas('therapy_children', ['person_id' => $person->id]);
    }

    public function test_same_name_can_be_created_but_requires_permission_to_create_people(): void
    {
        $admin = $this->admin();
        Person::create(['full_name' => 'Jane Doe']);
        $data = ['first_name' => 'Jane', 'last_name' => 'Doe', 'registered_on' => '2026-11-17', 'registration_data' => ['gender' => 'female']];
        $this->actingAs($admin)->post(route('admin.therapy.children.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(2, Person::where('full_name', 'Jane Doe')->count());
        $limited = User::factory()->create()->givePermissionTo(['access-admin', 'route-admin-people-index', 'route-admin-therapy-children-store']);
        $this->actingAs($limited)->post(route('admin.therapy.children.store'), $data)->assertForbidden();
    }

    public function test_existing_person_selection_cannot_be_mixed_with_a_new_name(): void
    {
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Existing Child']);
        $data = ['person_id' => $person->id, 'first_name' => 'Other', 'last_name' => 'Child', 'registered_on' => '2026-11-17', 'registration_data' => ['gender' => 'female']];
        $this->actingAs($admin)->post(route('admin.therapy.children.store'), $data)->assertSessionHasErrors('person_id');
        $this->assertDatabaseCount('therapy_children', 0);
        unset($data['first_name'], $data['last_name']);
        $this->post(route('admin.therapy.children.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('therapy_children', ['person_id' => $person->id]);
    }

    public function test_invalid_child_registration_does_not_create_person(): void
    {
        $this->actingAs($this->admin())->post(route('admin.therapy.children.store'), ['person_mode' => 'new', 'first_name' => 'Jane', 'last_name' => 'Doe', 'registered_on' => 'invalid', 'registration_data' => []])->assertSessionHasErrors('registered_on');
        $this->assertDatabaseMissing('people', ['full_name' => 'Jane Doe']);
    }

    public function test_therapy_gender_allows_only_new_male_or_female_but_preserves_existing_legacy_value(): void
    {
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Example Child']);
        $data = ['person_id' => $person->id, 'registered_on' => '2026-11-17', 'registration_data' => ['gender' => 'other']];
        $this->actingAs($admin)->post(route('admin.therapy.children.store'), $data)->assertSessionHasErrors('registration_data.gender');
        $child = TherapyChild::create(['person_id' => $person->id, 'registered_on' => '2026-11-17', 'registration_data' => ['gender' => 'other']]);
        $this->get(route('admin.therapy.children.edit', $child))->assertOk()->assertSee('Previously recorded value (unchanged)')->assertDontSee('Prefer not to say');
        $this->put(route('admin.therapy.children.update', $child), $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('other', $child->fresh()->registration_data['gender']);
        $data['registration_data']['gender'] = 'not_recorded';
        $this->put(route('admin.therapy.children.update', $child), $data)->assertSessionHasErrors('registration_data.gender');
    }

    public function test_form_links_existing_people_stores_registration_privately_and_prevents_duplicate_links(): void
    {
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Example Child', 'phone' => '0700123456']);
        $this->actingAs($admin)->get(route('admin.therapy.children.create', ['person_search' => 'Example']))->assertOk()->assertSee('Example Child')->assertSee('Step by step');
        $data = ['person_id' => $person->id, 'registered_on' => '2026-11-17', 'date_of_birth' => '2016-05-01', 'registration_data' => [
            'allergies' => 'PRIVATE-ALLERGY', 'consent_internal_media' => 'yes', 'consent_external_media' => 'no',
            'guardians' => [['name' => 'Example Guardian', 'primary_phone' => '0700123456']], 'siblings' => [['name' => 'Sibling', 'school' => 'Example School']],
        ]];
        $this->post(route('admin.therapy.children.store'), $data)->assertRedirect();
        $child = TherapyChild::firstOrFail();
        $this->assertSame('PRIVATE-ALLERGY', $child->registration_data['allergies']);
        $this->assertStringNotContainsString('PRIVATE-ALLERGY', DB::table('therapy_children')->where('id', $child->id)->value('registration_data'));
        $this->get(route('admin.therapy.children.show', $child))->assertOk()->assertSee('Example Guardian')->assertSee('PRIVATE-ALLERGY')->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $this->post(route('admin.therapy.children.store'), $data)->assertSessionHasErrors('person_id');
        $this->delete(route('admin.people.destroy', $person))->assertSessionHasErrors('person');
        $this->assertNotSoftDeleted($person);
        $this->assertSame(1, TherapyChild::count());
    }

    public function test_visit_can_have_multiple_services_and_reports_exclude_missed_visits_from_completed_counts(): void
    {
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Example Child']);
        $child = TherapyChild::create(['person_id' => $person->id, 'registered_on' => '2026-11-17', 'registration_data' => []]);
        $services = TherapyService::whereIn('name', ['Music', 'Physio'])->pluck('id')->all();
        $this->actingAs($admin)->post(route('admin.therapy.visits.store', $child), ['visited_on' => '2026-11-17', 'status' => 'completed', 'services' => $services, 'notes' => 'PRIVATE-NOTES'])->assertRedirect();
        $visit = TherapyVisit::firstOrFail();
        $this->assertCount(2, $visit->services);
        $this->assertStringNotContainsString('PRIVATE-NOTES', DB::table('therapy_visits')->where('id', $visit->id)->value('notes'));
        $this->post(route('admin.therapy.visits.store', $child), ['visited_on' => '2026-11-18', 'status' => 'missed', 'services' => [$services[0]]])->assertRedirect();
        $this->get(route('admin.therapy.reports.index'))->assertOk()->assertViewHas('visitsCount', 1)->assertViewHas('childrenCount', 1)->assertSee('Example Child');
        $this->get(route('admin.therapy.reports.index'))->assertViewHas('serviceCounts', fn ($counts) => $counts->count() === 2 && $counts->sum('visit_count') === 2);
        $response = $this->get(route('admin.therapy.reports.export', ['status' => 'completed']));
        $response->assertOk(); $csv = $response->streamedContent();
        $this->assertStringContainsString('Music', $csv); $this->assertStringContainsString('Physio', $csv); $this->assertStringNotContainsString('PRIVATE-NOTES', $csv);
    }

    public function test_permissions_block_direct_access_and_service_deactivation_preserves_existing_visit(): void
    {
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Example Child']);
        $child = TherapyChild::create(['person_id' => $person->id, 'registered_on' => now(), 'registration_data' => []]);
        $service = TherapyService::where('name', 'Music')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.therapy.visits.store', $child), ['visited_on' => '2026-11-17', 'status' => 'completed', 'services' => [$service->id]])->assertRedirect();
        $visit = TherapyVisit::firstOrFail();
        $this->put(route('admin.therapy.services.update', $service), ['name' => 'Renamed music', 'category' => 'therapy', 'is_active' => 1])->assertSessionHasErrors('name');
        $this->put(route('admin.therapy.services.update', $service), ['name' => 'Music', 'category' => 'therapy', 'is_active' => 0])->assertRedirect();
        $this->assertSame('Music', $visit->fresh()->services->first()->name);
        $user = User::factory()->create()->givePermissionTo('access-admin');
        $this->actingAs($user)->get(route('admin.therapy.children.show', $child))->assertForbidden();
        $this->get(route('admin.therapy.visits.show', $visit))->assertForbidden();
        $this->get(route('admin.therapy.reports.export'))->assertForbidden();
    }

    public function test_documents_are_private_and_download_requires_both_document_and_child_access(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Example Child']);
        $child = TherapyChild::create(['person_id' => $person->id, 'registered_on' => now(), 'registration_data' => []]);
        $this->actingAs($admin)->post(route('admin.therapy.documents.store', $child), [
            'category' => 'signed_consent', 'document' => UploadedFile::fake()->create('consent.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $document = $child->documents()->firstOrFail();
        Storage::disk('local')->assertExists($document->path);
        $user = User::factory()->create()->givePermissionTo(['access-admin', 'route-admin-therapy-documents-download']);
        $this->actingAs($user)->get(route('admin.therapy.documents.download', $document))->assertForbidden();
        $user->givePermissionTo('route-admin-therapy-children-show');
        $this->get(route('admin.therapy.documents.download', $document))->assertOk()->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    }

    public function test_registration_options_are_configurable_and_shown_as_suggestions(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.therapy.catalogues.store', 'mobility'), ['name' => 'Custom mobility', 'is_active' => 1])->assertRedirect();
        $this->get(route('admin.therapy.catalogues.index', 'mobility'))->assertOk()->assertSee('Custom mobility');
        $this->get(route('admin.therapy.children.create'))->assertOk()->assertSee('Custom mobility');
    }

    public function test_music_options_are_visible_and_seeder_preserves_inactive_and_custom_options(): void
    {
        $admin = $this->admin();
        DB::table('therapy_previous_therapies')->where('name', 'Music therapy')->update(['is_active' => false]);
        DB::table('therapy_frequencies')->insert(['name' => 'Custom schedule', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $before = DB::table('therapy_frequencies')->count();
        $this->seed(TherapyMusicOptionsSeeder::class);
        $this->seed(TherapyMusicOptionsSeeder::class);
        $this->assertSame($before, DB::table('therapy_frequencies')->count());
        $this->assertDatabaseHas('therapy_previous_therapies', ['name' => 'Music therapy', 'is_active' => false]);
        $this->actingAs($admin)->get(route('admin.therapy.children.create'))->assertOk()
            ->assertSee('name="registration_data[previous_therapy]"', false)->assertSee('Occupational therapy')
            ->assertSee('name="registration_data[musical_interests]"', false)->assertSee('Drums / percussion')
            ->assertSee('name="registration_data[session_type]"', false)->assertSee('Individual (1-on-1)')
            ->assertSee('name="registration_data[frequency]"', false)->assertSee('Custom schedule');
    }

    public function test_custom_music_values_and_previously_recorded_text_are_preserved(): void
    {
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Example Child']);
        $data = ['person_id' => $person->id, 'registered_on' => '2026-11-17', 'registration_data' => ['musical_interests' => 'Singing'], 'registration_custom' => ['musical_interests' => 'Singing, Guitar']];
        $this->actingAs($admin)->post(route('admin.therapy.children.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $child = TherapyChild::firstOrFail();
        $this->assertSame('Singing, Guitar', $child->registration_data['musical_interests']);
        $this->get(route('admin.therapy.children.edit', $child))->assertOk()->assertSee('Singing, Guitar (previously recorded)');
        $data['registration_data']['musical_interests'] = 'Singing, Guitar';
        unset($data['registration_custom']);
        $this->put(route('admin.therapy.children.update', $child), $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Singing, Guitar', $child->fresh()->registration_data['musical_interests']);
    }

    public function test_validation_keeps_sensitive_old_input_encrypted_in_the_session(): void
    {
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Example Child']);
        $this->actingAs($admin)->from(route('admin.therapy.children.create'))->post(route('admin.therapy.children.store'), [
            'person_id' => $person->id, 'registered_on' => 'not-a-date', 'date_of_birth' => '2016-05-01',
            'registration_data' => ['allergies' => 'PRIVATE-ALLERGY'],
        ])->assertSessionHasErrors('registered_on');
        $this->assertArrayNotHasKey('registration_data', session('_old_input', []));
        $this->assertArrayNotHasKey('date_of_birth', session('_old_input', []));
        $this->assertStringNotContainsString('PRIVATE-ALLERGY', session('therapy_private_old_input'));
        $this->assertSame('PRIVATE-ALLERGY', TherapyOldInput::get('registration_data.allergies'));
        $this->get(route('admin.therapy.children.create'))->assertOk()->assertSee('PRIVATE-ALLERGY');
    }

    public function test_disability_seeder_is_repeatable_and_does_not_reactivate_existing_options(): void
    {
        DB::table('therapy_disabilities')->where('name', 'ADHD')->update(['is_active' => false]);
        $before = DB::table('therapy_disabilities')->count();
        $this->seed(TherapyDisabilityOptionsSeeder::class);
        $this->seed(TherapyDisabilityOptionsSeeder::class);
        $this->assertSame($before, DB::table('therapy_disabilities')->count());
        $this->assertFalse((bool) DB::table('therapy_disabilities')->where('name', 'ADHD')->value('is_active'));
    }

    public function test_disability_choices_and_other_are_saved_privately_and_preserve_legacy_values(): void
    {
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Example Child']);
        $this->actingAs($admin)->get(route('admin.therapy.children.create'))->assertOk()
            ->assertSee('registration_data[disabilities_selected][]', false)->assertSee('Other disability / diagnosis');
        $data = ['person_id' => $person->id, 'registered_on' => '2026-11-17', 'registration_data' => [
            'disabilities_selected' => ['Autism spectrum', 'ADHD'], 'disabilities_other' => 'Individual description',
        ]];
        $this->post(route('admin.therapy.children.store'), $data)->assertRedirect();
        $child = TherapyChild::firstOrFail();
        $this->assertSame(['Autism spectrum', 'ADHD'], $child->registration_data['disabilities_selected']);
        $this->assertSame('Individual description', $child->registration_data['disabilities_other']);
        $this->assertDatabaseMissing('therapy_disabilities', ['name' => 'Individual description']);
        $this->get(route('admin.therapy.children.show', $child))->assertOk()->assertSee('Autism spectrum')->assertSee('Individual description');
        DB::table('therapy_disabilities')->where('name', 'ADHD')->update(['is_active' => false]);
        $child->update(['registration_data' => $child->registration_data + ['disabilities' => 'Legacy entry']]);
        $this->get(route('admin.therapy.children.edit', $child))->assertOk()->assertSee('ADHD (previously recorded)')->assertSee('Legacy entry');
        $data['registration_data']['disabilities'] = 'Legacy entry';
        $this->put(route('admin.therapy.children.update', $child), $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('Legacy entry', $child->fresh()->registration_data['disabilities']);
        $this->assertContains('ADHD', $child->fresh()->registration_data['disabilities_selected']);
    }

    public function test_disability_option_settings_allow_deactivation_and_restore_without_deleting_history(): void
    {
        $admin = $this->admin();
        $option = DB::table('therapy_disabilities')->where('name', 'Autism spectrum')->first();
        $this->actingAs($admin)->get(route('admin.therapy.catalogues.index', 'disabilities'))->assertOk()
            ->assertSee('Autism spectrum')->assertSee('Deactivate');
        $data = ['name' => $option->name, 'is_active' => 0];
        $this->put(route('admin.therapy.catalogues.update', ['disabilities', $option->id]), $data)->assertRedirect();
        $this->assertDatabaseHas('therapy_disabilities', ['id' => $option->id, 'is_active' => 0]);
        $this->put(route('admin.therapy.catalogues.update', ['disabilities', $option->id]), array_merge($data, ['is_active' => 1]))->assertRedirect();
        $this->assertDatabaseHas('therapy_disabilities', ['id' => $option->id, 'is_active' => 1]);
        $limited = User::factory()->create()->givePermissionTo('access-admin');
        $this->actingAs($limited)->put(route('admin.therapy.catalogues.update', ['disabilities', $option->id]), $data)->assertForbidden();
    }

    public function test_each_catalogue_has_its_own_table_and_page_and_rejects_invalid_names(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        foreach (TherapyCatalogues::ITEMS as $key => $definition) {
            $this->get(route('admin.therapy.catalogues.index', $key))->assertOk()->assertSee($definition['label']);
            $this->post(route('admin.therapy.catalogues.store', $key), ['name' => 'Custom '.$key, 'is_active' => 1])->assertRedirect()->assertSessionHasNoErrors();
            $this->assertDatabaseHas($definition['table'], ['name' => 'Custom '.$key]);
        }
        $this->get(route('admin.therapy.catalogues.index', 'not-a-catalogue'))->assertNotFound();
        $this->post(route('admin.therapy.options.store'), ['category' => 'mobility', 'name' => 'Old form', 'is_active' => 1])->assertStatus(410);
    }

    public function test_catalogue_migration_copies_custom_and_inactive_choices_without_changing_source(): void
    {
        // RefreshDatabase uses an isolated in-memory SQLite database for these tests.
        foreach (TherapyCatalogues::ITEMS as $definition) \Illuminate\Support\Facades\Schema::drop($definition['table']);
        DB::table('therapy_options')->insert(['category' => 'mobility', 'name' => 'Custom inactive', 'is_active' => false, 'created_at' => '2020-01-02 00:00:00', 'updated_at' => '2020-01-03 00:00:00']);
        $migration = require database_path('migrations/2026_11_17_000006_create_separate_therapy_option_tables.php');
        $migration->up();
        foreach (TherapyCatalogues::ITEMS as $key => $definition) {
            $this->assertSame(DB::table('therapy_options')->where('category', $key)->count(), DB::table($definition['table'])->count());
        }
        $this->assertDatabaseHas('therapy_mobility_options', ['name' => 'Custom inactive', 'is_active' => 0, 'created_at' => '2020-01-02 00:00:00', 'updated_at' => '2020-01-03 00:00:00']);
        $this->assertDatabaseHas('therapy_options', ['category' => 'mobility', 'name' => 'Custom inactive', 'is_active' => 0]);
    }

    public function test_unlisted_disability_checkbox_value_is_rejected_but_other_text_can_be_recorded(): void
    {
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Example Child']);
        $data = ['person_id' => $person->id, 'registered_on' => '2026-11-17', 'registration_data' => [
            'disabilities_selected' => ['Unlisted value'], 'disabilities_other' => 'A private description',
        ]];
        $this->actingAs($admin)->post(route('admin.therapy.children.store'), $data)->assertSessionHasErrors('registration_data.disabilities_selected.0');
        $this->assertDatabaseCount('therapy_children', 0);
    }
}
