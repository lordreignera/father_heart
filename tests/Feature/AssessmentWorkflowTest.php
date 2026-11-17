<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Person;
use App\Models\User;
use App\Models\District;
use App\Models\Subcounty;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AssessmentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Super Administrator');
        return $admin;
    }

    private function data(Person $person, string $date): array
    {
        return ['person_id' => $person->id, 'assessed_on' => $date, 'status' => 'draft',
            'registration_complete' => 0, 'assessment_complete' => 0, 'safety_lesson_received' => 0,
            'therapy_received' => 0, 'released_to_go' => 0];
    }

    public function test_consent_is_last_step_and_granted_consent_requires_evidence_and_guardian_for_minors(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Example Child']);
        $this->actingAs($admin)->get(route('admin.assessments.create', ['person_id' => $person->id]))->assertOk()->assertSee('6. Consent and release')->assertSee('consent[status]', false);
        $data = $this->data($person, '2026-11-03') + ['age_years' => 10, 'consent' => ['status' => 'granted', 'signer_name' => 'Example Child', 'signed_on' => '2026-11-03', 'recipient_identity' => 'PRIVATE-NIN']];
        $this->post(route('admin.assessments.store'), $data)->assertSessionHasErrors(['consent.document', 'consent.guardian_name', 'consent.guardian_signed_on']);
        $this->assertDatabaseCount('assessments', 0);
        $this->assertNull(data_get(session('_old_input'), 'consent.recipient_identity'));
        $data['consent'] += ['guardian_name' => 'Parent', 'guardian_signed_on' => '2026-11-03', 'document' => UploadedFile::fake()->create('signed.pdf', 10, 'application/pdf')];
        $this->post(route('admin.assessments.store'), $data)->assertRedirect()->assertSessionHasNoErrors();
        $assessment = Assessment::firstOrFail();
        $consent = $assessment->mediaConsents()->firstOrFail();
        $this->assertSame('granted', $consent->status);
        $this->assertSame('PRIVATE-NIN', $consent->recipient_identity);
        $this->assertStringNotContainsString('PRIVATE-NIN', DB::table('media_consents')->where('id', $consent->id)->value('recipient_identity'));
        Storage::disk('local')->assertExists($consent->document_path);
        $this->put(route('admin.assessments.update', $assessment), $this->data($person, '2026-11-03'))->assertRedirect();
        $this->assertCount(1, $assessment->fresh()->mediaConsents);
    }

    public function test_consent_requires_its_own_permission_and_is_not_inferred(): void
    {
        $this->seed(RoleSeeder::class);
        $person = Person::create(['full_name' => 'Example']);
        $user = User::factory()->create()->givePermissionTo(['access-admin', 'route-admin-assessments-store']);
        $data = $this->data($person, '2026-11-03');
        $this->actingAs($user)->post(route('admin.assessments.store'), $data + ['consent' => ['status' => 'declined']])->assertForbidden();
        $this->assertDatabaseCount('assessments', 0);
        $user->givePermissionTo('route-admin-assessments-index');
        $this->post(route('admin.assessments.store'), $data)->assertRedirect();
        $this->assertDatabaseCount('media_consents', 0);
    }

    public function test_gender_validation_and_legacy_gender_preservation(): void
    {
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Example']);
        $data = $this->data($person, '2026-11-03');
        $this->actingAs($admin)->post(route('admin.assessments.store'), $data + ['gender' => 'other'])->assertSessionHasErrors('gender');
        $this->post(route('admin.assessments.store'), $data + ['gender' => 'not_recorded'])->assertSessionHasErrors('gender');
        $assessment = $person->assessments()->create(['status' => 'draft', 'gender' => 'other']);
        $this->get(route('admin.assessments.edit', $assessment))->assertOk()->assertSee('Previously recorded value (unchanged)')->assertDontSee('Prefer not to say');
        $this->put(route('admin.assessments.update', $assessment), $data + ['gender' => 'other'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('other', $assessment->fresh()->gender);
        $this->put(route('admin.assessments.update', $assessment), $data + ['gender' => 'not_recorded'])->assertSessionHasErrors('gender');
    }

    public function test_assessment_and_location_routes_require_permissions(): void
    {
        $this->seed(RoleSeeder::class);
        $person = Person::create(['full_name' => 'Example']);
        $assessment = $person->assessments()->create(['status' => 'draft']);
        $district = District::create(['name' => 'District One']);
        $user = User::factory()->create()->givePermissionTo('access-admin');
        $this->actingAs($user)->get(route('admin.assessments.create', ['person_id' => $person->id]))->assertForbidden();
        $this->get(route('admin.assessments.edit', $assessment))->assertForbidden();
        $this->getJson(route('admin.location-options.subcounties', ['district_id' => $district->id]))->assertForbidden();
        $user->givePermissionTo('route-admin-assessments-create');
        $this->get(route('admin.assessments.create', ['person_id' => $person->id]))->assertOk()->assertSee('data-searchable-district', false)->assertDontSee('Search districts');
        $this->get(route('admin.assessments.edit', $assessment))->assertForbidden();
        $this->getJson(route('admin.location-options.subcounties', ['district_id' => $district->id]))->assertOk();
    }

    public function test_codes_increment_by_month_preserve_existing_and_skip_collisions(): void
    {
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Jane Doe']);
        $person->assessments()->create(['intake_code' => '112026-1000-JD', 'status' => 'draft']);

        $this->actingAs($admin)->post(route('admin.assessments.store'), $this->data($person, '2026-11-03') + ['intake_code' => 'forged'])->assertRedirect();
        $first = Assessment::latest('id')->firstOrFail();
        $this->assertSame('112026-1001-JD', $first->intake_code);
        $this->actingAs($admin)->post(route('admin.assessments.store'), $this->data($person, '2026-11-04'))->assertRedirect();
        $this->assertSame('112026-1002-JD', Assessment::latest('id')->firstOrFail()->intake_code);
        $this->actingAs($admin)->post(route('admin.assessments.store'), $this->data($person, '2026-12-01'))->assertRedirect();
        $this->assertSame('122026-1000-JD', Assessment::latest('id')->firstOrFail()->intake_code);
        $this->actingAs($admin)->put(route('admin.assessments.update', $first), $this->data($person, '2026-12-03') + ['intake_code' => 'changed'])->assertRedirect();
        $this->assertSame('112026-1001-JD', $first->fresh()->intake_code);
    }

    public function test_code_settings_are_restricted_and_apply_to_future_assessments(): void
    {
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Jane Doe']);
        $settings = ['prefix' => 'fh', 'date_format' => 'Ym', 'starting_number' => 1500, 'sequence_reset' => 'continuous'];
        $this->actingAs(User::factory()->create())->put(route('admin.settings.intake-code.update'), $settings)->assertForbidden();
        $this->actingAs($admin)->put(route('admin.settings.intake-code.update'), $settings)->assertRedirect();
        $this->actingAs($admin)->post(route('admin.assessments.store'), $this->data($person, '2026-11-10'))->assertRedirect();
        $this->actingAs($admin)->post(route('admin.assessments.store'), $this->data($person, '2026-12-10'))->assertRedirect();
        $this->assertDatabaseHas('assessments', ['intake_code' => 'FH-202611-1500-JD']);
        $this->assertDatabaseHas('assessments', ['intake_code' => 'FH-202612-1501-JD']);
    }

    public function test_photos_are_private_and_new_uploads_preserve_photo_history(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Photo Recipient']);
        $this->actingAs($admin)->post(route('admin.assessments.store'), $this->data($person, '2026-11-10') + [
            'before_camera' => UploadedFile::fake()->image('before.jpg'),
            'after_upload' => UploadedFile::fake()->image('after.png'),
        ])->assertRedirect();
        $assessment = Assessment::firstOrFail();
        $this->assertCount(2, $assessment->photos);
        $photo = $assessment->photos->first();
        Storage::disk('local')->assertExists($photo->path);
        $this->get(route('admin.assessment-photos.show', $photo))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs(User::factory()->create())->get(route('admin.assessment-photos.show', $photo))->assertForbidden();
        $this->actingAs($admin)->put(route('admin.assessments.update', $assessment), $this->data($person, '2026-11-10') + ['before_upload' => UploadedFile::fake()->image('new.jpg')])->assertRedirect();
        $this->assertCount(3, $assessment->fresh()->photos);
        Storage::disk('local')->assertExists($photo->path);
    }

    public function test_photo_number_inputs_are_hidden_and_existing_references_are_preserved(): void
    {
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Photo Recipient']);
        $assessment = $person->assessments()->create(['status' => 'draft', 'before_photo_number' => 'OLD-BEFORE', 'after_photo_number' => 'OLD-AFTER']);
        $this->actingAs($admin)->get(route('admin.assessments.edit', $assessment))->assertOk()
            ->assertDontSee('name="before_photo_number"', false)->assertDontSee('name="after_photo_number"', false)
            ->assertSee('name="before_camera"', false)->assertSee('name="after_upload"', false);
        $this->put(route('admin.assessments.update', $assessment), $this->data($person, '2026-11-10') + ['before_photo_number' => 'forged'])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('OLD-BEFORE', $assessment->fresh()->before_photo_number);
        $this->assertSame('OLD-AFTER', $assessment->fresh()->after_photo_number);
    }

    public function test_invalid_or_conflicting_uploads_do_not_create_assessments(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $person = Person::create(['full_name' => 'Photo Recipient']);
        $this->actingAs($admin)->post(route('admin.assessments.store'), $this->data($person, '2026-11-10') + [
            'before_camera' => UploadedFile::fake()->image('one.jpg'), 'before_upload' => UploadedFile::fake()->image('two.jpg'),
        ])->assertSessionHasErrors(['before_camera', 'before_upload']);
        $this->actingAs($admin)->post(route('admin.assessments.store'), $this->data($person, '2026-11-10') + [
            'after_upload' => UploadedFile::fake()->create('not-image.txt', 1, 'text/plain'),
        ])->assertSessionHasErrors('after_upload');
        $this->assertDatabaseCount('assessments', 0);
        $this->assertSame(0, DB::table('intake_code_sequences')->count());
    }

    public function test_location_options_preserve_a_selected_inactive_unit_only_in_its_district(): void
    {
        $admin = $this->admin();
        $district = District::create(['name' => 'District One']);
        $other = District::create(['name' => 'District Two']);
        $unit = Subcounty::create(['name' => 'Inactive Unit', 'district_id' => $district->id, 'is_active' => false]);
        $url = route('admin.location-options.subcounties');
        $this->actingAs($admin)->getJson($url.'?district_id='.$district->id)->assertOk()->assertJsonMissing(['id' => $unit->id]);
        $this->actingAs($admin)->getJson($url.'?district_id='.$district->id.'&selected_id='.$unit->id)->assertOk()->assertJsonFragment(['id' => $unit->id]);
        $this->actingAs($admin)->getJson($url.'?district_id='.$other->id.'&selected_id='.$unit->id)->assertOk()->assertJsonMissing(['id' => $unit->id]);
    }
}
