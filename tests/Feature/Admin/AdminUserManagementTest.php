<?php
namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_admin_can_view_user_list(): void
    {
        $admin = $this->admin();
        User::factory()->count(3)->create();
        $this->actingAs($admin)->get('/admin/users')->assertStatus(200)->assertSee('Users');
    }

    public function test_traveler_cannot_access_user_list(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin/users')->assertStatus(403);
    }

    public function test_admin_can_ban_user(): void
    {
        $admin = $this->admin();
        $user  = User::factory()->create(['role' => 'traveler']);

        $this->actingAs($admin)->patch("/admin/users/{$user->id}/ban")->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'banned']);
    }

    public function test_admin_can_unban_user(): void
    {
        $admin = $this->admin();
        $user  = User::factory()->create(['role' => 'banned']);

        $this->actingAs($admin)->patch("/admin/users/{$user->id}/ban")->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $user->id, 'role' => 'traveler']);
    }

    public function test_admin_can_delete_user(): void
    {
        $admin = $this->admin();
        $user  = User::factory()->create();

        $this->actingAs($admin)->delete("/admin/users/{$user->id}")->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_themselves(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->delete("/admin/users/{$admin->id}")->assertStatus(403);
    }
}
