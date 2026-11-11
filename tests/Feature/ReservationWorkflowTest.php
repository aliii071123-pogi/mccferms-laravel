<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Facility;
use App\Models\MobileAuthCode;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Tests\TestCase;

class ReservationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function student(array $attributes = []): User
    {
        return User::factory()->create($attributes + [
            'role' => 'student',
            'organization' => 'Student Council',
            'position' => 'President',
        ]);
    }

    private function requestData(Facility $facility, Equipment $equipment, array $overrides = []): array
    {
        return array_replace([
            'facility_id' => $facility->id,
            'date' => now()->addDays(2)->toDateString(),
            'start_time' => '13:00',
            'end_time' => '15:00',
            'pax' => 24,
            'purpose' => 'Student leadership workshop',
            'description' => 'A planning session for the student organization.',
            'equipment' => [$equipment->id => 2],
        ], $overrides);
    }

    private function mockMicrosoftIdentity(string $email, string $name = 'MCC User'): void
    {
        $identity = \Mockery::mock();
        $identity->shouldReceive('getEmail')->once()->andReturn($email);
        $identity->shouldReceive('getName')->once()->andReturn($name);

        $provider = \Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($identity);

        Socialite::shouldReceive('driver')->once()->with('microsoft')->andReturn($provider);
    }

    public function test_student_can_submit_and_track_a_reservation_with_equipment(): void
    {
        $student = $this->student();
        $facility = Facility::create(['name' => 'Innovation Hall', 'location' => 'North Wing', 'capacity' => 40]);
        $equipment = Equipment::create(['name' => 'Projector', 'category' => 'AV', 'quantity_available' => 4]);
        $this->actingAs($student)->get('/dashboard')->assertOk()->assertSee('Innovation Hall');

        $response = $this->actingAs($student)->post('/reservations', $this->requestData($facility, $equipment));
        $reservation = Reservation::sole();

        $response->assertRedirect(route('reservations.show', $reservation));
        $response->assertSessionHas('request_submitted', true);
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'status' => 'pending', 'user_id' => $student->id]);
        $this->assertSame(2, $reservation->equipment()->first()->pivot->quantity);
        $this->assertSame($reservation->ends_at->copy()->addDays(2)->timestamp, $reservation->return_due_at->timestamp);
        $this->actingAs($student)->get(route('reservations.show', $reservation))->assertOk()->assertSee('Printed letters still need to be signed');
        $this->actingAs($student)->get('/reservations')->assertOk()->assertSee('Student leadership workshop');
        $this->actingAs($student)->get('/facilities')->assertOk()->assertSee('Innovation Hall');
        $this->actingAs($student)->get('/equipment')->assertOk()->assertSee('Projector');
    }

    public function test_request_rejects_out_of_hours_and_conflicting_facility_times(): void
    {
        $student = $this->student();
        $facility = Facility::create(['name' => 'Lecture Room', 'location' => 'Main Building', 'capacity' => 40]);
        $equipment = Equipment::create(['name' => 'Speaker', 'category' => 'AV', 'quantity_available' => 6]);

        $this->actingAs($student)->from('/reservations/create')->post('/reservations', $this->requestData($facility, $equipment, [
            'start_time' => '12:00',
            'end_time' => '08:00',
        ]))->assertSessionHasErrors(['end_time']);

        $valid = $this->requestData($facility, $equipment);
        $this->actingAs($student)->post('/reservations', $valid)->assertSessionHasNoErrors();
        $this->actingAs($student)->from('/reservations/create')->post('/reservations', $valid)->assertSessionHasErrors(['facility_id']);

        $this->actingAs($student)->from('/reservations/create')->post('/reservations', $this->requestData($facility, $equipment, ['start_time' => '07:00']))->assertSessionHasErrors(['start_time']);

        $this->actingAs($student)->from('/reservations/create')->post('/reservations', $this->requestData($facility, $equipment, [
            'facility_id' => null,
            'equipment' => [$equipment->id => 5],
        ]))->assertSessionHasErrors(['equipment.'.$equipment->id]);
    }

    public function test_student_can_submit_an_equipment_only_request(): void
    {
        $student = $this->student();
        $equipment = Equipment::create(['name' => 'Wireless Microphone', 'category' => 'Audio', 'quantity_available' => 3]);

        $this->actingAs($student)->post('/reservations', [
            'facility_id' => null,
            'date' => now()->addDays(2)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'pax' => 18,
            'purpose' => 'Outdoor orientation',
            'description' => 'Borrow a microphone for the welcome program.',
            'equipment' => [$equipment->id => 1],
        ])->assertSessionHasNoErrors();

        $reservation = Reservation::sole();
        $this->assertNull($reservation->facility_id);
        $this->assertNotNull($reservation->return_due_at);
        $this->assertSame(1, $reservation->equipment()->first()->pivot->quantity);
    }

    public function test_students_only_manage_their_requests_and_admins_decide_them(): void
    {
        $owner = $this->student();
        $otherStudent = $this->student();
        $admin = $this->student(['role' => 'admin']);
        $facility = Facility::create(['name' => 'Meeting Room', 'location' => 'East Wing', 'capacity' => 20]);
        $reservation = Reservation::create([
            'user_id' => $owner->id,
            'facility_id' => $facility->id,
            'starts_at' => now()->addDays(3)->setTime(10, 0),
            'ends_at' => now()->addDays(3)->setTime(11, 0),
            'pax' => 12,
            'purpose' => 'Council meeting',
            'description' => 'Monthly meeting.',
        ]);

        $this->actingAs($otherStudent)->get(route('reservations.show', $reservation))->assertForbidden();
        $this->actingAs($otherStudent)->delete(route('reservations.cancel', $reservation))->assertForbidden();
        $this->actingAs($owner)->delete(route('reservations.cancel', $reservation))->assertRedirect(route('reservations.index'));
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'status' => 'cancelled']);

        $reservation->update(['status' => 'pending']);
        $this->actingAs($admin)->put(route('reservations.respond', $reservation), ['status' => 'approved'])->assertRedirect(route('reservations.index'));
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'status' => 'approved', 'reviewed_by' => $admin->id]);
    }

    public function test_staff_can_manage_inventory_but_cannot_approve_requests(): void
    {
        $staff = $this->student(['role' => 'staff']);

        $this->actingAs($staff)->post('/facilities', [
            'name' => 'Auditorium', 'location' => 'Main Campus', 'capacity' => 300, 'is_available' => 1,
        ])->assertRedirect('/facilities');
        $facility = Facility::sole();
        $this->assertDatabaseHas('facilities', ['id' => $facility->id, 'name' => 'Auditorium']);

        $reservation = Reservation::create([
            'user_id' => $this->student()->id,
            'facility_id' => $facility->id,
            'starts_at' => now()->addDays(3)->setTime(10, 0),
            'ends_at' => now()->addDays(3)->setTime(11, 0),
            'pax' => 80,
            'purpose' => 'Assembly',
            'description' => 'Student assembly.',
        ]);
        $this->actingAs($staff)->put(route('reservations.respond', $reservation), ['status' => 'approved'])->assertForbidden();
    }

    public function test_microsoft_sign_in_is_configurable_and_student_profiles_are_required(): void
    {
        config([
            'services.microsoft.client_id' => null,
            'services.microsoft.client_secret' => null,
        ]);
        $this->get('/auth/microsoft')->assertServiceUnavailable();

        config([
            'services.microsoft.client_id' => 'test-client-id',
            'services.microsoft.client_secret' => 'test-client-secret',
            'services.microsoft.redirect' => 'http://localhost/auth/microsoft/callback',
        ]);
        $this->get('/auth/microsoft')->assertRedirect();

        $student = $this->student(['organization' => null, 'position' => null]);
        $this->actingAs($student)->get('/dashboard')->assertRedirect(route('profile.edit'));
        $this->put(route('profile.update'), ['organization' => 'Robotics Club', 'position' => 'Treasurer'])
            ->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', ['id' => $student->id, 'organization' => 'Robotics Club', 'position' => 'Treasurer']);
    }

    public function test_flutter_web_callback_redirects_to_configured_url_with_exchangeable_code(): void
    {
        config(['ferms.flutter_web_url' => 'https://flutter.example.test']);
        config([
            'services.microsoft.client_id' => 'test-client-id',
            'services.microsoft.client_secret' => 'test-client-secret',
        ]);
        $identity = \Mockery::mock();
        $identity->shouldReceive('getEmail')->once()->andReturn('flutter.student@mcc.edu.ph');
        $identity->shouldReceive('getName')->once()->andReturn('Flutter Student');
        $provider = \Mockery::mock();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://login.microsoftonline.com/'));
        $provider->shouldReceive('user')->once()->andReturn($identity);
        Socialite::shouldReceive('driver')->twice()->with('microsoft')->andReturn($provider);

        $this->get('/auth/microsoft?client=web')
            ->assertRedirect('https://login.microsoftonline.com/')
            ->assertSessionHas('ferms_auth_client', 'flutter_web');

        $response = $this->get(route('microsoft.callback'))->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertSame('https://flutter.example.test', strtok($location, '?'));

        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $query['code']);

        $this->postJson('/api/v1/auth/exchange', ['code' => $query['code']])
            ->assertOk()
            ->assertJsonPath('user.email', 'flutter.student@mcc.edu.ph');
        $this->postJson('/api/v1/auth/exchange', ['code' => $query['code']])->assertNotFound();
    }

    public function test_normal_web_callback_still_redirects_to_laravel_dashboard(): void
    {
        $admin = User::factory()->create(['email' => 'admin@mcc.edu.ph', 'role' => 'admin']);
        config([
            'services.microsoft.client_id' => 'test-client-id',
            'services.microsoft.client_secret' => 'test-client-secret',
        ]);
        $identity = \Mockery::mock();
        $identity->shouldReceive('getEmail')->once()->andReturn($admin->email);
        $identity->shouldReceive('getName')->once()->andReturn($admin->name);
        $provider = \Mockery::mock();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://login.microsoftonline.com/'));
        $provider->shouldReceive('user')->once()->andReturn($identity);
        Socialite::shouldReceive('driver')->twice()->with('microsoft')->andReturn($provider);

        $this->get('/auth/microsoft')
            ->assertRedirect('https://login.microsoftonline.com/')
            ->assertSessionHas('ferms_auth_client', 'laravel_web');
        $this->get(route('microsoft.callback'))
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_mobile_callback_keeps_using_the_mobile_deep_link(): void
    {
        config(['ferms.mobile_callback_uri' => 'mccferms://auth/callback']);
        config([
            'services.microsoft.client_id' => 'test-client-id',
            'services.microsoft.client_secret' => 'test-client-secret',
        ]);
        $identity = \Mockery::mock();
        $identity->shouldReceive('getEmail')->once()->andReturn('mobile.student@mcc.edu.ph');
        $identity->shouldReceive('getName')->once()->andReturn('Mobile Student');
        $provider = \Mockery::mock();
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://login.microsoftonline.com/'));
        $provider->shouldReceive('user')->once()->andReturn($identity);
        Socialite::shouldReceive('driver')->twice()->with('microsoft')->andReturn($provider);

        $this->get('/auth/microsoft?client=mobile')
            ->assertRedirect('https://login.microsoftonline.com/')
            ->assertSessionHas('ferms_auth_client', 'mobile');
        $response = $this->get(route('microsoft.callback'))->assertRedirect();
        $location = $response->headers->get('Location');

        $this->assertSame('mccferms://auth/callback', strtok($location, '?'));
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', $query['code']);
    }

    public function test_flutter_web_origin_is_added_to_cors_allowlist(): void
    {
        $origin = 'https://flutter.example.test:8080';
        $previousOrigin = getenv('FERMS_FLUTTER_WEB_URL');
        $previousServerOrigin = $_SERVER['FERMS_FLUTTER_WEB_URL'] ?? null;
        $previousEnvOrigin = $_ENV['FERMS_FLUTTER_WEB_URL'] ?? null;
        putenv('FERMS_FLUTTER_WEB_URL='.$origin);
        $_SERVER['FERMS_FLUTTER_WEB_URL'] = $origin;
        $_ENV['FERMS_FLUTTER_WEB_URL'] = $origin;

        try {
            $cors = require base_path('config/cors.php');
            $this->assertContains($origin, $cors['allowed_origins']);
            config(['cors.allowed_origins' => $cors['allowed_origins']]);

            $this->call('OPTIONS', '/api/v1/auth/exchange', [], [], [], [
                'HTTP_ORIGIN' => $origin,
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            ])->assertNoContent()->assertHeader('access-control-allow-origin', $origin);
        } finally {
            putenv($previousOrigin === false ? 'FERMS_FLUTTER_WEB_URL' : 'FERMS_FLUTTER_WEB_URL='.$previousOrigin);
            if ($previousServerOrigin === null) {
                unset($_SERVER['FERMS_FLUTTER_WEB_URL']);
            } else {
                $_SERVER['FERMS_FLUTTER_WEB_URL'] = $previousServerOrigin;
            }
            if ($previousEnvOrigin === null) {
                unset($_ENV['FERMS_FLUTTER_WEB_URL']);
            } else {
                $_ENV['FERMS_FLUTTER_WEB_URL'] = $previousEnvOrigin;
            }
        }
    }

    public function test_super_admin_can_provision_and_edit_mcc_accounts(): void
    {
        $superAdmin = $this->student(['role' => 'super_admin']);
        $this->actingAs($superAdmin)->get('/accounts')->assertOk()->assertSee('Student organization profiles');

        $this->actingAs($superAdmin)->post('/accounts', [
            'name' => 'Campus Staff',
            'email' => 'staff@mcc.edu.ph',
            'role' => 'staff',
        ])->assertRedirect('/accounts');
        $staff = User::where('email', 'staff@mcc.edu.ph')->sole();

        $this->actingAs($superAdmin)->put(route('accounts.update', $staff), [
            'name' => 'Campus Administrator',
            'email' => 'admin@mcc.edu.ph',
            'role' => 'admin',
            'organization' => null,
            'position' => null,
        ])->assertRedirect('/accounts');
        $this->assertDatabaseHas('users', ['id' => $staff->id, 'name' => 'Campus Administrator', 'email' => 'admin@mcc.edu.ph', 'role' => 'admin']);
    }

    public function test_flutter_can_exchange_a_single_use_code_and_call_the_json_api(): void
    {
        $student = $this->student();
        $facility = Facility::create(['name' => 'Mobile Lab', 'location' => 'Technology Center', 'capacity' => 28]);
        $equipment = Equipment::create(['name' => 'Tablet Set', 'category' => 'Computing', 'quantity_available' => 8]);
        $code = bin2hex(random_bytes(32));
        MobileAuthCode::create([
            'code_hash' => hash('sha256', $code),
            'user_id' => $student->id,
            'expires_at' => now()->addMinutes(3),
        ]);

        $exchange = $this->postJson('/api/v1/auth/exchange', ['code' => $code])
            ->assertOk()
            ->assertJsonPath('user.id', $student->id)
            ->assertJsonPath('user.role', 'student');
        $token = $exchange->json('token');

        $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.email', $student->email);
        $this->withToken($token)->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.facilities_available', 1);
        $this->withToken($token)->getJson('/api/v1/facilities')->assertOk()->assertJsonPath('data.0.name', 'Mobile Lab');
        $this->withToken($token)->getJson('/api/v1/equipment')->assertOk()->assertJsonPath('data.0.name', 'Tablet Set');

        $reservation = $this->withToken($token)->postJson('/api/v1/reservations', [
            'facility_id' => $facility->id,
            'date' => now()->addDays(2)->toDateString(),
            'start_time' => '13:00',
            'end_time' => '14:00',
            'pax' => 20,
            'purpose' => 'Mobile API rehearsal',
            'description' => 'Reservation submitted from Flutter.',
            'equipment' => [$equipment->id => 2],
        ])->assertCreated()->assertJsonPath('data.status', 'pending');

        $this->withToken($token)->getJson('/api/v1/reservations')->assertOk()->assertJsonPath('data.0.purpose', 'Mobile API rehearsal');
        $this->postJson('/api/v1/auth/exchange', ['code' => $code])->assertNotFound();
        $this->withToken($token)->deleteJson('/api/v1/auth/session')->assertOk();
    }
}
