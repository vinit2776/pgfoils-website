<?php

namespace Tests\Feature;

use App\Filament\Resources\Activities\ActivityResource;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    private function staff(string $role, bool $mfa = true, bool $active = true): User
    {
        $user = User::factory()->create(['is_active' => $active]);
        $user->assignRole($role);
        if ($mfa) {
            $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
        }

        return $user;
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_a_user_without_a_role_cannot_open_the_admin(): void
    {
        $user = User::factory()->create();
        $user->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_a_deactivated_account_cannot_open_the_admin(): void
    {
        $this->actingAs($this->staff(User::ADMIN, active: false))->get('/admin')->assertForbidden();
    }

    public function test_two_step_sign_in_is_required_before_the_dashboard(): void
    {
        $response = $this->actingAs($this->staff(User::ADMIN, mfa: false))->get('/admin');
        $response->assertRedirect();
        $this->assertStringContainsString('multi-factor', $response->headers->get('Location'));
    }

    public function test_admin_with_two_step_set_up_sees_the_dashboard_users_and_log(): void
    {
        $admin = $this->staff(User::ADMIN);
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/users')->assertOk();
        $this->actingAs($admin)->get('/admin/activities')->assertOk();
    }

    public function test_editors_and_reviewers_cannot_manage_users_or_read_the_log(): void
    {
        foreach ([User::EDITOR, User::REVIEWER] as $role) {
            $user = $this->staff($role);
            $this->actingAs($user)->get('/admin')->assertOk();
            $this->actingAs($user)->get('/admin/users')->assertForbidden();
            $this->actingAs($user)->get('/admin/activities')->assertForbidden();
        }
    }

    public function test_the_activity_log_cannot_be_edited_or_deleted_from_the_admin(): void
    {
        $this->actingAs($this->staff(User::ADMIN));
        $this->assertFalse(ActivityResource::canCreate());
        $this->assertFalse(ActivityResource::canEdit(new Activity));
        $this->assertFalse(ActivityResource::canDelete(new Activity));
        $this->assertFalse(ActivityResource::canDeleteAny());
    }

    public function test_staff_accounts_cannot_be_deleted_from_the_admin(): void
    {
        $this->actingAs($admin = $this->staff(User::ADMIN));
        $this->assertFalse(\App\Filament\Resources\Users\UserResource::canDelete($admin));
    }

    public function test_sign_ins_and_failed_attempts_are_logged(): void
    {
        $user = $this->staff(User::EDITOR);
        event(new Login('web', $user, false));
        event(new Failed('web', null, ['email' => 'nobody@example.com']));

        $this->assertDatabaseHas('activity_log', ['description' => 'Signed in', 'causer_id' => $user->id]);
        $this->assertDatabaseHas('activity_log', ['description' => 'Failed sign-in attempt']);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_role_changes_are_logged(): void
    {
        $admin = $this->staff(User::ADMIN);
        $this->actingAs($admin);
        $user = User::factory()->create();
        $user->assignRole(User::REVIEWER);

        $this->assertDatabaseHas('activity_log', ['description' => 'Role added', 'causer_id' => $admin->id, 'subject_id' => $user->id]);
    }

    public function test_the_two_step_secret_is_stored_encrypted(): void
    {
        $user = $this->staff(User::ADMIN);
        $raw = \DB::table('users')->where('id', $user->id)->value('app_authentication_secret');
        $this->assertNotSame('JBSWY3DPEHPK3PXP', $raw);
        $this->assertSame('JBSWY3DPEHPK3PXP', $user->fresh()->app_authentication_secret);
    }
}
