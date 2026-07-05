<?php
namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_loads_for_authenticated_user(): void
    {
        $user = User::factory()->create(['full_name' => 'Kent Pielago']);
        $response = $this->actingAs($user)->get('/profile');
        $response->assertStatus(200);
        $response->assertSee('Kent Pielago');
    }

    public function test_user_can_update_profile_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile', [
            'full_name'       => 'Updated Name',
            'phone'           => '09123456789',
            'country'         => 'Philippines',
            'currency_code'   => 'PHP',
            'currency_symbol' => '₱',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id'        => $user->id,
            'full_name' => 'Updated Name',
            'phone'     => '09123456789',
            'country'   => 'Philippines',
        ]);
    }

    public function test_user_can_upload_profile_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/profile', [
            'full_name'     => $user->full_name,
            'profile_photo' => UploadedFile::fake()->image('photo.jpg', 200, 200),
        ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertNotNull($user->profile_photo);
        Storage::disk('public')->assertExists($user->profile_photo);
    }

    public function test_profile_update_replaces_old_photo(): void
    {
        Storage::fake('public');
        $user = User::factory()->create(['profile_photo' => 'profile-photos/old.jpg']);
        Storage::disk('public')->put('profile-photos/old.jpg', 'fake-content');

        $this->actingAs($user)->put('/profile', [
            'full_name'     => $user->full_name,
            'profile_photo' => UploadedFile::fake()->image('new.jpg', 200, 200),
        ]);

        $user->refresh();
        Storage::disk('public')->assertMissing('profile-photos/old.jpg');
        Storage::disk('public')->assertExists($user->profile_photo);
    }

    public function test_profile_requires_authentication(): void
    {
        $this->get('/profile')->assertRedirect(route('login'));
    }

    public function test_full_name_is_required(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->put('/profile', ['full_name' => '']);
        $response->assertSessionHasErrors('full_name');
    }
}
