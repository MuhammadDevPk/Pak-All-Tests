<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class FilamentAdminAccessTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createAdminUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => 'System Administrator',
            'email' => 'admin-'.Str::random(8).'@pakalltests.com',
            'password' => Hash::make('SecretAdminPass123!'),
            'is_guest' => false,
            'is_suspended' => false,
        ], $attributes));
    }

    protected function createGuestUser(): User
    {
        return User::create([
            'name' => 'Guest Candidate #999',
            'email' => 'guest_'.Str::random(10).'@guest.pakalltests.local',
            'password' => Hash::make(Str::random(32)),
            'is_guest' => true,
            'guest_expires_at' => now()->addHours(24),
        ]);
    }

    public function test_unauthenticated_visitor_visiting_admin_is_redirected_to_admin_login(): void
    {
        $response = $this->get('/admin');

        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');
    }

    public function test_guest_user_visiting_admin_is_redirected_to_admin_login_not_403(): void
    {
        PlatformSetting::set('auto_guest_login', true);
        $guest = $this->createGuestUser();

        // Simulate visiting public site where student is logged in on 'web' guard
        $response = $this->actingAs($guest, 'web')->get('/admin');

        // Must redirect cleanly to /admin/login without an HTTP 403 Forbidden trap
        $this->assertNotEquals(403, $response->getStatusCode(), 'Guest user must not be greeted with a 403 error on /admin.');
        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');
    }

    public function test_guest_user_can_view_admin_login_page_without_redirect_loop_or_403(): void
    {
        PlatformSetting::set('auto_guest_login', true);
        $guest = $this->createGuestUser();

        // Simulate student opening /admin/login while having an active guest session on 'web'
        $response = $this->actingAs($guest, 'web')->get('/admin/login');

        // Login page must render with HTTP 200, allowing admin credentials to be entered
        $response->assertOk();
    }

    public function test_admin_user_with_web_session_is_seamlessly_bridged_to_admin_guard(): void
    {
        $admin = $this->createAdminUser();

        // Admin logged into main application on 'web' guard visits /admin
        $response = $this->actingAs($admin, 'web')->get('/admin');

        $response->assertOk();
        $this->assertTrue(
            Auth::guard('admin')->check(),
            'Admin session from web guard must be seamlessly bridged to the admin guard.'
        );
        $this->assertSame($admin->id, Auth::guard('admin')->id());
    }

    public function test_admin_user_authenticated_on_admin_guard_can_access_admin_dashboard(): void
    {
        $admin = $this->createAdminUser();

        $response = $this->actingAs($admin, 'admin')->get('/admin');

        $response->assertOk();
    }

    public function test_suspended_user_is_strictly_forbidden_403(): void
    {
        $suspendedUser = $this->createAdminUser([
            'is_suspended' => true,
        ]);

        $response = $this->actingAs($suspendedUser, 'admin')->get('/admin');

        $response->assertStatus(403);
    }

    public function test_auto_authenticate_guest_middleware_bypasses_admin_routes(): void
    {
        PlatformSetting::set('auto_guest_login', true);

        $initialUserCount = User::where('is_guest', true)->count();

        // Direct unauthenticated request to /admin
        $response = $this->get('/admin');

        $response->assertStatus(302);
        $response->assertRedirect('/admin/login');

        // Must not create a guest user
        $newUserCount = User::where('is_guest', true)->count();
        $this->assertSame($initialUserCount, $newUserCount, 'Admin route must never trigger auto-guest creation.');
    }
}
