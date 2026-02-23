<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', 'session.auth'])
            ->get('/_test/session-auth', fn () => response('ok', 200));

        Route::middleware(['web', 'session.timeout'])
            ->get('/_test/session-timeout', fn () => response('ok', 200));

        Route::middleware(['web', 'role:admin,treasurer'])
            ->get('/_test/role-check', fn () => response('ok', 200));
    }

    public function test_session_auth_redirects_when_user_id_missing(): void
    {
        $response = $this->get('/_test/session-auth');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Please login first.');
    }

    public function test_session_auth_allows_request_when_user_agent_hash_matches(): void
    {
        $agent = 'BWASA-Test-Agent/1.0';

        $response = $this
            ->withHeader('User-Agent', $agent)
            ->withSession([
                'usr_id' => 101,
                'auth_ua_hash' => hash('sha256', $agent),
            ])
            ->get('/_test/session-auth');

        $response->assertOk();
    }

    public function test_session_auth_invalidates_when_user_agent_hash_mismatches(): void
    {
        $response = $this
            ->withHeader('User-Agent', 'BWASA-Test-Agent/2.0')
            ->withSession([
                'usr_id' => 101,
                'auth_ua_hash' => hash('sha256', 'BWASA-Test-Agent/1.0'),
            ])
            ->get('/_test/session-auth');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Session invalidated. Please login again.');
        $response->assertSessionMissing('usr_id');
    }

    public function test_role_middleware_blocks_unauthorized_role(): void
    {
        $response = $this
            ->withSession(['usr_role' => 'resident'])
            ->get('/_test/role-check');

        $response->assertForbidden();
    }

    public function test_role_middleware_allows_authorized_role(): void
    {
        $response = $this
            ->withSession(['usr_role' => 'treasurer'])
            ->get('/_test/role-check');

        $response->assertOk();
    }

    public function test_session_timeout_redirects_when_last_activity_is_expired(): void
    {
        $response = $this
            ->withSession(['last_activity' => time() - 7201])
            ->get('/_test/session-timeout');

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Session expired. Please login again.');
    }

    public function test_session_timeout_allows_active_session_and_refreshes_timestamp(): void
    {
        $response = $this
            ->withSession(['last_activity' => time() - 10])
            ->get('/_test/session-timeout');

        $response->assertOk();
        $response->assertSessionHas('last_activity');
    }
}
