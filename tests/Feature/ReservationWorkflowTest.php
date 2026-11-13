<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Facility;
use App\Models\MobileAuthCode;
use App\Models\Organization;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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
            'duration_days' => 1,
            'days' => [[
                'date' => now()->addDays(2)->toDateString(),
                'start_time' => '13:00',
                'end_time' => '15:00',
            ]],
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
        $response = $this->actingAs($student)->get('/dashboard')
            ->assertOk()
            ->assertSee('Innovation Hall')
            ->assertSee('id="theme-toggle"', false)
            ->assertSee('Switch to dark mode')
            ->assertSee("localStorage.setItem('ferms-theme', theme)", false)
            ->assertSee('id="page-loader"', false)
            ->assertSee('loader-emblem', false)
            ->assertDontSee('Loading your workspace')
            ->assertSee('data-reveal', false)
            ->assertSee('IntersectionObserver', false)
            ->assertSee('prefers-reduced-motion: reduce');
        $this->assertStringContainsString('background-color .65s ease', $response->getContent());

        $response = $this->actingAs($student)->post('/reservations', $this->requestData($facility, $equipment));
        $reservation = Reservation::sole();

        $response->assertRedirect(route('reservations.show', $reservation));
        $response->assertSessionHas('request_submitted', true);
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'status' => 'pending', 'user_id' => $student->id]);
        $this->assertSame(2, $reservation->equipment()->first()->pivot->quantity);
        $this->assertSame($reservation->ends_at->copy()->addDays(2)->timestamp, $reservation->return_due_at->timestamp);
        $this->actingAs($student)->withSession(['request_submitted' => true])
            ->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('request-sent-modal')
            ->assertSee('Printed letters still need to be signed')
            ->assertSee('Done')
            ->assertDontSee('Review request');
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
            'days' => [[
                'date' => now()->addDays(2)->toDateString(),
                'start_time' => '12:00',
                'end_time' => '08:00',
            ]],
        ]))->assertSessionHasErrors(['days.0.end_time']);

        $valid = $this->requestData($facility, $equipment);
        $this->actingAs($student)->post('/reservations', $valid)->assertSessionHasNoErrors();
        $this->actingAs($student)->from('/reservations/create')->post('/reservations', $valid)->assertSessionHasErrors(['facility_conflict']);

        $this->actingAs($student)->from('/reservations/create')->post('/reservations', $this->requestData($facility, $equipment, [
            'days' => [[
                'date' => now()->addDays(2)->toDateString(),
                'start_time' => '07:00',
                'end_time' => '08:00',
            ]],
        ]))->assertSessionHasErrors(['days.0.start_time']);

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
            'duration_days' => 1,
            'days' => [[
                'date' => now()->addDays(2)->toDateString(),
                'start_time' => '10:00',
                'end_time' => '11:00',
            ]],
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

    public function test_equipment_catalog_has_search_and_category_filters(): void
    {
        $student = $this->student();
        Equipment::create(['name' => 'Projector', 'category' => 'Audio Visual', 'quantity_available' => 2]);
        Equipment::create(['name' => 'Wireless Microphone', 'category' => 'Audio', 'quantity_available' => 4]);
        Equipment::create(['name' => 'Folding Chairs', 'category' => null, 'quantity_available' => 30]);

        $this->actingAs($student)->get('/equipment')
            ->assertOk()
            ->assertSee('id="equipment-search"', false)
            ->assertSee('id="equipment-category"', false)
            ->assertSee('Audio Visual')
            ->assertSee('Wireless Microphone')
            ->assertSee('Uncategorized')
            ->assertSee('data-category="Audio Visual"', false)
            ->assertSee('id="equipment-no-matches"', false)
            ->assertSee('aria-live="polite"', false);
    }

    public function test_equipment_availability_is_checked_independently_for_each_event_day(): void
    {
        $equipment = Equipment::create(['name' => 'Portable Speaker', 'category' => 'Audio', 'quantity_available' => 2]);
        $firstDate = now()->addDays(2)->toDateString();
        $secondDate = now()->addDays(3)->toDateString();
        foreach ([$firstDate, $secondDate] as $index => $date) {
            $this->actingAs($this->student())->post('/reservations', [
                'facility_id' => null,
                'duration_days' => 1,
                'days' => [['date' => $date, 'start_time' => '10:00', 'end_time' => '11:00']],
                'pax' => 10,
                'purpose' => 'Equipment hold '.$index,
                'description' => 'One unit for this day.',
                'equipment' => [$equipment->id => 1],
            ])->assertRedirect();
        }

        $this->actingAs($this->student())->post('/reservations', [
            'facility_id' => null,
            'duration_days' => 2,
            'days' => [
                ['date' => $firstDate, 'start_time' => '10:00', 'end_time' => '11:00'],
                ['date' => $secondDate, 'start_time' => '10:00', 'end_time' => '11:00'],
            ],
            'pax' => 10,
            'purpose' => 'Two-day equipment hold',
            'description' => 'One unit on each day.',
            'equipment' => [$equipment->id => 1],
        ])->assertRedirect();

        $this->assertSame(3, Reservation::count());
    }

    public function test_multiday_slots_drive_conflicts_and_privacy_safe_calendar(): void
    {
        $requester = $this->student(['email' => 'event.owner@mcc.edu.ph']);
        $visitor = $this->student(['email' => 'calendar.viewer@mcc.edu.ph']);
        $facility = Facility::create(['name' => 'Innovation Hall', 'location' => 'North Wing', 'capacity' => 40]);
        $equipment = Equipment::create(['name' => 'Projector', 'category' => 'AV', 'quantity_available' => 2]);
        $firstDate = now()->addDays(2);
        $lastDate = now()->addDays(4);
        $multiDayRequest = $this->requestData($facility, $equipment, [
            'duration_days' => 2,
            'days' => [
                ['date' => $firstDate->toDateString(), 'start_time' => '09:00', 'end_time' => '11:00'],
                ['date' => $lastDate->toDateString(), 'start_time' => '14:00', 'end_time' => '16:00'],
            ],
        ]);

        $this->actingAs($requester)->post('/reservations', $multiDayRequest)->assertRedirect();
        $reservation = Reservation::sole();
        $this->assertCount(2, $reservation->schedules);

        $gapRequest = $this->requestData($facility, $equipment, [
            'days' => [[
                'date' => $firstDate->copy()->addDay()->toDateString(),
                'start_time' => '09:00',
                'end_time' => '11:00',
            ]],
        ]);
        $this->actingAs($visitor)->post('/reservations', $gapRequest)->assertRedirect();
        $this->assertSame(2, Reservation::count());

        $conflictingRequest = $this->requestData($facility, $equipment, [
            'days' => [[
                'date' => $lastDate->toDateString(),
                'start_time' => '15:00',
                'end_time' => '17:00',
            ]],
        ]);
        $this->actingAs($visitor)
            ->from('/reservations/create')
            ->followingRedirects()
            ->post('/reservations', $conflictingRequest)
            ->assertOk()
            ->assertSee('facility-conflict-modal')
            ->assertSee('Please communicate with the facility staff');
        $this->assertSame(2, Reservation::count());

        $month = $firstDate->format('Y-m');
        $this->actingAs($visitor)->get(route('calendar.index', ['month' => $month]))
            ->assertOk()
            ->assertSee('Innovation Hall')
            ->assertSee('Pending')
            ->assertDontSee('Student leadership workshop')
            ->assertDontSee('event.owner@mcc.edu.ph');

        $token = $visitor->createToken('calendar-view')->plainTextToken;
        $calendarData = $this->withToken($token)->getJson('/api/v1/calendar?month='.$month)
            ->assertOk()
            ->json('data');
        $this->assertCount(3, $calendarData);
        foreach ($calendarData as $event) {
            $this->assertSame(['facility', 'starts_at', 'ends_at', 'status'], array_keys($event));
            $this->assertSame('Innovation Hall', $event['facility']);
        }

        $equipmentConflict = $this->requestData($facility, $equipment, [
            'facility_id' => null,
            'days' => [[
                'date' => $lastDate->toDateString(),
                'start_time' => '15:00',
                'end_time' => '17:00',
            ]],
        ]);
        $this->actingAs($visitor)->from('/reservations/create')->post('/reservations', $equipmentConflict)
            ->assertSessionHasErrors(['equipment.'.$equipment->id]);
    }

    public function test_reservation_rejects_more_than_seven_event_days_or_mismatched_day_count(): void
    {
        $student = $this->student();
        $equipment = Equipment::create(['name' => 'Microphone', 'category' => 'Audio', 'quantity_available' => 3]);
        $day = ['date' => now()->addDays(2)->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00'];

        $this->actingAs($student)->from('/reservations/create')->post('/reservations', [
            'duration_days' => 8,
            'days' => array_fill(0, 8, $day),
            'equipment' => [$equipment->id => 1],
            'pax' => 10,
            'purpose' => 'Week-long activity',
            'description' => 'An activity exceeding the supported duration.',
        ])->assertSessionHasErrors(['duration_days', 'days']);

        $this->actingAs($student)->from('/reservations/create')->post('/reservations', [
            'duration_days' => 2,
            'days' => [$day],
            'equipment' => [$equipment->id => 1],
            'pax' => 10,
            'purpose' => 'Missing event day',
            'description' => 'The number of dates must match the number of days.',
        ])->assertSessionHasErrors(['duration_days']);
    }

    public function test_student_with_incomplete_profile_can_still_view_facility_calendar(): void
    {
        $student = $this->student(['organization' => null, 'position' => null]);

        $this->actingAs($student)->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('Facility calendar');
    }

    public function test_student_can_enter_an_unlisted_organization_without_adding_a_choice(): void
    {
        $student = $this->student(['organization' => null, 'position' => null]);
        Organization::create(['name' => 'Robotics Club']);

        $this->actingAs($student)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Other (not listed)')
            ->assertSee('other_organization');
        $this->actingAs($student)->put(route('profile.update'), [
            'organization' => '__other__',
            'other_organization' => 'Campus Arts Council',
            'position' => 'President',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', ['id' => $student->id, 'organization' => 'Campus Arts Council']);
        $this->assertSame(1, Organization::count());
        $this->actingAs($student->fresh())->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('value="Campus Arts Council"', false)
            ->assertSee('value="__other__" selected', false);

        $superAdmin = $this->student(['role' => 'super_admin']);
        $this->actingAs($superAdmin)->get(route('accounts.index'))
            ->assertOk()
            ->assertSee('Campus Arts Council (custom)');
        $this->actingAs($superAdmin)->put(route('accounts.update', $student), [
            'name' => $student->name,
            'email' => $student->email,
            'role' => 'student',
            'organization' => 'Campus Arts Council',
            'position' => 'President',
        ])->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('users', ['id' => $student->id, 'organization' => 'Campus Arts Council']);
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
        $this->actingAs($admin)->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('data-confirm-message="Approve this reservation request?"', false)
            ->assertSee('id="confirmation-modal"', false)
            ->assertDontSee('onsubmit="return confirm(', false);
        $this->actingAs($admin)->get(route('reservations.index'))
            ->assertOk()
            ->assertSee('data-confirm-message="Reject this reservation request?"', false);
        $this->actingAs($owner)->delete(route('reservations.cancel', $reservation))->assertRedirect(route('reservations.index'));
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'status' => 'cancelled']);

        $reservation->update(['status' => 'pending']);
        $this->actingAs($admin)->put(route('reservations.respond', $reservation), ['status' => 'approved'])->assertRedirect(route('reservations.index'));
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'status' => 'approved', 'reviewed_by' => $admin->id]);
    }

    public function test_reservation_decision_times_are_visible_to_users_who_can_view_the_request(): void
    {
        $owner = $this->student();
        $otherStudent = $this->student();
        $admin = $this->student(['role' => 'admin']);
        $staff = $this->student(['role' => 'staff']);
        $approvedAt = now()->subMinutes(20);
        $rejectedAt = now()->subMinutes(10);
        $approved = Reservation::create([
            'user_id' => $owner->id,
            'starts_at' => now()->addDays(3)->setTime(10, 0),
            'ends_at' => now()->addDays(3)->setTime(11, 0),
            'purpose' => 'Approved student event',
            'description' => 'Approved event description.',
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'reviewed_at' => $approvedAt,
        ]);
        $rejected = Reservation::create([
            'user_id' => $owner->id,
            'starts_at' => now()->addDays(4)->setTime(10, 0),
            'ends_at' => now()->addDays(4)->setTime(11, 0),
            'purpose' => 'Rejected student event',
            'description' => 'Rejected event description.',
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => $rejectedAt,
        ]);

        $this->actingAs($owner)->get(route('reservations.index'))
            ->assertOk()
            ->assertSee('Approved on '.$approvedAt->format('M j, Y g:i A'));
        $this->actingAs($owner)->get(route('reservations.index', ['view' => 'history']))
            ->assertOk()
            ->assertSee('Rejected on '.$rejectedAt->format('M j, Y g:i A'));
        $this->actingAs($owner)->get(route('reservations.show', $approved))
            ->assertOk()
            ->assertSee('Approved on '.$approvedAt->format('M j, Y g:i A'));
        $this->actingAs($owner)->get(route('reservations.show', $rejected))
            ->assertOk()
            ->assertSee('Rejected on '.$rejectedAt->format('M j, Y g:i A'));

        $this->actingAs($otherStudent)->get(route('reservations.index'))
            ->assertOk()
            ->assertDontSee('Approved student event')
            ->assertDontSee('Rejected student event');
        $this->actingAs($otherStudent)->get(route('reservations.show', $approved))->assertForbidden();
        $this->actingAs($admin)->get(route('reservations.index'))
            ->assertOk()
            ->assertSee('Approved on '.$approvedAt->format('M j, Y g:i A'));
        $this->actingAs($staff)->get(route('reservations.show', $rejected))
            ->assertOk()
            ->assertSee('Rejected on '.$rejectedAt->format('M j, Y g:i A'));
    }

    public function test_staff_can_manage_inventory_but_cannot_approve_requests(): void
    {
        $staff = $this->student(['role' => 'staff']);

        $this->actingAs($staff)->post('/facilities', [
            'name' => 'Auditorium', 'location' => 'Main Campus', 'capacity' => 300,
            'staff_in_charge' => 'Campus Facilities Desk', 'is_available' => 1,
        ])->assertRedirect('/facilities');
        $facility = Facility::sole();
        $this->assertDatabaseHas('facilities', [
            'id' => $facility->id,
            'name' => 'Auditorium',
            'staff_in_charge' => 'Campus Facilities Desk',
        ]);
        $this->actingAs($staff)->post('/equipment', [
            'name' => 'Portable Screen',
            'staff_in_charge' => 'Media Services',
            'quantity_available' => 2,
            'is_available' => 1,
        ])->assertRedirect('/equipment');
        $this->assertDatabaseHas('equipment', [
            'name' => 'Portable Screen',
            'category' => null,
            'staff_in_charge' => 'Media Services',
        ]);

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

        $superAdmin = $this->student(['role' => 'super_admin']);
        $unusedFacility = Facility::create([
            'name' => 'Unused Room', 'location' => 'Main Campus', 'capacity' => 20, 'is_available' => true,
        ]);
        $this->actingAs($superAdmin)->delete(route('facilities.destroy', $unusedFacility))
            ->assertRedirect(route('facilities.index'));
        $this->assertDatabaseMissing('facilities', ['id' => $unusedFacility->id]);
    }

    public function test_staff_can_recommend_approval_for_admin_review(): void
    {
        $staff = $this->student(['role' => 'staff']);
        $admin = $this->student(['role' => 'admin']);
        $student = $this->student();
        $facility = Facility::create(['name' => 'Arts Hall', 'location' => 'Main Campus', 'capacity' => 80]);
        $reservation = Reservation::create([
            'user_id' => $student->id,
            'facility_id' => $facility->id,
            'starts_at' => now()->addDays(3)->setTime(10, 0),
            'ends_at' => now()->addDays(3)->setTime(11, 0),
            'pax' => 40,
            'purpose' => 'Campus exhibit',
            'description' => 'Student art exhibit.',
        ]);

        $this->actingAs($staff)->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('Recommend approval');
        $this->actingAs($admin)->post(route('reservations.recommend', $reservation))->assertForbidden();
        $this->actingAs($student)->post(route('reservations.recommend', $reservation))->assertForbidden();

        $this->actingAs($staff)->post(route('reservations.recommend', $reservation))
            ->assertRedirect(route('reservations.index'))
            ->assertSessionHas('success', 'Your recommendation was sent to administrators for review.');
        $reservation->refresh();
        $this->assertSame('recommended', $reservation->status);
        $this->assertSame($staff->id, $reservation->recommended_by);
        $this->assertNotNull($reservation->recommended_at);

        $this->actingAs($admin)->get(route('reservations.show', $reservation))
            ->assertOk()
            ->assertSee('Recommending approval')
            ->assertSee($staff->name)
            ->assertSee('Approve');
        $this->actingAs($admin)->put(route('reservations.respond', $reservation), ['status' => 'approved'])
            ->assertRedirect(route('reservations.index'));
        $this->assertDatabaseHas('reservations', [
            'id' => $reservation->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
            'recommended_by' => $staff->id,
        ]);

        $reservation->update(['status' => 'recommended']);
        $secondRequest = $this->requestData($facility, Equipment::create([
            'name' => 'Display Monitor',
            'category' => 'AV',
            'quantity_available' => 3,
        ]), [
            'days' => [[
                'date' => $reservation->starts_at->toDateString(),
                'start_time' => '10:00',
                'end_time' => '11:00',
            ]],
        ]);
        $this->actingAs($this->student())->from('/reservations/create')
            ->post('/reservations', $secondRequest)
            ->assertSessionHasErrors(['facility_conflict']);
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
        Organization::create(['name' => 'Robotics Club']);
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

    public function test_mobile_staff_recommendation_api_preserves_audit_data_and_student_privacy(): void
    {
        $staff = $this->student(['role' => 'staff']);
        $admin = $this->student(['role' => 'admin']);
        $student = $this->student();
        $otherStudent = $this->student();
        $facility = Facility::create(['name' => 'Mobile Arts Hall', 'location' => 'Main Campus', 'capacity' => 80]);
        $reservation = Reservation::create([
            'user_id' => $student->id,
            'facility_id' => $facility->id,
            'starts_at' => now()->addDays(3)->setTime(10, 0),
            'ends_at' => now()->addDays(3)->setTime(11, 0),
            'pax' => 40,
            'purpose' => 'Mobile exhibit',
            'description' => 'Reservation submitted from Flutter.',
        ]);
        $this->postJson('/api/v1/reservations/'.$reservation->id.'/recommend')->assertUnauthorized();
        Sanctum::actingAs($admin);
        $this->postJson('/api/v1/reservations/'.$reservation->id.'/recommend')->assertForbidden();
        Sanctum::actingAs($student);
        $this->postJson('/api/v1/reservations/'.$reservation->id.'/recommend')->assertForbidden();

        Sanctum::actingAs($staff);
        $recommendation = $this->postJson('/api/v1/reservations/'.$reservation->id.'/recommend', ['status' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'recommended')
            ->assertJsonPath('data.recommended_by', $staff->id)
            ->assertJsonPath('data.recommender.id', $staff->id)
            ->assertJsonPath('data.recommender.name', $staff->name);
        $this->assertNotNull($recommendation->json('data.recommended_at'));
        $this->assertNull($recommendation->json('data.reviewed_at'));
        $this->postJson('/api/v1/reservations/'.$reservation->id.'/recommend')->assertStatus(409);

        Sanctum::actingAs($student);
        $this->getJson('/api/v1/reservations')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'recommended')
            ->assertJsonPath('data.0.recommended_by', $staff->id)
            ->assertJsonPath('data.0.recommender.name', $staff->name);
        Sanctum::actingAs($otherStudent);
        $this->getJson('/api/v1/reservations')
            ->assertOk()
            ->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/reservations/'.$reservation->id)->assertForbidden();
        Sanctum::actingAs($student);
        $this->getJson('/api/v1/reservations/'.$reservation->id)
            ->assertOk()
            ->assertJsonPath('data.recommender.name', $staff->name)
            ->assertJsonPath('data.reviewer', null);

        Sanctum::actingAs($staff);
        $this->putJson('/api/v1/reservations/'.$reservation->id.'/decision', ['status' => 'approved'])
            ->assertForbidden();
        Sanctum::actingAs($admin);
        $decision = $this->putJson('/api/v1/reservations/'.$reservation->id.'/decision', ['status' => 'approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.reviewed_by', $admin->id)
            ->assertJsonPath('data.reviewer.name', $admin->name)
            ->assertJsonPath('data.recommender.name', $staff->name);
        $this->assertNotNull($decision->json('data.reviewed_at'));
    }

    public function test_recommended_reservations_remain_busy_and_calendar_status_is_generic(): void
    {
        $student = $this->student();
        $facility = Facility::create(['name' => 'Reserved Hall', 'location' => 'Main Campus', 'capacity' => 60]);
        $equipment = Equipment::create(['name' => 'Reserved Projector', 'category' => 'AV', 'quantity_available' => 2]);
        $startsAt = now()->addDays(4)->setTime(10, 0);
        $reservation = Reservation::create([
            'user_id' => $this->student(['role' => 'staff'])->id,
            'facility_id' => $facility->id,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addHour(),
            'pax' => 20,
            'purpose' => 'Private reservation detail',
            'description' => 'This must not appear in availability endpoints.',
            'status' => 'recommended',
        ]);
        $reservation->equipment()->attach($equipment->id, ['quantity' => 2]);

        $token = $student->createToken('availability')->plainTextToken;
        $month = $startsAt->format('Y-m');
        $this->withToken($token)->getJson('/api/v1/calendar?month='.$month)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending')
            ->assertJsonMissingPath('data.0.requester')
            ->assertJsonMissingPath('data.0.purpose');
        $calendarEntry = $this->withToken($student->createToken('calendar-shape')->plainTextToken)
            ->getJson('/api/v1/calendar?month='.$month)->json('data.0');
        $this->assertSame(['facility', 'starts_at', 'ends_at', 'status'], array_keys($calendarEntry));

        $this->withToken($student->createToken('facility-schedule')->plainTextToken)
            ->getJson('/api/v1/facilities')
            ->assertOk()
            ->assertJsonPath('data.0.schedule.0.status', 'pending');
        $this->withToken($student->createToken('equipment-schedule')->plainTextToken)
            ->getJson('/api/v1/equipment')
            ->assertOk()
            ->assertJsonPath('data.0.schedule.0.status', 'pending')
            ->assertDontSee('Private reservation detail');

        $facilityConflict = $this->requestData($facility, $equipment, [
            'days' => [[
                'date' => $startsAt->toDateString(),
                'start_time' => '10:00',
                'end_time' => '11:00',
            ]],
            'equipment' => [],
        ]);
        $this->withToken($token)->postJson('/api/v1/reservations', $facilityConflict)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('facility_conflict');

        $equipmentConflict = $this->requestData($facility, $equipment, [
            'facility_id' => null,
            'days' => [[
                'date' => $startsAt->toDateString(),
                'start_time' => '10:00',
                'end_time' => '11:00',
            ]],
            'equipment' => [$equipment->id => 1],
        ]);
        $this->withToken($token)->postJson('/api/v1/reservations', $equipmentConflict)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('equipment.'.$equipment->id);
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

    public function test_it_administrator_can_create_other_it_administrators_up_to_three(): void
    {
        $itAdministrator = $this->student(['role' => 'super_admin']);
        $this->actingAs($itAdministrator)->get(route('accounts.index'))
            ->assertOk()
            ->assertSee('IT Administrator accounts: 1 of 3')
            ->assertSee('IT Administrator');

        $this->actingAs($this->student(['role' => 'admin']))->post(route('accounts.store'), [
            'name' => 'Not Authorized',
            'email' => 'not-authorized@mcc.edu.ph',
            'role' => 'super_admin',
        ])->assertForbidden();

        foreach ([2, 3] as $number) {
            $this->actingAs($itAdministrator)->post(route('accounts.store'), [
                'name' => 'IT Administrator '.$number,
                'email' => 'it-admin-'.$number.'@mcc.edu.ph',
                'role' => 'super_admin',
            ])->assertRedirect(route('accounts.index'));
        }

        $this->actingAs($itAdministrator)->post(route('accounts.store'), [
            'name' => 'Fourth IT Administrator',
            'email' => 'it-admin-4@mcc.edu.ph',
            'role' => 'super_admin',
        ])->assertSessionHasErrors('role');

        $this->assertSame(3, User::where('role', 'super_admin')->count());
        $this->assertDatabaseMissing('users', ['email' => 'it-admin-4@mcc.edu.ph']);
    }

    public function test_super_admin_can_manage_organizations_and_students_choose_from_them(): void
    {
        $superAdmin = $this->student(['role' => 'super_admin']);
        $student = $this->student(['organization' => null, 'position' => null]);

        $this->actingAs($superAdmin)->post(route('organizations.store'), [
            'organization_name' => 'Robotics Club',
        ])->assertRedirect(route('accounts.index'));
        $token = $superAdmin->createToken('organization-list')->plainTextToken;
        $this->withToken($token)->getJson('/api/v1/organizations')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Robotics Club');

        $this->actingAs($student)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Robotics Club');
        $this->actingAs($student)->put(route('profile.update'), [
            'organization' => 'Robotics Club',
            'position' => 'President',
        ])->assertRedirect(route('dashboard'));

        $organization = Organization::sole();
        $this->actingAs($superAdmin)->delete(route('organizations.destroy', $organization))
            ->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('users', ['id' => $student->id, 'organization' => null]);
        $this->withToken($token)->getJson('/api/v1/organizations')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_super_admin_can_suspend_reactivate_and_delete_non_super_admin_accounts(): void
    {
        $superAdmin = $this->student(['role' => 'super_admin']);
        $student = $this->student(['organization' => 'Student Council', 'position' => 'President']);

        $this->actingAs($superAdmin)->patch(route('accounts.suspension', $student), ['is_suspended' => 1])
            ->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('users', ['id' => $student->id, 'is_suspended' => true]);
        $this->actingAs($student->fresh())->get('/dashboard')->assertRedirect(route('login'));
        $this->actingAs($student->fresh())->getJson('/api/v1/me')->assertForbidden();

        $this->actingAs($superAdmin)->patch(route('accounts.suspension', $student), ['is_suspended' => 0])
            ->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('users', ['id' => $student->id, 'is_suspended' => false]);

        $this->actingAs($superAdmin)->delete(route('accounts.destroy', $student))
            ->assertRedirect(route('accounts.index'));
        $this->assertDatabaseMissing('users', ['id' => $student->id]);
        $this->actingAs($superAdmin)->delete(route('accounts.destroy', $superAdmin))->assertForbidden();
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
            'duration_days' => 1,
            'days' => [[
                'date' => now()->addDays(2)->toDateString(),
                'start_time' => '13:00',
                'end_time' => '14:00',
            ]],
            'pax' => 20,
            'purpose' => 'Mobile API rehearsal',
            'description' => 'Reservation submitted from Flutter.',
            'equipment' => [$equipment->id => 2],
        ])->assertCreated()->assertJsonPath('data.status', 'pending');
        $reservation->assertJsonCount(1, 'data.schedules');

        $this->withToken($token)->getJson('/api/v1/reservations')->assertOk()->assertJsonPath('data.0.purpose', 'Mobile API rehearsal');
        $this->postJson('/api/v1/auth/exchange', ['code' => $code])->assertNotFound();
        $this->withToken($token)->deleteJson('/api/v1/auth/session')->assertOk();
    }
}
