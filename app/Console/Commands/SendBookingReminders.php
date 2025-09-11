<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BookingRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SendScheduleBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';
    protected $description = 'Send reminders for upcoming scheduled bookings';

    public function handle()
    {
        $now = Carbon::now();
        \Log::info("⏰ ScheduleReminder running at: " . $now->toDateTimeString());
    
        $reminderTime = $now->copy()->addMinutes(15); // 15 min before ride
    
        $bookings = BookingRequest::where('request_type', 'Schedule')
            ->where('status', 'Accepted')
            ->whereTime('ride_time', '=', $reminderTime->format('H:i'))
            ->get();
    
        \Log::info("🔍 Found {$bookings->count()} bookings for reminder at {$reminderTime->format('H:i')}");
    
        foreach ($bookings as $booking) {
            // socket emit
            Http::withoutVerifying()->post(env('SOCKET_SERVER_URL').'/emit/schedule-reminder', [
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'driver_id' => $booking->driver_id,
                'ride_time' => $booking->ride_time,
                'message' => 'Your scheduled ride will start in 15 minutes!',
            ]);
    
            \Log::info("📩 Reminder sent for booking ID {$booking->id}");
            $this->info("Reminder sent for booking ID {$booking->id}");
        }
    
        return Command::SUCCESS;
    }

}