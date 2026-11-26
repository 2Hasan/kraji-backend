<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\MaintenanceSchedule;
use App\Models\NotificationLog;
use App\Models\OdometerReading;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\NotificationSender;
use App\Services\PushGateway;
use App\Services\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Mockery;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_by_kilometers_within_500_km(): void
    {
        $schedule = new MaintenanceSchedule(['next_due_km' => 10000]);
        $service = new ReminderService();

        $this->assertTrue($service->isDue($schedule, 9500, Carbon::parse('2026-11-26')));
        $this->assertFalse($service->isDue($schedule, 9499, Carbon::parse('2026-11-26')));
    }

    public function test_due_by_date_within_14_days(): void
    {
        $today = Carbon::parse('2026-11-26');
        $service = new ReminderService();

        $this->assertTrue($service->isDue(new MaintenanceSchedule(['next_due_date' => '2026-12-10']), 0, $today));
        $this->assertFalse($service->isDue(new MaintenanceSchedule(['next_due_date' => '2026-12-11']), 0, $today));
    }

    public function test_odometer_prompt_at_most_every_14_days(): void
    {
        $user = User::factory()->create();
        $vehicle = Vehicle::create([
            'owner_user_id' => $user->id, 'chassis_no' => 'JN1AZ34E12345678X', 'chassis_hash' => str_repeat('a', 64),
            'make' => 'Nissan', 'model' => '370Z', 'status' => 'active', 'source' => 'manual',
        ]);
        $now = Carbon::parse('2026-11-26 12:00');

        OdometerReading::create(['vehicle_id' => $vehicle->id, 'km' => 84200, 'recorded_at' => $now->copy()->subDays(13), 'source' => 'manual']);
        $this->assertFalse((new ReminderService())->needsOdometerPrompt($vehicle, $now));

        OdometerReading::query()->update(['recorded_at' => $now->copy()->subDays(14)]);
        $this->assertTrue((new ReminderService())->needsOdometerPrompt($vehicle, $now));
    }

    public function test_disabled_notification_type_is_not_sent_or_logged(): void
    {
        $user = User::factory()->create([
            'notification_settings' => ['reminder_km' => false],
        ]);
        Device::create(['user_id' => $user->id, 'fcm_token' => 'tok-1', 'platform' => 'ios']);

        $push = Mockery::mock(PushGateway::class);
        $push->shouldNotReceive('send');
        $this->app->instance(PushGateway::class, $push);

        app(NotificationSender::class)->send($user, 'reminder_km', ['title' => 'x', 'body' => 'y']);

        $this->assertSame(0, NotificationLog::count());
    }

    public function test_enabled_notification_is_sent_and_logged(): void
    {
        $user = User::factory()->create();
        Device::create(['user_id' => $user->id, 'fcm_token' => 'tok-2', 'platform' => 'android']);

        $push = Mockery::mock(PushGateway::class);
        $push->shouldReceive('send')->once();
        $this->app->instance(PushGateway::class, $push);

        app(NotificationSender::class)->send($user, 'reminder_km', ['title' => 'x', 'body' => 'y']);

        $this->assertSame(1, NotificationLog::count());
    }
}
