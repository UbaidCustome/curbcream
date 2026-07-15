<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CurbCream Analytics Report</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 24px; color: #122433; }
        h1, h2 { margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin: 16px 0 24px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f3f8f6; }
        .meta { color: #5b7384; margin-bottom: 24px; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()" style="margin-bottom:16px;padding:8px 14px;">Print / Save PDF</button>
    <h1>CurbCream Analytics Report</h1>
    <div class="meta">Generated: {{ now()->format('Y-m-d H:i') }}</div>

    <h2>Active Users</h2>
    <table>
        <tr><th>Daily</th><th>Weekly</th><th>Monthly</th></tr>
        <tr>
            <td>{{ $activeUsers['daily'] }}</td>
            <td>{{ $activeUsers['weekly'] }}</td>
            <td>{{ $activeUsers['monthly'] }}</td>
        </tr>
    </table>

    <h2>Average Job Completion</h2>
    <p>{{ $avgCompletionHours }} hours</p>

    <h2>Most Booked Services</h2>
    <table>
        <tr><th>Service</th><th>Jobs</th></tr>
        @forelse($mostBookedServices as $service)
            <tr><td>{{ $service->name }}</td><td>{{ $service->jobs_count }}</td></tr>
        @empty
            <tr><td colspan="2">No data</td></tr>
        @endforelse
    </table>

    <h2>Peak Booking Hours</h2>
    <table>
        <tr><th>Hour</th><th>Bookings</th></tr>
        @forelse($peakHours as $row)
            <tr><td>{{ str_pad($row->hour, 2, '0', STR_PAD_LEFT) }}:00</td><td>{{ $row->total }}</td></tr>
        @empty
            <tr><td colspan="2">No data</td></tr>
        @endforelse
    </table>

    <h2>Provider Performance</h2>
    <table>
        <tr><th>Provider</th><th>Completed Jobs</th><th>Avg Rating</th></tr>
        @forelse($providerPerformance as $provider)
            <tr>
                <td>{{ $provider->business_name ?: $provider->name }}</td>
                <td>{{ $provider->completed_jobs }}</td>
                <td>{{ $provider->avg_rating }}</td>
            </tr>
        @empty
            <tr><td colspan="3">No data</td></tr>
        @endforelse
    </table>
</body>
</html>
