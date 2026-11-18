<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm;
use Livewire\Livewire;
use Database\Seeders\RoleSeeder;
use Tests\TestCase;

class ProfileInformationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_profile_uses_workspace_layout_and_security_steps(): void
    {
        $this->seed(RoleSeeder::class);
        $staff = User::factory()->create()->assignRole('Super Administrator');
        $this->actingAs($staff)->get(route('profile.show'))->assertOk()
            ->assertSee('aria-label="Admin navigation"', false)
            ->assertSee('data-assessment-guide', false)
            ->assertSee('Step by step')->assertSee('Full view')
            ->assertSee('Profile Information')->assertSee('Update Password')
            ->assertSee('Two Factor Authentication')->assertSee('Browser Sessions')
            ->assertSee('Sign out');
    }

    public function test_profile_remains_available_without_workspace_access(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('profile.show'))->assertOk()
            ->assertDontSee('aria-label="Admin navigation"', false)->assertSee('Profile Information');
    }

    public function test_guest_cannot_access_profile(): void
    {
        $this->get(route('profile.show'))->assertRedirect(route('login'));
    }

    public function test_current_profile_information_is_available(): void
    {
        $this->actingAs($user = User::factory()->create());

        $component = Livewire::test(UpdateProfileInformationForm::class);

        $this->assertEquals($user->name, $component->state['name']);
        $this->assertEquals($user->email, $component->state['email']);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $this->actingAs($user = User::factory()->create());

        Livewire::test(UpdateProfileInformationForm::class)
            ->set('state', ['name' => 'Test Name', 'email' => 'test@example.com'])
            ->call('updateProfileInformation');

        $this->assertEquals('Test Name', $user->fresh()->name);
        $this->assertEquals('test@example.com', $user->fresh()->email);
    }
}
