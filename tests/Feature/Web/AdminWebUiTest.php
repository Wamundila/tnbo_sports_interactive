<?php

namespace Tests\Feature\Web;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWebUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_login_page_is_available_to_guests(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Admin Login');
    }

    public function test_admin_dashboard_redirects_guests_to_login(): void
    {
        $this->get('/admin')
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_authenticate_through_web_form(): void
    {
        $admin = Admin::create([
            'name' => 'Trivia Admin',
            'email' => 'admin@example.com',
            'password' => 'secret-pass',
            'role' => 'interactive_admin',
            'status' => 'active',
        ]);

        $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'secret-pass',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticated('admin');

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Recent quizzes')
            ->assertSee('How To');
    }

    public function test_authenticated_admin_can_open_quiz_report_and_help_pages(): void
    {
        $admin = Admin::create([
            'name' => 'Trivia Admin',
            'email' => 'admin@example.com',
            'password' => 'secret-pass',
            'role' => 'interactive_admin',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin');

        $this->get('/admin/quizzes/create')
            ->assertOk()
            ->assertSee('Create Quiz')
            ->assertSee('state: not_open');

        $this->get('/admin/reports')
            ->assertOk()
            ->assertSee('Reports');

        $this->get('/admin/help/how-to')
            ->assertOk()
            ->assertSee('How To Get Trivia Live')
            ->assertSee('scheduled')
            ->assertSee('Publish');
    }
}
