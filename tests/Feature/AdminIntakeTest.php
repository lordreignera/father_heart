<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\Person;
use App\Models\Subcounty;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminIntakeTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Administrator');

        return $user;
    }

    public function test_a_guest_cannot_open_people_or_location_records(): void
    {
        $this->get('/admin/people')->assertRedirect('/login');
        $this->get('/admin/districts')->assertRedirect('/login');
    }

    public function test_registration_warns_on_equivalent_phone_formats_and_allows_explicit_confirmation(): void
    {
        $admin = $this->admin();
        $existing = Person::create(['full_name' => 'Existing Person', 'phone' => '0700 123 456']);
        $payload = ['full_name' => 'Another Person', 'phone' => '+256 700 123 456', '_modal' => 'person-create-dialog', 'nin' => 'PRIVATE-NIN'];

        $this->actingAs($admin)->from(route('admin.people.index'))->post(route('admin.people.store'), $payload)
            ->assertRedirect(route('admin.people.index'))->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('people', 1);
        $review = session('duplicate_review');
        $this->assertSame($existing->id, $review['matches'][0]['id']);
        $this->assertTrue($review['nin_cleared']);
        $this->assertArrayNotHasKey('nin', session('_old_input'));
        $this->actingAs($admin)->get(route('admin.people.index'))
            ->assertOk()->assertSee('This phone number is already in the registry.')
            ->assertSee('Existing Person')->assertSee('confirm_duplicate', false);

        $this->actingAs($admin)->post(route('admin.people.store'), $payload + ['confirm_duplicate' => '1', 'duplicate_token' => $review['token']])->assertRedirect();
        $this->assertDatabaseCount('people', 2);
    }

    public function test_duplicate_confirmation_cannot_be_reused_for_a_different_person_or_changed_matches(): void
    {
        $admin = $this->admin();
        Person::create(['full_name' => 'Existing', 'phone' => '0700123456']);
        $payload = ['full_name' => 'First Person', 'phone' => '256700123456'];
        $this->actingAs($admin)->from(route('admin.people.create'))->post(route('admin.people.store'), $payload)->assertSessionHasErrors('phone');
        $token = session('duplicate_review')['token'];

        $this->actingAs($admin)->from(route('admin.people.create'))->post(route('admin.people.store'), [
            'full_name' => 'Different Person', 'phone' => '0700123456', 'confirm_duplicate' => '1', 'duplicate_token' => $token,
        ])->assertSessionHasErrors('phone');
        $this->assertDatabaseCount('people', 1);
    }

    public function test_archived_numbers_are_flagged_and_unchanged_shared_numbers_do_not_block_edits(): void
    {
        $admin = $this->admin();
        $archived = Person::create(['full_name' => 'Archived Person', 'phone' => '0700123456']);
        $archived->delete();
        $current = Person::create(['full_name' => 'Current Person', 'phone' => '+256700123456']);

        $this->actingAs($admin)->from(route('admin.people.edit', $current))->put(route('admin.people.update', $current), [
            'full_name' => 'Updated Person', 'phone' => '0700 123456',
        ])->assertRedirect(route('admin.people.show', $current));

        $this->actingAs($admin)->from(route('admin.people.create'))->post(route('admin.people.store'), [
            'full_name' => 'New Person', 'phone' => '00256 700 123456',
        ])->assertSessionHasErrors('phone');
        $this->assertTrue(session('duplicate_review')['matches'][0]['archived']);
        $this->assertSame(2, session('duplicate_review')['count']);
    }

    public function test_people_filters_and_csv_export_match_and_require_access(): void
    {
        $admin = $this->admin();
        $match = Person::create(['full_name' => 'Jane Example', 'phone' => '0700000123', 'nin' => 'PRIVATE-NIN']);
        $match->forceFill(['created_at' => '2026-01-15 12:00:00'])->save();
        $match->assessments()->create(['status' => 'draft']);
        $outside = Person::create(['full_name' => 'Jane Outside']);
        $outside->forceFill(['created_at' => '2026-02-15 12:00:00'])->save();
        $other = Person::create(['full_name' => 'Other Person']);
        $other->forceFill(['created_at' => '2026-01-15 12:00:00'])->save();

        $filters = ['q' => 'Jane', 'date_from' => '2026-01-01', 'date_to' => '2026-01-31'];
        $this->actingAs($admin)->get(route('admin.people.index', $filters))
            ->assertOk()->assertSee('Jane Example')->assertDontSee('Jane Outside')->assertDontSee('Other Person');

        $response = $this->actingAs($admin)->get(route('admin.people.export', $filters))->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Jane Example', $csv);
        $this->assertStringContainsString("'0700000123", $csv);
        $this->assertStringContainsString('2026-01-15', $csv);
        $this->assertStringNotContainsString('Jane Outside', $csv);
        $this->assertStringNotContainsString('PRIVATE-NIN', $csv);

        $this->actingAs(User::factory()->create())->get(route('admin.people.export'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.people.index', ['date_to' => '2026-01-31']))->assertOk()->assertSee('Jane Example');
        $this->actingAs($admin)->from(route('admin.people.index'))->get(route('admin.people.export', ['date_from' => '2026-02-01', 'date_to' => '2026-01-01']))->assertSessionHasErrors('date_to');
    }

    public function test_csv_export_escapes_spreadsheet_formulas(): void
    {
        $admin = $this->admin();
        Person::create(['full_name' => '=1+1', 'phone' => '+256700000000']);

        $csv = $this->actingAs($admin)->get(route('admin.people.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=1+1", $csv);
        $this->assertStringContainsString("'+256700000000", $csv);
    }

    public function test_people_registry_shows_modal_actions_and_only_authorized_users_can_delete(): void
    {
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Modal Recipient']);

        $this->actingAs($admin)->get(route('admin.people.index'))
            ->assertOk()
            ->assertSee(route('admin.assessments.create', ['person_id' => $person->id]))
            ->assertSee('Add assessment')
            ->assertSee('person-view-'.$person->id)
            ->assertSee('person-edit-'.$person->id)
            ->assertSee('person-delete-'.$person->id);

        $this->actingAs($admin)->get(route('admin.assessments.create', ['person_id' => $person->id]))
            ->assertOk()->assertSee('Modal Recipient');

        $this->actingAs(User::factory()->create())->delete(route('admin.people.destroy', $person))->assertForbidden();
        $this->assertDatabaseHas('people', ['id' => $person->id, 'deleted_at' => null]);

        $this->actingAs($admin)->delete(route('admin.people.destroy', $person))->assertRedirect(route('admin.people.index'));
        $this->assertSoftDeleted('people', ['id' => $person->id]);
    }

    public function test_person_with_care_records_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Recipient With Assessment']);
        $person->assessments()->create(['status' => 'draft']);

        $this->actingAs($admin)->delete(route('admin.people.destroy', $person))
            ->assertRedirect(route('admin.people.index'))
            ->assertSessionHasErrors('person');

        $this->assertDatabaseHas('people', ['id' => $person->id, 'deleted_at' => null]);
    }

    public function test_super_admin_can_register_and_assess_without_creating_a_handover(): void
    {
        $user = $this->admin();
        $district = District::create(['name' => 'Test District', 'kind' => 'district']);
        $subcounty = Subcounty::create(['district_id' => $district->id, 'name' => 'Test Subcounty', 'kind' => 'subcounty']);

        $this->actingAs($user)->post(route('admin.people.store'), [
            'full_name' => 'Test Recipient', 'phone' => '0700000000', 'nin' => 'TEST123',
        ])->assertRedirect();
        $person = Person::firstOrFail();
        $this->assertNotEquals('TEST123', \Illuminate\Support\Facades\DB::table('people')->value('nin'));

        $this->actingAs($user)->post(route('admin.assessments.store'), [
            'person_id' => $person->id,
            'district_id' => $district->id,
            'subcounty_id' => $subcounty->id,
            'age_years' => 20,
            'device_requested' => 'Wheelchair',
            'causes' => ['Accident'],
            'registration_complete' => 1,
            'assessment_complete' => 1,
            'safety_lesson_received' => 0,
            'therapy_received' => 0,
            'released_to_go' => 0,
            'status' => 'assessed',
        ])->assertRedirect();
        $this->assertDatabaseCount('assessments', 1);
        $this->assertDatabaseCount('media_consents', 0);
        $this->assertSame('Wheelchair', $person->assessments()->first()->device_requested);
    }

    public function test_subcounty_must_belong_to_selected_district(): void
    {
        $user = $this->admin();
        $district = District::create(['name' => 'One']);
        $other = District::create(['name' => 'Two']);
        $subcounty = Subcounty::create(['district_id' => $other->id, 'name' => 'Not in One']);
        $person = Person::create(['full_name' => 'Test Recipient']);

        $this->actingAs($user)->post(route('admin.assessments.store'), [
            'person_id' => $person->id, 'district_id' => $district->id, 'subcounty_id' => $subcounty->id,
            'registration_complete' => 0, 'assessment_complete' => 0,
            'safety_lesson_received' => 0, 'therapy_received' => 0, 'released_to_go' => 0,
            'status' => 'draft',
        ])->assertSessionHasErrors('subcounty_id');
        $this->assertDatabaseCount('assessments', 0);
    }

    public function test_admin_can_render_location_and_intake_pages(): void
    {
        $user = $this->admin();
        $district = District::create(['name' => 'Test District']);
        Subcounty::create(['district_id' => $district->id, 'name' => 'Test Subcounty']);
        $person = Person::create(['full_name' => 'Test Recipient']);

        $this->actingAs($user)->get(route('admin.districts.index'))->assertOk()->assertSee('Test District');
        $this->actingAs($user)->get(route('admin.subcounties.index'))->assertOk()->assertSee('Test Subcounty');
        $this->actingAs($user)->get(route('admin.people.index'))->assertOk()->assertSee('Test Recipient');
        $this->actingAs($user)->get(route('admin.assessments.create', ['person_id' => $person->id]))
            ->assertOk()->assertSee('Recipient intake questionnaire')
            ->assertSee('data-assessment-form', false)
            ->assertSee('Step by step')->assertSee('Full form')
            ->assertSee('4. Personal story and hopes')
            ->assertSee('5. Ministry & process checklist', false);
    }

    public function test_signed_media_consent_is_stored_privately_and_requires_a_guardian_for_a_minor(): void
    {
        Storage::fake('local');
        $user = $this->admin();
        $person = Person::create(['full_name' => 'Test Child']);
        $assessment = $person->assessments()->create(['age_years' => 16, 'status' => 'assessed']);

        $this->actingAs($user)->post(route('admin.assessments.consents.store', $assessment), [
            'status' => 'granted', 'signed_on' => '2026-11-13', 'signer_name' => 'Test Child',
            'document' => UploadedFile::fake()->image('consent.jpg'),
        ])->assertSessionHasErrors('guardian_name');
        $this->assertDatabaseCount('media_consents', 0);

        $this->actingAs($user)->post(route('admin.assessments.consents.store', $assessment), [
            'status' => 'granted', 'signed_on' => '2026-11-13', 'signer_name' => 'Test Child',
            'guardian_name' => 'Test Guardian', 'guardian_signed_on' => '2026-11-13',
            'document' => UploadedFile::fake()->image('consent.jpg'),
        ])->assertRedirect();
        $this->assertDatabaseCount('media_consents', 1);
        Storage::disk('local')->assertExists($assessment->mediaConsents()->first()->document_path);
    }
}
